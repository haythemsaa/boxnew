<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\BookingWidget;
use App\Models\Site;
use App\Models\BookingSettings;
use App\Services\PricingService;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use Carbon\Carbon;

class WidgetApiController extends Controller
{
    /**
     * Add CORS headers to response for widget API
     */
    protected function addCorsHeaders(JsonResponse $response, Request $request): JsonResponse
    {
        $origin = $request->header('Origin');

        // Allow the requesting origin for embedded widgets
        if ($origin) {
            $response->headers->set('Access-Control-Allow-Origin', $origin);
            $response->headers->set('Access-Control-Allow-Credentials', 'true');
        } else {
            $response->headers->set('Access-Control-Allow-Origin', '*');
        }

        $response->headers->set('Access-Control-Allow-Methods', 'GET, POST, OPTIONS');
        $response->headers->set('Access-Control-Allow-Headers', 'Content-Type, Accept, Origin, X-Requested-With');

        return $response;
    }

    /**
     * Get widget data for embedded JavaScript widget
     */
    public function getData(Request $request, string $widgetKey): JsonResponse
    {
        $widget = BookingWidget::findByKey($widgetKey);

        if (!$widget) {
            return $this->addCorsHeaders(response()->json([
                'success' => false,
                'error' => 'Widget not found or inactive',
            ], 404), $request);
        }

        // Check domain if restriction is set
        $origin = $request->header('Origin') ?? $request->header('Referer');
        if ($origin) {
            $domain = parse_url($origin, PHP_URL_HOST);
            if (!$widget->isDomainAllowed($domain)) {
                return $this->addCorsHeaders(response()->json([
                    'success' => false,
                    'error' => 'Domain not authorized',
                ], 403), $request);
            }
        }

        // Increment views
        $widget->incrementViews();

        // Get settings
        $settings = BookingSettings::getForTenant($widget->tenant_id, $widget->site_id);
        $tenant = $widget->tenant;

        // Get sites and boxes
        $sitesQuery = Site::where('tenant_id', $tenant->id)
            ->where('is_active', true);

        if ($widget->site_id) {
            $sitesQuery->where('id', $widget->site_id);
        }

        $sites = $sitesQuery->with(['boxes' => function ($q) {
            $q->where('status', 'available')->orderBy('current_price');
        }])->get();

        return $this->addCorsHeaders(response()->json([
            'success' => true,
            'widget' => [
                'key' => $widget->widget_key,
                'type' => $widget->widget_type,
                'style' => $widget->style_config,
            ],
            'settings' => [
                'require_deposit' => $settings?->require_deposit ?? true,
                'deposit_percentage' => $settings?->deposit_percentage ?? 100,
                'deposit_amount' => $settings?->deposit_amount ?? 0,
                'allow_promo_codes' => $settings?->allow_promo_codes ?? true,
                'min_rental_days' => $settings?->min_rental_days ?? 30,
                'require_id_verification' => $settings?->require_id_verification ?? false,
            ],
            'tenant' => [
                'id' => $tenant->id,
                'name' => $tenant->name,
                'slug' => $tenant->slug,
            ],
            'sites' => $sites->map(function ($site) {
                return [
                    'id' => $site->id,
                    'name' => $site->name,
                    'address' => $site->address,
                    'city' => $site->city,
                    'postal_code' => $site->postal_code,
                    'phone' => $site->phone,
                    'available_boxes_count' => $site->boxes->count(),
                    'boxes' => $site->boxes->map(function ($box) {
                        return [
                            'id' => $box->id,
                            'name' => $box->name,
                            'size' => $box->size,
                            'width' => $box->width,
                            'length' => $box->length,
                            'height' => $box->height,
                            'floor' => $box->floor,
                            'price' => $box->current_price,
                            'features' => $box->features ?? [],
                            'images' => $box->images ?? [],
                        ];
                    }),
                ];
            }),
            'booking_url' => url("/booking/{$tenant->slug}"),
        ]), $request);
    }

    /**
     * Calculate pricing for widget
     */
    public function calculatePricing(Request $request, string $widgetKey, PricingService $pricingService): JsonResponse
    {
        $widget = BookingWidget::findByKey($widgetKey);

        if (!$widget) {
            return $this->addCorsHeaders(response()->json([
                'success' => false,
                'error' => 'Widget not found',
            ], 404), $request);
        }

        $request->validate([
            'box_id' => 'required|integer',
            'start_date' => 'required|date',
            'duration_months' => 'nullable|integer|min:1|max:60',
            'promo_code' => 'nullable|string|max:50',
            'include_insurance' => 'nullable|boolean',
        ]);

        $box = \App\Models\Box::where('id', $request->box_id)
            ->where('tenant_id', $widget->tenant_id)
            ->first();

        if (!$box) {
            return $this->addCorsHeaders(response()->json([
                'success' => false,
                'error' => 'Box not found',
            ], 404), $request);
        }

        $startDate = Carbon::parse($request->start_date);
        $settings = BookingSettings::getForTenant($widget->tenant_id, $box->site_id);

        $pricing = $pricingService->calculateBookingTotal(
            $box,
            $startDate,
            $request->duration_months,
            $request->promo_code,
            $settings,
            $request->include_insurance ?? false
        );

        return $this->addCorsHeaders(response()->json([
            'success' => true,
            'pricing' => $pricing,
        ]), $request);
    }

    /**
     * Validate promo code for widget
     */
    public function validatePromoCode(Request $request, string $widgetKey): JsonResponse
    {
        $widget = BookingWidget::findByKey($widgetKey);

        if (!$widget) {
            return $this->addCorsHeaders(response()->json([
                'success' => false,
                'error' => 'Widget not found',
            ], 404), $request);
        }

        $request->validate([
            'code' => 'required|string|max:50',
            'site_id' => 'nullable|integer',
        ]);

        $promo = \App\Models\BookingPromoCode::findValidCode(
            $request->code,
            $widget->tenant_id,
            $request->site_id
        );

        if (!$promo) {
            return $this->addCorsHeaders(response()->json([
                'success' => false,
                'valid' => false,
                'error' => 'Code promo invalide ou expiré',
            ]), $request);
        }

        return $this->addCorsHeaders(response()->json([
            'success' => true,
            'valid' => true,
            'promo' => [
                'code' => $promo->code,
                'name' => $promo->name,
                'type' => $promo->discount_type,
                'value' => $promo->discount_value,
                'label' => $promo->discount_label,
            ],
        ]), $request);
    }

    /**
     * Track widget booking started
     */
    public function trackBooking(Request $request, string $widgetKey): JsonResponse
    {
        $widget = BookingWidget::findByKey($widgetKey);

        if (!$widget) {
            return $this->addCorsHeaders(response()->json(['success' => false], 404), $request);
        }

        $widget->incrementBookings();

        return $this->addCorsHeaders(response()->json(['success' => true]), $request);
    }
}
