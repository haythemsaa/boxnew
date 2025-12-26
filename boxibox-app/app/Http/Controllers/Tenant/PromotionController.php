<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\Promotion;
use App\Models\BookingPromoCode;
use App\Models\Site;
use App\Models\Contract;
use App\Models\Booking;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;
use Inertia\Response;

class PromotionController extends Controller
{
    /**
     * Display a listing of promotions and promo codes.
     */
    public function index(Request $request): Response
    {
        $tenantId = Auth::user()->tenant_id;

        // Get promotions
        $promotions = Promotion::where('tenant_id', $tenantId)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'description' => $p->description,
                'type' => $p->type,
                'value' => $p->value,
                'discount_label' => $this->formatDiscountLabel($p->type, $p->value),
                'start_date' => $p->start_date?->format('d/m/Y'),
                'end_date' => $p->end_date?->format('d/m/Y'),
                'max_uses' => $p->max_uses,
                'used_count' => $p->used_count,
                'is_active' => $p->is_active,
                'is_valid' => $p->isValid(),
                'applicable_to' => $p->applicable_to,
            ]);

        // Get booking promo codes
        $promoCodes = BookingPromoCode::where('tenant_id', $tenantId)
            ->with('site')
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'description' => $p->description,
                'discount_type' => $p->discount_type,
                'discount_value' => $p->discount_value,
                'discount_label' => $p->discount_label,
                'site_id' => $p->site_id,
                'site_name' => $p->site?->name ?? 'Tous les sites',
                'valid_from' => $p->valid_from?->format('d/m/Y'),
                'valid_until' => $p->valid_until?->format('d/m/Y'),
                'max_uses' => $p->max_uses,
                'uses_count' => $p->uses_count,
                'is_active' => $p->is_active,
                'is_valid' => $p->isValid(),
                'first_time_only' => $p->first_time_only,
                'min_rental_months' => $p->min_rental_months,
            ]);

        // Stats
        $stats = [
            'total_promotions' => $promotions->count(),
            'active_promotions' => $promotions->where('is_active', true)->count(),
            'total_promo_codes' => $promoCodes->count(),
            'active_promo_codes' => $promoCodes->where('is_active', true)->count(),
            'total_uses' => $promotions->sum('used_count') + $promoCodes->sum('uses_count'),
            'expiring_soon' => $promotions->filter(function ($p) {
                return $p['is_active'] && $p['end_date'] &&
                    now()->diffInDays($p['end_date'], false) <= 7 &&
                    now()->diffInDays($p['end_date'], false) >= 0;
            })->count(),
        ];

        // Get sites for filtering/assignment
        $sites = Site::where('tenant_id', $tenantId)
            ->get(['id', 'name', 'code']);

        return Inertia::render('Tenant/Marketing/Promotions', [
            'promotions' => $promotions,
            'promoCodes' => $promoCodes,
            'stats' => $stats,
            'sites' => $sites,
            'filters' => $request->only(['status', 'type', 'site_id']),
        ]);
    }

    /**
     * Store a new promotion.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:percentage,fixed_amount,free_month',
            'value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'max_uses' => 'nullable|integer|min:1',
            'min_rental_amount' => 'nullable|numeric|min:0',
            'applicable_to' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $tenantId = Auth::user()->tenant_id;

        // Generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = $this->generateUniqueCode($tenantId);
        } else {
            $validated['code'] = strtoupper($validated['code']);
        }

        // Check uniqueness
        if (Promotion::where('tenant_id', $tenantId)
            ->where('code', $validated['code'])
            ->exists()) {
            return back()->withErrors(['code' => 'Ce code promotion existe deja.']);
        }

        $validated['tenant_id'] = $tenantId;
        $validated['used_count'] = 0;
        $validated['is_active'] = $validated['is_active'] ?? true;

        Promotion::create($validated);

        return redirect()->route('tenant.marketing.promotions.index')
            ->with('success', 'Promotion creee avec succes.');
    }

    /**
     * Update a promotion.
     */
    public function update(Request $request, Promotion $promotion): RedirectResponse
    {
        if ($promotion->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'type' => 'required|in:percentage,fixed_amount,free_month',
            'value' => 'required|numeric|min:0',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'max_uses' => 'nullable|integer|min:1',
            'min_rental_amount' => 'nullable|numeric|min:0',
            'applicable_to' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $promotion->update($validated);

        return redirect()->route('tenant.marketing.promotions.index')
            ->with('success', 'Promotion mise a jour.');
    }

    /**
     * Delete a promotion.
     */
    public function destroy(Promotion $promotion): RedirectResponse
    {
        if ($promotion->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $promotion->delete();

        return redirect()->route('tenant.marketing.promotions.index')
            ->with('success', 'Promotion supprimee.');
    }

    /**
     * Toggle promotion status.
     */
    public function toggle(Promotion $promotion): RedirectResponse
    {
        if ($promotion->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $promotion->update(['is_active' => !$promotion->is_active]);

        $status = $promotion->is_active ? 'activee' : 'desactivee';

        return back()->with('success', "Promotion {$status}.");
    }

    // ============================================
    // Promo Codes (for online booking)
    // ============================================

    /**
     * Store a new promo code.
     */
    public function storePromoCode(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'code' => 'nullable|string|max:50',
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'discount_type' => 'required|in:percentage,fixed,free_months',
            'discount_value' => 'required|numeric|min:0',
            'site_id' => 'nullable|exists:sites,id',
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'max_uses' => 'nullable|integer|min:1',
            'min_rental_amount' => 'nullable|numeric|min:0',
            'min_rental_months' => 'nullable|integer|min:1',
            'first_time_only' => 'boolean',
            'applicable_box_types' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $tenantId = Auth::user()->tenant_id;

        // Generate code if not provided
        if (empty($validated['code'])) {
            $validated['code'] = $this->generateUniquePromoCode($tenantId);
        } else {
            $validated['code'] = strtoupper($validated['code']);
        }

        // Check uniqueness
        if (BookingPromoCode::where('tenant_id', $tenantId)
            ->where('code', $validated['code'])
            ->exists()) {
            return back()->withErrors(['code' => 'Ce code promo existe deja.']);
        }

        // Verify site belongs to tenant
        if (!empty($validated['site_id'])) {
            $site = Site::find($validated['site_id']);
            if ($site->tenant_id !== $tenantId) {
                abort(403);
            }
        }

        $validated['tenant_id'] = $tenantId;
        $validated['uses_count'] = 0;
        $validated['is_active'] = $validated['is_active'] ?? true;
        $validated['first_time_only'] = $validated['first_time_only'] ?? false;

        BookingPromoCode::create($validated);

        return redirect()->route('tenant.marketing.promotions.index')
            ->with('success', 'Code promo cree avec succes.');
    }

    /**
     * Update a promo code.
     */
    public function updatePromoCode(Request $request, BookingPromoCode $promoCode): RedirectResponse
    {
        $tenantId = Auth::user()->tenant_id;

        if ($promoCode->tenant_id !== $tenantId) {
            abort(403);
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'discount_type' => 'required|in:percentage,fixed,free_months',
            'discount_value' => 'required|numeric|min:0',
            'site_id' => ['nullable', 'exists:sites,id', new \App\Rules\SameTenantResource(\App\Models\Site::class, $tenantId)],
            'valid_from' => 'nullable|date',
            'valid_until' => 'nullable|date|after_or_equal:valid_from',
            'max_uses' => 'nullable|integer|min:1',
            'min_rental_amount' => 'nullable|numeric|min:0',
            'min_rental_months' => 'nullable|integer|min:1',
            'first_time_only' => 'boolean',
            'applicable_box_types' => 'nullable|array',
            'is_active' => 'boolean',
        ]);

        $promoCode->update($validated);

        return redirect()->route('tenant.marketing.promotions.index')
            ->with('success', 'Code promo mis a jour.');
    }

    /**
     * Delete a promo code.
     */
    public function destroyPromoCode(BookingPromoCode $promoCode): RedirectResponse
    {
        if ($promoCode->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $promoCode->delete();

        return redirect()->route('tenant.marketing.promotions.index')
            ->with('success', 'Code promo supprime.');
    }

    /**
     * Toggle promo code status.
     */
    public function togglePromoCode(BookingPromoCode $promoCode): RedirectResponse
    {
        if ($promoCode->tenant_id !== Auth::user()->tenant_id) {
            abort(403);
        }

        $promoCode->update(['is_active' => !$promoCode->is_active]);

        $status = $promoCode->is_active ? 'active' : 'desactive';

        return back()->with('success', "Code promo {$status}.");
    }

    // ============================================
    // API: Validate promo code
    // ============================================

    /**
     * Validate a promo code for booking (API).
     */
    public function validateCode(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string',
            'tenant_id' => 'required|integer',
            'site_id' => 'nullable|integer',
            'monthly_price' => 'required|numeric|min:0',
            'rental_months' => 'nullable|integer|min:1',
            'customer_email' => 'nullable|email',
        ]);

        // Find valid promo code
        $promoCode = BookingPromoCode::findValidCode(
            $validated['code'],
            $validated['tenant_id'],
            $validated['site_id'] ?? null
        );

        if (!$promoCode) {
            return response()->json([
                'valid' => false,
                'error' => 'Code promo invalide ou expire.',
            ]);
        }

        // Check first-time-only restriction
        if ($promoCode->first_time_only && !empty($validated['customer_email'])) {
            $hasBooking = Booking::where('tenant_id', $validated['tenant_id'])
                ->where('customer_email', $validated['customer_email'])
                ->whereIn('status', ['confirmed', 'completed'])
                ->exists();

            if ($hasBooking) {
                return response()->json([
                    'valid' => false,
                    'error' => 'Ce code est reserve aux nouveaux clients.',
                ]);
            }
        }

        // Calculate discount
        $discount = $promoCode->calculateDiscount(
            $validated['monthly_price'],
            $validated['rental_months'] ?? 1
        );

        return response()->json([
            'valid' => true,
            'code' => $promoCode->code,
            'name' => $promoCode->name,
            'discount_type' => $promoCode->discount_type,
            'discount_value' => $promoCode->discount_value,
            'discount_amount' => $discount,
            'discount_label' => $promoCode->discount_label,
            'description' => $promoCode->description,
        ]);
    }

    /**
     * Apply a promo code to a booking/contract.
     */
    public function applyCode(Request $request)
    {
        $tenantId = Auth::user()->tenant_id;

        $validated = $request->validate([
            'code' => 'required|string',
            'booking_id' => ['nullable', 'exists:bookings,id', new \App\Rules\SameTenantResource(\App\Models\Booking::class, $tenantId)],
            'contract_id' => ['nullable', 'exists:contracts,id', new \App\Rules\SameTenantResource(\App\Models\Contract::class, $tenantId)],
        ]);

        // Find the target
        if (!empty($validated['booking_id'])) {
            $booking = Booking::findOrFail($validated['booking_id']);
            if ($booking->tenant_id !== $tenantId) {
                abort(403);
            }

            $promoCode = BookingPromoCode::findValidCode($validated['code'], $tenantId, $booking->site_id);

            if (!$promoCode) {
                return response()->json([
                    'success' => false,
                    'error' => 'Code promo invalide.',
                ], 400);
            }

            $discount = $promoCode->calculateDiscount($booking->monthly_price, $booking->rental_months ?? 1);

            // Update booking
            $booking->update([
                'promo_code_id' => $promoCode->id,
                'promo_code' => $promoCode->code,
                'discount_amount' => $discount,
            ]);

            $promoCode->incrementUses();

            return response()->json([
                'success' => true,
                'discount_amount' => $discount,
                'new_total' => $booking->monthly_price - $discount,
            ]);
        }

        if (!empty($validated['contract_id'])) {
            $contract = Contract::findOrFail($validated['contract_id']);
            if ($contract->tenant_id !== $tenantId) {
                abort(403);
            }

            $promotion = Promotion::where('tenant_id', $tenantId)
                ->where('code', strtoupper($validated['code']))
                ->where('is_active', true)
                ->first();

            if (!$promotion || !$promotion->isValid()) {
                return response()->json([
                    'success' => false,
                    'error' => 'Code promotion invalide.',
                ], 400);
            }

            $discount = $promotion->calculateDiscount($contract->monthly_price);

            // Update contract
            $contract->update([
                'promotion_id' => $promotion->id,
                'promotion_code' => $promotion->code,
                'discount_amount' => $discount,
            ]);

            $promotion->incrementUsage();

            return response()->json([
                'success' => true,
                'discount_amount' => $discount,
                'new_monthly' => $contract->monthly_price - $discount,
            ]);
        }

        return response()->json([
            'success' => false,
            'error' => 'Cible non specifiee.',
        ], 400);
    }

    // ============================================
    // Reports & Analytics
    // ============================================

    /**
     * Promotion usage report.
     */
    public function usageReport(Request $request): Response
    {
        $tenantId = Auth::user()->tenant_id;

        // Promotions usage
        $promotions = Promotion::where('tenant_id', $tenantId)
            ->orderBy('used_count', 'desc')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'type' => $p->type,
                'value' => $p->value,
                'discount_label' => $this->formatDiscountLabel($p->type, $p->value),
                'used_count' => $p->used_count,
                'max_uses' => $p->max_uses,
                'usage_rate' => $p->max_uses ? round(($p->used_count / $p->max_uses) * 100, 1) : null,
                'is_active' => $p->is_active,
                'start_date' => $p->start_date?->format('d/m/Y'),
                'end_date' => $p->end_date?->format('d/m/Y'),
            ]);

        // Promo codes usage
        $promoCodes = BookingPromoCode::where('tenant_id', $tenantId)
            ->with('site')
            ->orderBy('uses_count', 'desc')
            ->get()
            ->map(fn($p) => [
                'id' => $p->id,
                'code' => $p->code,
                'name' => $p->name,
                'discount_label' => $p->discount_label,
                'uses_count' => $p->uses_count,
                'max_uses' => $p->max_uses,
                'usage_rate' => $p->max_uses ? round(($p->uses_count / $p->max_uses) * 100, 1) : null,
                'site_name' => $p->site?->name ?? 'Tous',
                'is_active' => $p->is_active,
            ]);

        // Bookings with promo codes
        $bookingsWithPromo = Booking::where('tenant_id', $tenantId)
            ->whereNotNull('promo_code')
            ->with(['site'])
            ->latest()
            ->limit(50)
            ->get()
            ->map(fn($b) => [
                'id' => $b->id,
                'promo_code' => $b->promo_code,
                'discount_amount' => $b->discount_amount ?? 0,
                'monthly_price' => $b->monthly_price,
                'site_name' => $b->site?->name,
                'status' => $b->status,
                'created_at' => $b->created_at->format('d/m/Y'),
            ]);

        // Stats
        $totalDiscount = $bookingsWithPromo->sum('discount_amount');
        $avgDiscount = $bookingsWithPromo->count() > 0
            ? $bookingsWithPromo->avg('discount_amount')
            : 0;

        $stats = [
            'total_promotions' => $promotions->count(),
            'total_promo_codes' => $promoCodes->count(),
            'total_usage' => $promotions->sum('used_count') + $promoCodes->sum('uses_count'),
            'total_discount_given' => $totalDiscount,
            'average_discount' => round($avgDiscount, 2),
            'bookings_with_promo' => $bookingsWithPromo->count(),
        ];

        return Inertia::render('Tenant/Marketing/PromotionReport', [
            'promotions' => $promotions,
            'promoCodes' => $promoCodes,
            'recentBookings' => $bookingsWithPromo,
            'stats' => $stats,
        ]);
    }

    // ============================================
    // Helpers
    // ============================================

    protected function formatDiscountLabel(string $type, float $value): string
    {
        return match ($type) {
            'percentage' => "{$value}%",
            'fixed_amount', 'fixed' => number_format($value, 2, ',', ' ') . ' €',
            'free_month', 'free_months' => "{$value} mois gratuit(s)",
            default => (string) $value,
        };
    }

    protected function generateUniqueCode(int $tenantId): string
    {
        do {
            $code = 'PROMO' . strtoupper(Str::random(6));
        } while (Promotion::where('tenant_id', $tenantId)->where('code', $code)->exists());

        return $code;
    }

    protected function generateUniquePromoCode(int $tenantId): string
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (BookingPromoCode::where('tenant_id', $tenantId)->where('code', $code)->exists());

        return $code;
    }
}
