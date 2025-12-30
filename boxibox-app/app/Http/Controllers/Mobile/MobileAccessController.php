<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\BoxAccessLog;
use App\Models\BoxAccessShare;
use App\Models\Contract;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Inertia\Inertia;
use SimpleSoftwareIO\QrCode\Facades\QrCode;

class MobileAccessController extends Controller
{
    /**
     * Get access page with QR code and access history
     */
    public function index(Request $request)
    {
        $customer = $this->getCustomer($request);
        $tenantId = $customer->tenant_id;

        // Get active contracts with boxes
        $contracts = Contract::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->with(['box', 'site'])
            ->get();

        // Get recent access history
        $accessHistory = BoxAccessLog::where('customer_id', $customer->id)
            ->with(['box', 'accessShare'])
            ->latest('accessed_at')
            ->take(20)
            ->get();

        // Get active shares
        $activeShares = BoxAccessShare::where('customer_id', $customer->id)
            ->where('status', 'active')
            ->where('valid_until', '>', now())
            ->with(['box', 'accessLogs'])
            ->get();

        return Inertia::render('Mobile/Access/Index', [
            'contracts' => $contracts,
            'accessHistory' => $accessHistory,
            'activeShares' => $activeShares,
        ]);
    }

    /**
     * Generate QR code for box access
     */
    public function generateQrCode(Request $request, Contract $contract)
    {
        $customer = $this->getCustomer($request);

        if ($contract->customer_id !== $customer->id) {
            abort(403);
        }

        // Generate unique access token
        $accessToken = Str::random(32);
        $expiresAt = now()->addMinutes(5);

        // Store token temporarily
        cache()->put("box_access_{$accessToken}", [
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'box_id' => $contract->box_id,
            'expires_at' => $expiresAt,
        ], $expiresAt);

        // Generate QR code data
        $qrData = json_encode([
            'type' => 'box_access',
            'token' => $accessToken,
            'box_id' => $contract->box_id,
            'expires' => $expiresAt->timestamp,
        ]);

        $qrCode = QrCode::format('svg')
            ->size(300)
            ->margin(2)
            ->generate($qrData);

        return response()->json([
            'qr_code' => base64_encode($qrCode),
            'access_code' => $contract->access_code,
            'expires_at' => $expiresAt->toISOString(),
            'box' => [
                'id' => $contract->box_id,
                'name' => $contract->box?->name,
            ],
        ]);
    }

    /**
     * Get access history for a specific box
     */
    public function history(Request $request, Contract $contract)
    {
        $customer = $this->getCustomer($request);

        if ($contract->customer_id !== $customer->id) {
            abort(403);
        }

        $history = BoxAccessLog::where('contract_id', $contract->id)
            ->with(['accessShare'])
            ->latest('accessed_at')
            ->paginate(30);

        return response()->json($history);
    }

    /**
     * Share access page
     */
    public function shareForm(Request $request, Contract $contract)
    {
        $customer = $this->getCustomer($request);

        if ($contract->customer_id !== $customer->id) {
            abort(403);
        }

        $existingShares = BoxAccessShare::where('contract_id', $contract->id)
            ->where('customer_id', $customer->id)
            ->where('status', 'active')
            ->get();

        return Inertia::render('Mobile/Access/Share', [
            'contract' => $contract->load(['box', 'site']),
            'existingShares' => $existingShares,
        ]);
    }

    /**
     * Create shared access
     */
    public function createShare(Request $request, Contract $contract)
    {
        $customer = $this->getCustomer($request);

        if ($contract->customer_id !== $customer->id) {
            abort(403);
        }

        $validated = $request->validate([
            'guest_name' => 'required|string|max:255',
            'guest_phone' => 'nullable|string|max:20',
            'guest_email' => 'nullable|email|max:255',
            'guest_note' => 'nullable|string|max:500',
            'valid_from' => 'required|date|after_or_equal:now',
            'valid_until' => 'required|date|after:valid_from',
            'max_uses' => 'nullable|integer|min:1|max:100',
            'allowed_hours' => 'nullable|array',
            'allowed_hours.start' => 'nullable|date_format:H:i',
            'allowed_hours.end' => 'nullable|date_format:H:i',
            'allowed_days' => 'nullable|array',
            'notify_on_use' => 'boolean',
            'send_sms' => 'boolean',
            'send_email' => 'boolean',
        ]);

        $share = BoxAccessShare::create([
            'tenant_id' => $customer->tenant_id,
            'box_id' => $contract->box_id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'guest_name' => $validated['guest_name'],
            'guest_phone' => $validated['guest_phone'] ?? null,
            'guest_email' => $validated['guest_email'] ?? null,
            'guest_note' => $validated['guest_note'] ?? null,
            'valid_from' => $validated['valid_from'],
            'valid_until' => $validated['valid_until'],
            'max_uses' => $validated['max_uses'] ?? null,
            'allowed_hours' => $validated['allowed_hours'] ?? null,
            'allowed_days' => $validated['allowed_days'] ?? null,
            'notify_on_use' => $validated['notify_on_use'] ?? true,
        ]);

        // Generate and save QR code
        $qrData = json_encode([
            'type' => 'shared_access',
            'code' => $share->share_code,
            'box_id' => $contract->box_id,
        ]);

        $qrCode = QrCode::format('png')
            ->size(400)
            ->margin(2)
            ->generate($qrData);

        $path = "qr-codes/shares/{$share->uuid}.png";
        Storage::disk('public')->put($path, $qrCode);
        $share->update(['qr_code_path' => $path]);

        // Send notifications if requested
        if ($request->boolean('send_sms') && $share->guest_phone) {
            // TODO: Send SMS notification
            $share->update(['sms_sent' => true]);
        }

        if ($request->boolean('send_email') && $share->guest_email) {
            // TODO: Send email notification
            $share->update(['email_sent' => true]);
        }

        return response()->json([
            'success' => true,
            'share' => $share->load(['box']),
            'share_url' => $share->getShareUrl(),
            'qr_code_url' => Storage::disk('public')->url($path),
        ]);
    }

    /**
     * Revoke shared access
     */
    public function revokeShare(Request $request, BoxAccessShare $share)
    {
        $customer = $this->getCustomer($request);

        if ($share->customer_id !== $customer->id) {
            abort(403);
        }

        $share->revoke($request->input('reason'));

        return response()->json([
            'success' => true,
            'message' => 'Accès révoqué avec succès',
        ]);
    }

    /**
     * Get share details
     */
    public function getShare(Request $request, BoxAccessShare $share)
    {
        $customer = $this->getCustomer($request);

        if ($share->customer_id !== $customer->id) {
            abort(403);
        }

        return response()->json([
            'share' => $share->load(['box', 'accessLogs']),
            'qr_code_url' => $share->qr_code_path ? Storage::disk('public')->url($share->qr_code_path) : null,
            'share_url' => $share->getShareUrl(),
        ]);
    }

    /**
     * Validate shared access code (public endpoint for door/lock systems)
     */
    public function validateShareCode(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|size:8',
            'box_id' => 'required|integer',
        ]);

        $share = BoxAccessShare::where('share_code', $validated['code'])
            ->where('box_id', $validated['box_id'])
            ->first();

        if (!$share) {
            return response()->json([
                'valid' => false,
                'reason' => 'Code invalide',
            ], 404);
        }

        if (!$share->canBeUsedNow()) {
            $reason = match(true) {
                $share->isExpired() => 'Code expiré',
                $share->isRevoked() => 'Code révoqué',
                $share->isUsedUp() => 'Nombre d\'utilisations atteint',
                default => 'Accès non autorisé à ce moment',
            };

            return response()->json([
                'valid' => false,
                'reason' => $reason,
            ], 403);
        }

        // Log the access
        BoxAccessLog::create([
            'tenant_id' => $share->tenant_id,
            'box_id' => $share->box_id,
            'contract_id' => $share->contract_id,
            'customer_id' => null,
            'access_type' => 'entry',
            'method' => 'shared_access',
            'access_code_used' => $share->share_code,
            'shared_by_customer_id' => $share->customer_id,
            'box_access_share_id' => $share->id,
            'ip_address' => $request->ip(),
            'status' => 'success',
            'accessed_at' => now(),
        ]);

        // Increment usage count
        $share->incrementUsage();

        // Notify owner if enabled
        if ($share->notify_on_use) {
            // TODO: Send push notification to owner
        }

        return response()->json([
            'valid' => true,
            'access_code' => $share->contract->access_code,
            'guest_name' => $share->guest_name,
            'remaining_uses' => $share->remaining_uses,
        ]);
    }

    /**
     * Log manual access (from mobile app)
     */
    public function logAccess(Request $request, Contract $contract)
    {
        $customer = $this->getCustomer($request);

        if ($contract->customer_id !== $customer->id) {
            abort(403);
        }

        $validated = $request->validate([
            'access_type' => 'required|in:entry,exit',
            'method' => 'required|in:code,qr_code,nfc,smart_lock,manual',
            'latitude' => 'nullable|numeric',
            'longitude' => 'nullable|numeric',
        ]);

        $log = BoxAccessLog::create([
            'tenant_id' => $customer->tenant_id,
            'box_id' => $contract->box_id,
            'contract_id' => $contract->id,
            'customer_id' => $customer->id,
            'access_type' => $validated['access_type'],
            'method' => $validated['method'],
            'access_code_used' => $contract->access_code,
            'device_id' => $request->header('X-Device-ID'),
            'device_name' => $request->header('X-Device-Name'),
            'ip_address' => $request->ip(),
            'latitude' => $validated['latitude'] ?? null,
            'longitude' => $validated['longitude'] ?? null,
            'status' => 'success',
            'accessed_at' => now(),
        ]);

        return response()->json([
            'success' => true,
            'log' => $log,
        ]);
    }

    /**
     * Get customer from session
     */
    protected function getCustomer(Request $request): Customer
    {
        $customerId = session('mobile_customer_id');

        if (!$customerId) {
            abort(401);
        }

        return Customer::findOrFail($customerId);
    }
}
