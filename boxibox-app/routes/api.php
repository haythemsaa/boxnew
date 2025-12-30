<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\API\V1\AuthController;
use App\Http\Controllers\API\V1\SiteController;
use App\Http\Controllers\API\V1\BoxController;
use App\Http\Controllers\API\V1\CustomerController;
use App\Http\Controllers\API\V1\ContractController;
use App\Http\Controllers\API\V1\InvoiceController;
use App\Http\Controllers\API\V1\PaymentController;
use App\Http\Controllers\WebhookController;
use App\Http\Controllers\API\V1\PushNotificationController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

/*
|--------------------------------------------------------------------------
| Health Check Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

Route::prefix('health')->group(function () {
    Route::get('/', [\App\Http\Controllers\API\HealthController::class, 'liveness'])->name('health.liveness');
    Route::get('/ready', [\App\Http\Controllers\API\HealthController::class, 'readiness'])->name('health.readiness');
    Route::get('/detailed', [\App\Http\Controllers\API\HealthController::class, 'detailed'])->name('health.detailed');
});

/*
|--------------------------------------------------------------------------
| Webhook Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

Route::middleware('throttle:webhooks')->group(function () {
    Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripe'])->name('webhooks.stripe');

    // GoCardless SEPA webhooks
    Route::post('/webhooks/gocardless', [\App\Http\Controllers\API\GoCardlessWebhookController::class, 'handle'])->name('webhooks.gocardless');

    // Google Reserve with Google API v3
    Route::prefix('google-reserve/v3')->group(function () {
        Route::get('/health', [\App\Http\Controllers\API\GoogleReserveWebhookController::class, 'healthCheck'])->name('google-reserve.health');
        Route::post('/CheckAvailability', [\App\Http\Controllers\API\GoogleReserveWebhookController::class, 'checkAvailability'])->name('google-reserve.check-availability');
        Route::post('/BatchAvailabilityLookup', [\App\Http\Controllers\API\GoogleReserveWebhookController::class, 'batchAvailabilityLookup'])->name('google-reserve.batch-availability');
        Route::post('/CreateBooking', [\App\Http\Controllers\API\GoogleReserveWebhookController::class, 'createBooking'])->name('google-reserve.create-booking');
        Route::post('/UpdateBooking', [\App\Http\Controllers\API\GoogleReserveWebhookController::class, 'updateBooking'])->name('google-reserve.update-booking');
        Route::post('/GetBookingStatus', [\App\Http\Controllers\API\GoogleReserveWebhookController::class, 'getBookingStatus'])->name('google-reserve.get-booking');
        Route::post('/ListBookings', [\App\Http\Controllers\API\GoogleReserveWebhookController::class, 'listBookings'])->name('google-reserve.list-bookings');
    });

    // Email & SMS Tracking Webhooks
    Route::post('/webhooks/email/{provider}/{token}', [\App\Http\Controllers\API\TrackingController::class, 'handleEmailWebhook'])->name('webhooks.email');
    Route::post('/webhooks/sms/{provider}/{token}', [\App\Http\Controllers\API\TrackingController::class, 'handleSmsWebhook'])->name('webhooks.sms');

    // Provider-specific webhooks (legacy endpoints)
    Route::post('/webhooks/twilio', [\App\Http\Controllers\API\TrackingController::class, 'handleTwilioWebhook'])->name('webhooks.twilio');
    Route::post('/webhooks/mailgun', [\App\Http\Controllers\API\TrackingController::class, 'handleMailgunWebhook'])->name('webhooks.mailgun');
    Route::post('/webhooks/sendinblue', [\App\Http\Controllers\API\TrackingController::class, 'handleSendinblueWebhook'])->name('webhooks.sendinblue');
    Route::post('/webhooks/brevo', [\App\Http\Controllers\API\TrackingController::class, 'handleSendinblueWebhook'])->name('webhooks.brevo');
    Route::post('/webhooks/vonage', [\App\Http\Controllers\API\TrackingController::class, 'handleVonageWebhook'])->name('webhooks.vonage');
});

/*
|--------------------------------------------------------------------------
| Email & SMS Tracking Routes (Public, No Auth)
|--------------------------------------------------------------------------
*/
Route::prefix('track')->group(function () {
    // Email open tracking (1x1 pixel)
    Route::get('/email/open/{trackingId}', [\App\Http\Controllers\API\TrackingController::class, 'trackEmailOpen'])->name('track.email.open');

    // Email link click tracking
    Route::get('/email/click/{linkId}', [\App\Http\Controllers\API\TrackingController::class, 'trackEmailClick'])->name('track.email.click');
});

/*
|--------------------------------------------------------------------------
| API Version 1 Routes
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Public Chatbot API (No Auth Required)
|--------------------------------------------------------------------------
*/
// Legacy route for ChatbotWidget.vue (/api/chatbot)
Route::post('/chatbot', [\App\Http\Controllers\API\ChatbotController::class, 'chat'])->middleware('throttle:60,1')->name('api.chatbot');

// V1 API Routes
Route::prefix('v1/chatbot')->middleware('throttle:60,1')->group(function () {
    Route::get('/provider', [\App\Http\Controllers\API\V1\ChatbotController::class, 'getProviderInfo'])->name('api.v1.chatbot.provider');
    Route::post('/message', [\App\Http\Controllers\API\V1\ChatbotController::class, 'sendMessage'])->name('api.v1.chatbot.message');
    Route::post('/recommend-size', [\App\Http\Controllers\API\V1\ChatbotController::class, 'recommendSize'])->name('api.v1.chatbot.recommend-size');
    Route::get('/conversation/{conversationId}', [\App\Http\Controllers\API\V1\ChatbotController::class, 'getConversation'])->name('api.v1.chatbot.conversation');
    Route::post('/handoff', [\App\Http\Controllers\API\V1\ChatbotController::class, 'requestHandoff'])->name('api.v1.chatbot.handoff');
});

/*
|--------------------------------------------------------------------------
| Public Availability Calendar API (No Auth Required)
|--------------------------------------------------------------------------
*/
Route::prefix('v1/availability')->group(function () {
    Route::get('/calendar', [\App\Http\Controllers\API\V1\AvailabilityController::class, 'calendar'])->name('api.v1.availability.calendar');
    Route::get('/boxes', [\App\Http\Controllers\API\V1\AvailabilityController::class, 'availableBoxes'])->name('api.v1.availability.boxes');
    Route::get('/sites/{site}/box-types', [\App\Http\Controllers\API\V1\AvailabilityController::class, 'boxTypes'])->name('api.v1.availability.box-types');
});

/*
|--------------------------------------------------------------------------
| Public Media Gallery API (No Auth Required)
|--------------------------------------------------------------------------
*/
Route::prefix('v1/gallery')->group(function () {
    Route::get('/sites/{site}', [\App\Http\Controllers\Tenant\MediaGalleryController::class, 'publicGallery'])->name('api.v1.gallery.site');
});

/*
|--------------------------------------------------------------------------
| Referral Code Validation API (Public)
|--------------------------------------------------------------------------
*/
Route::prefix('referral')->middleware('throttle:60,1')->group(function () {
    Route::post('/validate', function (\Illuminate\Http\Request $request) {
        $request->validate([
            'code' => 'required|string|max:20',
            'tenant_id' => 'required|integer|exists:tenants,id',
        ]);

        $referralService = app(\App\Services\ReferralService::class);
        $result = $referralService->validateCode(
            $request->code,
            $request->tenant_id,
            $request->customer_id
        );

        return response()->json($result);
    })->name('api.referral.validate');
});

/*
|--------------------------------------------------------------------------
| Booking Widget API (No Auth Required - CORS enabled)
|--------------------------------------------------------------------------
*/
Route::prefix('widget')->middleware('throttle:120,1')->group(function () {
    // Get widget data (sites, boxes, settings)
    Route::get('/{widgetKey}', [\App\Http\Controllers\API\WidgetApiController::class, 'getData'])->name('api.widget.data');

    // Calculate pricing
    Route::post('/{widgetKey}/pricing', [\App\Http\Controllers\API\WidgetApiController::class, 'calculatePricing'])->name('api.widget.pricing');

    // Validate promo code
    Route::post('/{widgetKey}/promo-validate', [\App\Http\Controllers\API\WidgetApiController::class, 'validatePromoCode'])->name('api.widget.promo');

    // Track booking conversion
    Route::post('/{widgetKey}/track-booking', [\App\Http\Controllers\API\WidgetApiController::class, 'trackBooking'])->name('api.widget.track');
});

/*
|--------------------------------------------------------------------------
| Self-Service Access Control API (For gate/lock systems)
|--------------------------------------------------------------------------
| Rate limited to prevent brute-force attacks on access codes
*/
Route::prefix('v1/access')->middleware('throttle:10,1')->group(function () {
    Route::post('/validate', [\App\Http\Controllers\Tenant\SelfServiceController::class, 'validateAccess'])->name('api.v1.access.validate');

    // Shared access validation (for temporary guest access)
    Route::post('/validate-share', [\App\Http\Controllers\Mobile\MobileAccessController::class, 'validateShareCode'])->name('api.v1.access.validate-share');
});

/*
|--------------------------------------------------------------------------
| Public Referral Validation API (Improved)
|--------------------------------------------------------------------------
*/
Route::prefix('v1/referral')->middleware('throttle:30,1')->group(function () {
    Route::post('/validate', [\App\Http\Controllers\Mobile\MobileReferralController::class, 'validateCode'])->name('api.v1.referral.validate');
});

/*
|--------------------------------------------------------------------------
| Push Notifications API (Requires Auth)
|--------------------------------------------------------------------------
*/
// Public endpoint for VAPID key
Route::get('/v1/push/public-key', [PushNotificationController::class, 'getPublicKey'])->name('api.v1.push.public-key');

Route::middleware('auth:sanctum')->group(function () {
    Route::post('/notifications/register-token', [\App\Http\Controllers\API\NotificationController::class, 'registerToken'])->name('api.notifications.register');
    Route::post('/notifications/unregister-token', [\App\Http\Controllers\API\NotificationController::class, 'unregisterToken'])->name('api.notifications.unregister');
    Route::get('/notifications/tokens', [\App\Http\Controllers\API\NotificationController::class, 'getTokens'])->name('api.notifications.tokens');
    Route::get('/notifications/preferences', [\App\Http\Controllers\API\NotificationController::class, 'getPreferences'])->name('api.notifications.preferences.get');
    Route::put('/notifications/preferences', [\App\Http\Controllers\API\NotificationController::class, 'updatePreferences'])->name('api.notifications.preferences.update');

    // Push Notification Routes
    Route::prefix('v1/push')->group(function () {
        Route::post('/subscribe', [PushNotificationController::class, 'subscribe'])->name('api.v1.push.subscribe');
        Route::post('/unsubscribe', [PushNotificationController::class, 'unsubscribe'])->name('api.v1.push.unsubscribe');
        Route::get('/subscriptions', [PushNotificationController::class, 'getSubscriptions'])->name('api.v1.push.subscriptions');
        Route::delete('/subscriptions/{subscription}', [PushNotificationController::class, 'revokeSubscription'])->name('api.v1.push.revoke');
        Route::post('/test', [PushNotificationController::class, 'sendTest'])->name('api.v1.push.test');
        Route::post('/preferences', [PushNotificationController::class, 'updatePreferences'])->name('api.v1.push.preferences');
    });
});

/*
|--------------------------------------------------------------------------
| External Integration API (API Key Authentication for n8n, Zapier, etc.)
|--------------------------------------------------------------------------
*/
Route::prefix('v1/external')->middleware('throttle:60,1')->group(function () {
    // Lead creation endpoint for automation tools (n8n, Zapier, Make)
    Route::post('/leads', [\App\Http\Controllers\API\V1\ExternalLeadController::class, 'store'])
        ->name('api.v1.external.leads.store');

    // Webhook for lead status updates
    Route::post('/leads/{lead}/status', [\App\Http\Controllers\API\V1\ExternalLeadController::class, 'updateStatus'])
        ->name('api.v1.external.leads.status');

    // Get available sites (for automation configuration)
    Route::get('/sites', [\App\Http\Controllers\API\V1\ExternalLeadController::class, 'getSites'])
        ->name('api.v1.external.sites');
});

Route::prefix('v1')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Authentication Routes (with rate limiting for security)
    |--------------------------------------------------------------------------
    */
    Route::middleware('throttle:login')->group(function () {
        Route::post('/auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');
        Route::post('/auth/register', [AuthController::class, 'register'])->name('api.v1.auth.register');
    });

    /*
    |--------------------------------------------------------------------------
    | Protected Routes (Require Authentication)
    |--------------------------------------------------------------------------
    */
    Route::middleware(['auth:sanctum', 'throttle:api'])->group(function () {
        // Auth
        Route::post('/auth/logout', [AuthController::class, 'logout'])->name('api.v1.auth.logout');
        Route::get('/auth/user', [AuthController::class, 'user'])->name('api.v1.auth.user');

        // Sites
        Route::apiResource('sites', SiteController::class, ['as' => 'api.v1']);

        // Boxes
        Route::apiResource('boxes', BoxController::class, ['as' => 'api.v1']);
        Route::get('sites/{site}/boxes', [BoxController::class, 'bySite'])->name('api.v1.sites.boxes');

        // Customers
        Route::apiResource('customers', CustomerController::class, ['as' => 'api.v1']);

        // Contracts
        Route::apiResource('contracts', ContractController::class, ['as' => 'api.v1']);
        Route::get('customers/{customer}/contracts', [ContractController::class, 'byCustomer'])->name('api.v1.customers.contracts');
        Route::get('contracts/{contract}/pdf', [ContractController::class, 'downloadPdf'])->name('api.v1.contracts.pdf');

        // Invoices
        Route::apiResource('invoices', InvoiceController::class, ['as' => 'api.v1']);
        Route::get('customers/{customer}/invoices', [InvoiceController::class, 'byCustomer'])->name('api.v1.customers.invoices');
        Route::get('contracts/{contract}/invoices', [InvoiceController::class, 'byContract'])->name('api.v1.contracts.invoices');
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('api.v1.invoices.pdf');

        // Payments
        Route::apiResource('payments', PaymentController::class, ['as' => 'api.v1']);
        Route::get('customers/{customer}/payments', [PaymentController::class, 'byCustomer'])->name('api.v1.customers.payments');
        Route::get('invoices/{invoice}/payments', [PaymentController::class, 'byInvoice'])->name('api.v1.invoices.payments');
    });
});
