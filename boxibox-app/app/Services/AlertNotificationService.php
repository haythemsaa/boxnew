<?php

namespace App\Services;

use App\Models\Notification;
use App\Models\Invoice;
use App\Models\Contract;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;

class AlertNotificationService
{
    /**
     * Types d'alertes avec leurs priorités
     */
    const ALERT_TYPES = [
        'critical_overdue' => ['priority' => 'critical', 'icon' => 'exclamation-triangle', 'color' => 'red'],
        'high_overdue' => ['priority' => 'high', 'icon' => 'exclamation-circle', 'color' => 'orange'],
        'overdue_invoice' => ['priority' => 'medium', 'icon' => 'clock', 'color' => 'yellow'],
        'contract_expiring' => ['priority' => 'medium', 'icon' => 'calendar', 'color' => 'blue'],
        'contract_expired' => ['priority' => 'high', 'icon' => 'x-circle', 'color' => 'red'],
        'payment_received' => ['priority' => 'low', 'icon' => 'check-circle', 'color' => 'green'],
        'new_contract' => ['priority' => 'low', 'icon' => 'document-text', 'color' => 'blue'],
        'new_customer' => ['priority' => 'low', 'icon' => 'user-plus', 'color' => 'green'],
        'low_occupancy' => ['priority' => 'medium', 'icon' => 'chart-bar', 'color' => 'yellow'],
        'high_occupancy' => ['priority' => 'low', 'icon' => 'chart-bar', 'color' => 'green'],
    ];

    /**
     * Générer toutes les alertes pour un tenant
     */
    public function generateAlertsForTenant(int $tenantId): array
    {
        $alerts = [];

        // Alertes sur les impayés
        $alerts = array_merge($alerts, $this->checkOverdueInvoices($tenantId));

        // Alertes sur les contrats expirant
        $alerts = array_merge($alerts, $this->checkExpiringContracts($tenantId));

        // Alertes sur les contrats expirés
        $alerts = array_merge($alerts, $this->checkExpiredContracts($tenantId));

        // Alertes sur l'occupation
        $alerts = array_merge($alerts, $this->checkOccupancyAlerts($tenantId));

        return $alerts;
    }

    /**
     * Vérifier les factures impayées et créer des notifications
     */
    public function checkOverdueInvoices(int $tenantId): array
    {
        $alerts = [];
        $now = Carbon::now();

        // Factures en retard groupées par niveau de gravité
        $overdueInvoices = Invoice::where('tenant_id', $tenantId)
            ->whereIn('status', ['sent', 'overdue', 'partial'])
            ->where('due_date', '<', $now)
            ->with(['customer', 'contract.box'])
            ->get();

        $totalOverdue = 0;
        $criticalCount = 0;
        $highCount = 0;
        $normalCount = 0;

        foreach ($overdueInvoices as $invoice) {
            $daysOverdue = $now->diffInDays($invoice->due_date);
            $amount = $invoice->total - ($invoice->paid_amount ?? 0);
            $totalOverdue += $amount;

            // Catégoriser par gravité
            if ($daysOverdue > 60 || $amount > 1000) {
                $criticalCount++;
                $priority = 'critical_overdue';
            } elseif ($daysOverdue > 30 || $amount > 500) {
                $highCount++;
                $priority = 'high_overdue';
            } else {
                $normalCount++;
                $priority = 'overdue_invoice';
            }

            // Créer notification si elle n'existe pas déjà (éviter les doublons)
            $existingNotification = Notification::where('tenant_id', $tenantId)
                ->where('type', 'payment_reminder')
                ->where('related_type', 'invoice')
                ->where('related_id', $invoice->id)
                ->where('is_read', false)
                ->first();

            if (!$existingNotification) {
                $notification = $this->createNotification($tenantId, [
                    'type' => 'payment_reminder',
                    'priority' => self::ALERT_TYPES[$priority]['priority'],
                    'title' => $this->getOverdueTitle($daysOverdue, $amount),
                    'message' => $this->getOverdueMessage($invoice, $daysOverdue),
                    'related_type' => 'invoice',
                    'related_id' => $invoice->id,
                    'data' => [
                        'invoice_number' => $invoice->invoice_number,
                        'customer_name' => $invoice->customer->full_name ?? 'Client',
                        'amount' => $amount,
                        'days_overdue' => $daysOverdue,
                        'box_number' => $invoice->contract->box->number ?? null,
                        'icon' => self::ALERT_TYPES[$priority]['icon'],
                        'color' => self::ALERT_TYPES[$priority]['color'],
                    ],
                ]);

                if ($notification) {
                    $alerts[] = $notification;
                }
            }
        }

        // Alerte de synthèse si beaucoup d'impayés
        if ($totalOverdue > 5000 || $criticalCount > 3) {
            $summaryExists = Notification::where('tenant_id', $tenantId)
                ->where('type', 'payment_reminder')
                ->where('title', 'LIKE', '%Alerte impayés critique%')
                ->whereDate('created_at', $now->toDateString())
                ->exists();

            if (!$summaryExists) {
                $alerts[] = $this->createNotification($tenantId, [
                    'type' => 'payment_reminder',
                    'priority' => 'critical',
                    'title' => "⚠️ Alerte impayés critique",
                    'message' => sprintf(
                        "Total impayés: %s€ | %d factures critiques, %d urgentes, %d en retard",
                        number_format($totalOverdue, 2, ',', ' '),
                        $criticalCount,
                        $highCount,
                        $normalCount
                    ),
                    'data' => [
                        'total_overdue' => $totalOverdue,
                        'critical_count' => $criticalCount,
                        'high_count' => $highCount,
                        'normal_count' => $normalCount,
                        'icon' => 'exclamation-triangle',
                        'color' => 'red',
                    ],
                ]);
            }
        }

        return $alerts;
    }

    /**
     * Vérifier les contrats qui vont expirer
     */
    public function checkExpiringContracts(int $tenantId): array
    {
        $alerts = [];
        $now = Carbon::now();

        // Contrats expirant dans les 30 prochains jours
        $expiringContracts = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->whereBetween('end_date', [$now, $now->copy()->addDays(30)])
            ->with(['customer', 'box'])
            ->get();

        foreach ($expiringContracts as $contract) {
            $daysUntilExpiry = $now->diffInDays($contract->end_date, false);

            // Éviter les doublons
            $exists = Notification::where('tenant_id', $tenantId)
                ->where('type', 'contract_expiring')
                ->where('related_type', 'contract')
                ->where('related_id', $contract->id)
                ->where('is_read', false)
                ->exists();

            if (!$exists) {
                $urgency = $daysUntilExpiry <= 7 ? 'high' : 'medium';

                $alerts[] = $this->createNotification($tenantId, [
                    'type' => 'contract_expiring',
                    'priority' => $urgency,
                    'title' => $daysUntilExpiry <= 7
                        ? "🔔 Contrat expire dans {$daysUntilExpiry} jours!"
                        : "Contrat expire bientôt",
                    'message' => sprintf(
                        "Le contrat de %s (Box %s) expire le %s",
                        $contract->customer->full_name ?? 'Client',
                        $contract->box->number ?? 'N/A',
                        $contract->end_date->format('d/m/Y')
                    ),
                    'related_type' => 'contract',
                    'related_id' => $contract->id,
                    'data' => [
                        'contract_number' => $contract->contract_number,
                        'customer_name' => $contract->customer->full_name ?? 'Client',
                        'box_number' => $contract->box->number ?? null,
                        'days_until_expiry' => $daysUntilExpiry,
                        'end_date' => $contract->end_date->format('Y-m-d'),
                        'monthly_rent' => $contract->monthly_rent,
                        'icon' => 'calendar',
                        'color' => $daysUntilExpiry <= 7 ? 'orange' : 'blue',
                    ],
                ]);
            }
        }

        return $alerts;
    }

    /**
     * Vérifier les contrats expirés (non renouvelés)
     */
    public function checkExpiredContracts(int $tenantId): array
    {
        $alerts = [];
        $now = Carbon::now();

        // Contrats expirés depuis moins de 30 jours
        $expiredContracts = Contract::where('tenant_id', $tenantId)
            ->where('status', 'active')
            ->where('end_date', '<', $now)
            ->where('end_date', '>=', $now->copy()->subDays(30))
            ->with(['customer', 'box'])
            ->get();

        foreach ($expiredContracts as $contract) {
            $daysExpired = $now->diffInDays($contract->end_date);

            $exists = Notification::where('tenant_id', $tenantId)
                ->where('type', 'contract_expiring')
                ->where('related_type', 'contract')
                ->where('related_id', $contract->id)
                ->where('title', 'LIKE', '%expiré%')
                ->where('is_read', false)
                ->exists();

            if (!$exists) {
                $alerts[] = $this->createNotification($tenantId, [
                    'type' => 'contract_expiring',
                    'priority' => 'high',
                    'title' => "❌ Contrat expiré depuis {$daysExpired} jours",
                    'message' => sprintf(
                        "Le contrat de %s (Box %s) a expiré le %s. Action requise.",
                        $contract->customer->full_name ?? 'Client',
                        $contract->box->number ?? 'N/A',
                        $contract->end_date->format('d/m/Y')
                    ),
                    'related_type' => 'contract',
                    'related_id' => $contract->id,
                    'data' => [
                        'contract_number' => $contract->contract_number,
                        'customer_name' => $contract->customer->full_name ?? 'Client',
                        'box_number' => $contract->box->number ?? null,
                        'days_expired' => $daysExpired,
                        'icon' => 'x-circle',
                        'color' => 'red',
                    ],
                ]);
            }
        }

        return $alerts;
    }

    /**
     * Vérifier les alertes d'occupation
     */
    public function checkOccupancyAlerts(int $tenantId): array
    {
        $alerts = [];

        // Calculer le taux d'occupation global
        $totalBoxes = DB::table('boxes')
            ->where('tenant_id', $tenantId)
            ->count();

        $occupiedBoxes = DB::table('boxes')
            ->where('tenant_id', $tenantId)
            ->where('status', 'occupied')
            ->count();

        if ($totalBoxes > 0) {
            $occupancyRate = ($occupiedBoxes / $totalBoxes) * 100;

            // Alerte si occupation < 70%
            if ($occupancyRate < 70) {
                $exists = Notification::where('tenant_id', $tenantId)
                    ->where('type', 'invoice_generated')
                    ->where('title', 'LIKE', '%occupation faible%')
                    ->whereDate('created_at', Carbon::today())
                    ->exists();

                if (!$exists) {
                    $alerts[] = $this->createNotification($tenantId, [
                        'type' => 'invoice_generated',
                        'priority' => $occupancyRate < 50 ? 'high' : 'medium',
                        'title' => "📉 Taux d'occupation faible",
                        'message' => sprintf(
                            "Taux d'occupation actuel: %.1f%% (%d/%d boxes). Considérez des actions marketing.",
                            $occupancyRate,
                            $occupiedBoxes,
                            $totalBoxes
                        ),
                        'data' => [
                            'occupancy_rate' => $occupancyRate,
                            'occupied_boxes' => $occupiedBoxes,
                            'total_boxes' => $totalBoxes,
                            'available_boxes' => $totalBoxes - $occupiedBoxes,
                            'icon' => 'chart-bar',
                            'color' => 'yellow',
                        ],
                    ]);
                }
            }

            // Alerte si occupation > 95% (presque plein)
            if ($occupancyRate > 95) {
                $exists = Notification::where('tenant_id', $tenantId)
                    ->where('type', 'invoice_generated')
                    ->where('title', 'LIKE', '%occupation élevée%')
                    ->whereDate('created_at', Carbon::today())
                    ->exists();

                if (!$exists) {
                    $alerts[] = $this->createNotification($tenantId, [
                        'type' => 'invoice_generated',
                        'priority' => 'low',
                        'title' => "📈 Taux d'occupation élevé!",
                        'message' => sprintf(
                            "Excellent! Taux d'occupation: %.1f%% (%d/%d boxes). Pensez à augmenter la capacité.",
                            $occupancyRate,
                            $occupiedBoxes,
                            $totalBoxes
                        ),
                        'data' => [
                            'occupancy_rate' => $occupancyRate,
                            'occupied_boxes' => $occupiedBoxes,
                            'total_boxes' => $totalBoxes,
                            'icon' => 'chart-bar',
                            'color' => 'green',
                        ],
                    ]);
                }
            }
        }

        return $alerts;
    }

    /**
     * Créer une notification pour un paiement reçu
     */
    public function notifyPaymentReceived(Payment $payment): ?Notification
    {
        return $this->createNotification($payment->tenant_id, [
            'type' => 'payment_received',
            'priority' => 'low',
            'title' => "💰 Paiement reçu: {$payment->amount}€",
            'message' => sprintf(
                "Paiement de %s€ reçu de %s pour la facture %s",
                number_format($payment->amount, 2, ',', ' '),
                $payment->customer->full_name ?? 'Client',
                $payment->invoice->invoice_number ?? 'N/A'
            ),
            'related_type' => 'payment',
            'related_id' => $payment->id,
            'data' => [
                'amount' => $payment->amount,
                'payment_method' => $payment->payment_method,
                'customer_name' => $payment->customer->full_name ?? 'Client',
                'icon' => 'check-circle',
                'color' => 'green',
            ],
        ]);
    }

    /**
     * Créer une notification pour un nouveau contrat
     */
    public function notifyNewContract(Contract $contract): ?Notification
    {
        return $this->createNotification($contract->tenant_id, [
            'type' => 'message_received',
            'priority' => 'low',
            'title' => "📝 Nouveau contrat créé",
            'message' => sprintf(
                "Nouveau contrat %s pour %s - Box %s (%s€/mois)",
                $contract->contract_number,
                $contract->customer->full_name ?? 'Client',
                $contract->box->number ?? 'N/A',
                number_format($contract->monthly_rent, 2, ',', ' ')
            ),
            'related_type' => 'contract',
            'related_id' => $contract->id,
            'data' => [
                'contract_number' => $contract->contract_number,
                'customer_name' => $contract->customer->full_name ?? 'Client',
                'box_number' => $contract->box->number ?? null,
                'monthly_rent' => $contract->monthly_rent,
                'icon' => 'document-text',
                'color' => 'blue',
            ],
        ]);
    }

    /**
     * Créer une notification pour un nouveau client
     */
    public function notifyNewCustomer(Customer $customer): ?Notification
    {
        return $this->createNotification($customer->tenant_id, [
            'type' => 'message_received',
            'priority' => 'low',
            'title' => "👤 Nouveau client inscrit",
            'message' => sprintf(
                "Nouveau client: %s (%s)",
                $customer->full_name,
                $customer->email ?? 'Pas d\'email'
            ),
            'related_type' => 'customer',
            'related_id' => $customer->id,
            'data' => [
                'customer_name' => $customer->full_name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'icon' => 'user-plus',
                'color' => 'green',
            ],
        ]);
    }

    /**
     * Créer une notification en base de données
     */
    protected function createNotification(int $tenantId, array $data): ?Notification
    {
        try {
            // Trouver les admins du tenant pour les notifier
            $adminUsers = User::where('tenant_id', $tenantId)
                ->whereHas('roles', function ($q) {
                    $q->whereIn('name', ['tenant_admin', 'super_admin', 'manager']);
                })
                ->get();

            $notifications = [];

            foreach ($adminUsers as $user) {
                $notification = Notification::create([
                    'tenant_id' => $tenantId,
                    'user_id' => $user->id,
                    'type' => $data['type'],
                    'title' => $data['title'],
                    'message' => $data['message'],
                    'channels' => ['in_app'],
                    'status' => 'sent',
                    'is_read' => false,
                    'related_type' => $data['related_type'] ?? null,
                    'related_id' => $data['related_id'] ?? null,
                    'data' => $data['data'] ?? [],
                ]);

                $notifications[] = $notification;
            }

            return $notifications[0] ?? null;
        } catch (\Exception $e) {
            Log::error("Erreur création notification: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Obtenir le titre selon le retard
     */
    protected function getOverdueTitle(int $daysOverdue, float $amount): string
    {
        if ($daysOverdue > 60) {
            return "🚨 CRITIQUE: Facture impayée {$daysOverdue}j";
        } elseif ($daysOverdue > 30) {
            return "⚠️ Urgent: Facture impayée {$daysOverdue}j";
        } else {
            return "⏰ Facture en retard ({$daysOverdue}j)";
        }
    }

    /**
     * Obtenir le message détaillé
     */
    protected function getOverdueMessage(Invoice $invoice, int $daysOverdue): string
    {
        $amount = $invoice->total - ($invoice->paid_amount ?? 0);
        return sprintf(
            "Facture %s de %s - Montant: %s€ - Client: %s - Box: %s",
            $invoice->invoice_number,
            $invoice->due_date->format('d/m/Y'),
            number_format($amount, 2, ',', ' '),
            $invoice->customer->full_name ?? 'N/A',
            $invoice->contract->box->number ?? 'N/A'
        );
    }

    /**
     * Obtenir les statistiques des notifications pour un tenant
     */
    public function getNotificationStats(int $tenantId): array
    {
        $unreadCount = Notification::where('tenant_id', $tenantId)
            ->where('is_read', false)
            ->count();

        $criticalCount = Notification::where('tenant_id', $tenantId)
            ->where('is_read', false)
            ->whereJsonContains('data->color', 'red')
            ->count();

        $todayCount = Notification::where('tenant_id', $tenantId)
            ->whereDate('created_at', Carbon::today())
            ->count();

        return [
            'unread' => $unreadCount,
            'critical' => $criticalCount,
            'today' => $todayCount,
        ];
    }
}
