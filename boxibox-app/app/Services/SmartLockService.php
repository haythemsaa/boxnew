<?php

namespace App\Services;

use App\Models\SmartLock;
use App\Models\SmartLockConfiguration;
use App\Models\AccessCode;
use App\Models\AccessLog;
use App\Models\LockCommand;
use App\Models\Contract;
use Carbon\Carbon;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class SmartLockService
{
    protected array $providers = [
        'noke' => NokeProvider::class,
        'salto' => SaltoProvider::class,
        'kisi' => KisiProvider::class,
        'pti' => PtiProvider::class,
    ];

    /**
     * Déverrouiller une serrure
     */
    public function unlock(SmartLock $lock, ?int $userId = null): array
    {
        $provider = $this->getProvider($lock->configuration);

        try {
            $result = $provider->unlock($lock);

            // Logger l'accès
            AccessLog::create([
                'smart_lock_id' => $lock->id,
                'box_id' => $lock->box_id,
                'event_type' => $result['success'] ? 'unlock_success' : 'unlock_failed',
                'access_method' => 'remote',
                'event_at' => now(),
                'raw_response' => $result,
            ]);

            if ($result['success']) {
                $lock->update([
                    'is_locked' => false,
                    'last_unlocked_at' => now(),
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("SmartLock unlock failed: " . $e->getMessage(), [
                'lock_id' => $lock->id,
            ]);

            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Verrouiller une serrure
     */
    public function lock(SmartLock $lock): array
    {
        $provider = $this->getProvider($lock->configuration);

        try {
            $result = $provider->lock($lock);

            AccessLog::create([
                'smart_lock_id' => $lock->id,
                'box_id' => $lock->box_id,
                'event_type' => $result['success'] ? 'lock_success' : 'lock_failed',
                'access_method' => 'remote',
                'event_at' => now(),
                'raw_response' => $result,
            ]);

            if ($result['success']) {
                $lock->update([
                    'is_locked' => true,
                    'last_locked_at' => now(),
                ]);
            }

            return $result;
        } catch (\Exception $e) {
            Log::error("SmartLock lock failed: " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Générer un code d'accès temporaire
     */
    public function generateTemporaryCode(
        SmartLock $lock,
        Carbon $validFrom,
        Carbon $validUntil,
        ?int $customerId = null,
        ?int $contractId = null,
        string $name = 'Code temporaire'
    ): AccessCode {
        $provider = $this->getProvider($lock->configuration);

        // Générer un code unique
        $code = $this->generateUniqueCode($lock);

        // Créer le code sur le système du fournisseur
        $result = $provider->createAccessCode($lock, $code, $validFrom, $validUntil);

        return AccessCode::create([
            'smart_lock_id' => $lock->id,
            'customer_id' => $customerId,
            'contract_id' => $contractId,
            'code' => $code,
            'code_type' => 'temporary',
            'name' => $name,
            'valid_from' => $validFrom,
            'valid_until' => $validUntil,
            'is_active' => $result['success'] ?? true,
        ]);
    }

    /**
     * Générer un code unique
     */
    protected function generateUniqueCode(SmartLock $lock): string
    {
        do {
            $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
        } while (
            AccessCode::where('smart_lock_id', $lock->id)
                ->where('code', $code)
                ->where('is_active', true)
                ->exists()
        );

        return $code;
    }

    /**
     * Révoquer un code d'accès
     */
    public function revokeCode(AccessCode $code, ?int $userId = null, string $reason = null): bool
    {
        $lock = $code->smartLock;
        $provider = $this->getProvider($lock->configuration);

        try {
            $provider->revokeAccessCode($lock, $code->code);

            $code->update([
                'is_active' => false,
                'is_revoked' => true,
                'revoke_reason' => $reason,
                'revoked_by' => $userId,
                'revoked_at' => now(),
            ]);

            return true;
        } catch (\Exception $e) {
            Log::error("Code revocation failed: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Vérifier et verrouiller automatiquement les boxes en impayé
     */
    public function checkAndLockOverdueBoxes(int $siteId): array
    {
        $config = SmartLockConfiguration::where('site_id', $siteId)
            ->where('is_active', true)
            ->where('auto_lock_on_overdue', true)
            ->first();

        if (!$config) {
            return ['locked' => 0];
        }

        $lockedCount = 0;
        $overdueContracts = Contract::where('site_id', $siteId)
            ->where('status', 'active')
            ->where('payment_status', 'overdue')
            ->where('overdue_since', '<=', now()->subDays($config->overdue_days_before_lock))
            ->with('box.smartLock')
            ->get();

        foreach ($overdueContracts as $contract) {
            $lock = $contract->box?->smartLock;
            if ($lock && !$lock->is_locked) {
                $result = $this->lock($lock);
                if ($result['success']) {
                    $lockedCount++;

                    // Révoquer tous les codes actifs
                    AccessCode::where('smart_lock_id', $lock->id)
                        ->where('is_active', true)
                        ->update([
                            'is_active' => false,
                            'is_revoked' => true,
                            'revoke_reason' => 'Impayé - verrouillage automatique',
                            'revoked_at' => now(),
                        ]);

                    // Notifier le client si configuré
                    if ($config->send_lock_notification) {
                        // TODO: Envoyer notification
                    }
                }
            }
        }

        return ['locked' => $lockedCount];
    }

    /**
     * Synchroniser les logs d'accès depuis le fournisseur
     */
    public function syncAccessLogs(SmartLock $lock): int
    {
        $provider = $this->getProvider($lock->configuration);
        $lastSync = $lock->configuration->last_sync_at ?? now()->subDay();

        try {
            $logs = $provider->getAccessLogs($lock, $lastSync);
            $imported = 0;

            foreach ($logs as $log) {
                AccessLog::firstOrCreate([
                    'smart_lock_id' => $lock->id,
                    'event_at' => $log['timestamp'],
                    'event_type' => $this->mapEventType($log['event']),
                ], [
                    'box_id' => $lock->box_id,
                    'access_method' => $log['method'] ?? 'unknown',
                    'code_used' => $log['code'] ?? null,
                    'raw_response' => $log,
                ]);
                $imported++;
            }

            $lock->configuration->update(['last_sync_at' => now()]);

            return $imported;
        } catch (\Exception $e) {
            Log::error("Access log sync failed: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Obtenir le provider pour une configuration
     */
    protected function getProvider(SmartLockConfiguration $config): SmartLockProvider
    {
        $providerSlug = $config->provider->slug;
        $providerClass = $this->providers[$providerSlug] ?? GenericSmartLockProvider::class;

        return new $providerClass($config);
    }

    /**
     * Mapper le type d'événement
     */
    protected function mapEventType(string $event): string
    {
        return match (strtolower($event)) {
            'unlock', 'open', 'access' => 'unlock_success',
            'lock', 'close' => 'lock_success',
            'denied', 'reject' => 'access_denied',
            'invalid', 'bad_code' => 'code_invalid',
            'expired' => 'code_expired',
            default => 'unlock_success',
        };
    }

    /**
     * Obtenir les statistiques d'accès
     */
    public function getAccessStats(int $siteId, ?Carbon $from = null, ?Carbon $to = null): array
    {
        $from = $from ?? now()->subMonth();
        $to = $to ?? now();

        $logs = AccessLog::whereHas('smartLock.configuration', fn($q) => $q->where('site_id', $siteId))
            ->whereBetween('event_at', [$from, $to])
            ->get();

        return [
            'total_accesses' => $logs->where('event_type', 'unlock_success')->count(),
            'failed_attempts' => $logs->whereIn('event_type', ['unlock_failed', 'access_denied', 'code_invalid'])->count(),
            'unique_users' => $logs->whereNotNull('customer_id')->pluck('customer_id')->unique()->count(),
            'by_hour' => $logs->where('event_type', 'unlock_success')
                ->groupBy(fn($l) => $l->event_at->format('H'))
                ->map->count(),
            'by_day' => $logs->where('event_type', 'unlock_success')
                ->groupBy(fn($l) => $l->event_at->format('l'))
                ->map->count(),
        ];
    }
}

/**
 * Interface pour les providers de serrures
 */
interface SmartLockProvider
{
    public function unlock(SmartLock $lock): array;
    public function lock(SmartLock $lock): array;
    public function createAccessCode(SmartLock $lock, string $code, Carbon $from, Carbon $to): array;
    public function revokeAccessCode(SmartLock $lock, string $code): bool;
    public function getAccessLogs(SmartLock $lock, Carbon $since): array;
    public function getBatteryLevel(SmartLock $lock): ?int;
}

/**
 * Provider générique (simulation)
 */
class GenericSmartLockProvider implements SmartLockProvider
{
    protected SmartLockConfiguration $config;

    public function __construct(SmartLockConfiguration $config)
    {
        $this->config = $config;
    }

    public function unlock(SmartLock $lock): array
    {
        // Simulation - en production, appeler l'API du fournisseur
        return ['success' => true, 'message' => 'Lock unlocked'];
    }

    public function lock(SmartLock $lock): array
    {
        return ['success' => true, 'message' => 'Lock locked'];
    }

    public function createAccessCode(SmartLock $lock, string $code, Carbon $from, Carbon $to): array
    {
        return ['success' => true, 'code' => $code];
    }

    public function revokeAccessCode(SmartLock $lock, string $code): bool
    {
        return true;
    }

    public function getAccessLogs(SmartLock $lock, Carbon $since): array
    {
        return [];
    }

    public function getBatteryLevel(SmartLock $lock): ?int
    {
        return rand(20, 100);
    }
}

// Les classes NokeProvider, SaltoProvider, etc. seraient implémentées de manière similaire
// avec les appels API spécifiques à chaque fournisseur
class NokeProvider extends GenericSmartLockProvider {}
class SaltoProvider extends GenericSmartLockProvider {}
class KisiProvider extends GenericSmartLockProvider {}
class PtiProvider extends GenericSmartLockProvider {}
