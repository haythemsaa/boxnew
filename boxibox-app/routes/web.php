<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\SiteController;
use App\Http\Controllers\Tenant\BoxController;
use App\Http\Controllers\Tenant\CustomerController;
use App\Http\Controllers\Tenant\ContractController;
use App\Http\Controllers\Tenant\InvoiceController;
use App\Http\Controllers\Tenant\PaymentController;
use App\Http\Controllers\Portal\PortalDashboardController;
use App\Http\Controllers\Portal\PortalContractController;
use App\Http\Controllers\Portal\PortalInvoiceController;
use App\Http\Controllers\Portal\PortalPaymentController;
use App\Http\Controllers\Portal\PortalProfileController;
use App\Http\Controllers\Booking\BookingController;
use App\Http\Controllers\Tenant\AnalyticsController;
use App\Http\Controllers\Tenant\PricingController;
use App\Http\Controllers\Tenant\LeadController;
use App\Http\Controllers\Tenant\AccessControlController;
use App\Http\Controllers\Tenant\ProspectController;
use App\Http\Controllers\Tenant\SignatureController;
use App\Http\Controllers\Tenant\SepaMandateController;
use App\Http\Controllers\Tenant\ReminderController;
use App\Http\Controllers\Tenant\BulkInvoiceController;
use App\Http\Controllers\Signature\PublicSignatureController;
use App\Http\Controllers\BoxCalculatorController;
use App\Http\Controllers\Mobile\MobileController;
use App\Http\Controllers\Tenant\PlanController;
use App\Http\Controllers\Tenant\BookingManagementController;
use App\Http\Controllers\Public\BookingController as PublicBookingController;
use App\Http\Controllers\Api\BookingApiController;
use App\Http\Controllers\SuperAdmin\DashboardController as SuperAdminDashboardController;
use App\Http\Controllers\SuperAdmin\TenantController as SuperAdminTenantController;
use App\Http\Controllers\SuperAdmin\SubscriptionController as SuperAdminSubscriptionController;
use App\Http\Controllers\SuperAdmin\AnalyticsController as SuperAdminAnalyticsController;
use App\Http\Controllers\SuperAdmin\SettingsController as SuperAdminSettingsController;
use App\Http\Controllers\SuperAdmin\UserController as SuperAdminUserController;
use App\Http\Controllers\SuperAdmin\ActivityLogController as SuperAdminActivityLogController;
use App\Http\Controllers\SuperAdmin\AnnouncementController as SuperAdminAnnouncementController;
use App\Http\Controllers\SuperAdmin\PlatformBillingController as SuperAdminBillingController;
use App\Http\Controllers\SuperAdmin\EmailTemplateController as SuperAdminEmailTemplateController;
use App\Http\Controllers\SuperAdmin\BackupController as SuperAdminBackupController;
use App\Http\Controllers\SuperAdmin\FeatureFlagController as SuperAdminFeatureFlagController;
use App\Http\Controllers\SuperAdmin\ModuleController as SuperAdminModuleController;
use App\Http\Controllers\Tenant\MaintenanceController;
use App\Http\Controllers\Tenant\OverdueController;
use App\Http\Controllers\Tenant\ReportingController;
use App\Http\Controllers\Tenant\InspectionController;
use App\Http\Controllers\Tenant\LoyaltyController;
use App\Http\Controllers\Tenant\StaffController;
use App\Http\Controllers\Tenant\CalculatorController as TenantCalculatorController;
use App\Http\Controllers\Tenant\ReviewController;
use App\Http\Controllers\Tenant\GdprController;
use App\Http\Controllers\Tenant\VideoCallController;
use App\Http\Controllers\Public\HomeController as PublicHomeController;
use App\Http\Controllers\Public\BlogController as PublicBlogController;
use App\Http\Controllers\SuperAdmin\BlogController as SuperAdminBlogController;
use App\Http\Controllers\LocaleController;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

/*
|--------------------------------------------------------------------------
| Locale/Language Routes
|--------------------------------------------------------------------------
*/

Route::get('/lang/{locale}', [LocaleController::class, 'translations'])->name('lang.translations');
Route::post('/locale/{locale}', [LocaleController::class, 'change'])->name('locale.change');
Route::get('/locales', [LocaleController::class, 'available'])->name('locale.available');

/*
|--------------------------------------------------------------------------
| Public Marketing Website Routes
|--------------------------------------------------------------------------
*/

Route::get('/', [PublicHomeController::class, 'index'])->name('home');
Route::get('/features', [PublicHomeController::class, 'features'])->name('features');
Route::get('/about', [PublicHomeController::class, 'about'])->name('about');
Route::get('/contact', [PublicHomeController::class, 'contact'])->name('contact');
Route::post('/contact', [PublicHomeController::class, 'submitContact'])->name('contact.submit');
Route::get('/demo', [PublicHomeController::class, 'demo'])->name('demo');
Route::post('/demo', [PublicHomeController::class, 'submitDemo'])->name('demo.submit');
Route::get('/pricing', [PublicHomeController::class, 'pricing'])->name('pricing');
Route::get('/calculator', [PublicHomeController::class, 'calculator'])->name('calculator');
Route::get('/size-calculator', [PublicHomeController::class, 'sizeCalculator'])->name('size-calculator');

/*
|--------------------------------------------------------------------------
| Public Blog Routes (SEO Optimized)
|--------------------------------------------------------------------------
*/

Route::prefix('blog')->name('blog.')->group(function () {
    Route::get('/', [PublicBlogController::class, 'index'])->name('index');
    Route::get('/rss', [PublicBlogController::class, 'rss'])->name('rss');
    Route::get('/sitemap.xml', [PublicBlogController::class, 'sitemap'])->name('sitemap');
    Route::get('/category/{slug}', [PublicBlogController::class, 'category'])->name('category');
    Route::get('/tag/{slug}', [PublicBlogController::class, 'tag'])->name('tag');
    Route::get('/{slug}', [PublicBlogController::class, 'show'])->name('show');
    Route::post('/{post}/comment', [PublicBlogController::class, 'storeComment'])->name('comment.store');
});

/*
|--------------------------------------------------------------------------
| Public Signature Routes (No Auth Required)
|--------------------------------------------------------------------------
*/

Route::prefix('signature')->name('signature.')->group(function () {
    Route::get('/{token}', [PublicSignatureController::class, 'show'])->name('show');
    Route::post('/{token}/sign', [PublicSignatureController::class, 'sign'])->name('sign');
    Route::post('/{token}/refuse', [PublicSignatureController::class, 'refuse'])->name('refuse');
    Route::get('/{token}/confirmation', [PublicSignatureController::class, 'confirmation'])->name('confirmation');
    Route::get('/{token}/download-contract', [PublicSignatureController::class, 'downloadContract'])->name('download-contract');
});

/*
|--------------------------------------------------------------------------
| Box Size Calculator API (Public)
|--------------------------------------------------------------------------
*/

Route::prefix('size-calculator')->name('size-calculator.')->group(function () {
    Route::get('/', [BoxCalculatorController::class, 'index'])->name('index');
    Route::post('/calculate', [BoxCalculatorController::class, 'calculate'])->name('calculate');
});

// Home - Legacy redirect for authenticated users
Route::get('/app', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $user = Auth::user();

    // Redirect to SuperAdmin dashboard if user is a super admin
    if ($user->hasRole('super_admin')) {
        return redirect()->route('superadmin.dashboard');
    }

    // Redirect to portal if user is a client
    if ($user->isClient()) {
        return redirect()->route('portal.dashboard');
    }

    // Redirect to tenant dashboard for admins
    return redirect()->route('tenant.dashboard');
});

/*
|--------------------------------------------------------------------------
| Online Booking Routes (Public) - Legacy
|--------------------------------------------------------------------------
*/

Route::prefix('booking')->name('booking.')->group(function () {
    Route::get('/', [BookingController::class, 'index'])->name('index');
    Route::get('/box/{box}', [BookingController::class, 'show'])->name('show');
    Route::get('/box/{box}/checkout', [BookingController::class, 'checkout'])->name('checkout');
    Route::post('/box/{box}/reserve', [BookingController::class, 'store'])->name('store');
    Route::get('/confirmation/{contract}', [BookingController::class, 'confirmation'])->name('confirmation');
});

/*
|--------------------------------------------------------------------------
| NEW Public Booking System Routes (EasyWeek-style)
|--------------------------------------------------------------------------
*/

Route::prefix('book')->name('public.booking.')->group(function () {
    // Kiosk mode for unmanned facilities (fullscreen touch interface)
    Route::get('/{slug}/kiosk', [PublicBookingController::class, 'kiosk'])->name('kiosk');

    // Optimized one-page checkout (default for better conversion)
    Route::get('/{slug}/checkout', [PublicBookingController::class, 'checkout'])->name('checkout');

    // Main booking page by slug (multi-step version)
    Route::get('/{slug}', [PublicBookingController::class, 'show'])->name('show');

    // Fallback by tenant ID
    Route::get('/tenant/{tenantId}', [PublicBookingController::class, 'showByTenant'])->name('by-tenant');

    // API endpoints for booking form
    Route::get('/api/sites/{siteId}/boxes', [PublicBookingController::class, 'getAvailableBoxes'])->name('get-boxes');
    Route::post('/api/promo-code/validate', [PublicBookingController::class, 'validatePromoCode'])->name('validate-promo');
    Route::post('/api/check-availability', [PublicBookingController::class, 'checkAvailability'])->name('check-availability');
    Route::post('/api/create-payment-intent', [PublicBookingController::class, 'createPaymentIntent'])->name('create-payment-intent');
    Route::post('/api/bookings', [PublicBookingController::class, 'store'])->name('store');

    // Booking status page
    Route::get('/status/{uuid}', [PublicBookingController::class, 'status'])->name('status');
});

// Widget embedding endpoint
Route::get('/widget/booking/{widgetKey}', [PublicBookingController::class, 'widget'])->name('booking.widget');

/*
|--------------------------------------------------------------------------
| Booking API Routes (for external integrations)
|--------------------------------------------------------------------------
*/

Route::prefix('api/v1/booking')->name('api.booking.')->group(function () {
    Route::get('/sites', [BookingApiController::class, 'sites'])->name('sites');
    Route::get('/sites/{siteId}/boxes', [BookingApiController::class, 'boxes'])->name('boxes');
    Route::get('/boxes/{boxId}/availability', [BookingApiController::class, 'checkAvailability'])->name('check-availability');
    Route::post('/bookings', [BookingApiController::class, 'createBooking'])->name('create');
    Route::get('/bookings', [BookingApiController::class, 'listBookings'])->name('list');
    Route::get('/bookings/{uuid}', [BookingApiController::class, 'getBooking'])->name('get');
    Route::patch('/bookings/{uuid}/status', [BookingApiController::class, 'updateStatus'])->name('update-status');
    Route::post('/promo-codes/validate', [BookingApiController::class, 'validatePromoCode'])->name('validate-promo');
});

/*
|--------------------------------------------------------------------------
| IoT Webhook Routes (for smart locks and sensors)
|--------------------------------------------------------------------------
*/

Route::prefix('api/iot')->name('api.iot.')->group(function () {
    // Health check
    Route::get('/health', [\App\Http\Controllers\Api\IoTWebhookController::class, 'healthCheck'])->name('health');

    // Generic sensor data
    Route::post('/sensor-data', [\App\Http\Controllers\Api\IoTWebhookController::class, 'sensorData'])->name('sensor-data');
    Route::post('/sensor-data/batch', [\App\Http\Controllers\Api\IoTWebhookController::class, 'sensorDataBatch'])->name('sensor-data-batch');

    // Smart lock provider webhooks
    Route::post('/webhooks/noke', [\App\Http\Controllers\Api\IoTWebhookController::class, 'nokeWebhook'])->name('webhook.noke');
    Route::post('/webhooks/salto', [\App\Http\Controllers\Api\IoTWebhookController::class, 'saltoWebhook'])->name('webhook.salto');
    Route::post('/webhooks/kisi', [\App\Http\Controllers\Api\IoTWebhookController::class, 'kisiWebhook'])->name('webhook.kisi');
    Route::post('/webhooks/pti', [\App\Http\Controllers\Api\IoTWebhookController::class, 'ptiWebhook'])->name('webhook.pti');

    // Specialized sensor webhooks
    Route::post('/door-sensor', [\App\Http\Controllers\Api\IoTWebhookController::class, 'doorSensorWebhook'])->name('door-sensor');
    Route::post('/climate-sensor', [\App\Http\Controllers\Api\IoTWebhookController::class, 'climateSensorWebhook'])->name('climate-sensor');
});

/*
|--------------------------------------------------------------------------
| Guest Routes (Authentication)
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    // Login
    Route::get('/login', function () {
        return Inertia::render('Auth/Login');
    })->name('login');

    Route::post('/login', function (Request $request) {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (Auth::attempt($credentials, $request->boolean('remember'))) {
            $request->session()->regenerate();

            $user = Auth::user();

            // Redirect directly to appropriate dashboard based on role
            if ($user->isSuperAdmin()) {
                return redirect()->route('superadmin.dashboard');
            }

            if ($user->isClient()) {
                return redirect()->route('portal.dashboard');
            }

            // Tenant admin or other tenant users
            return redirect()->route('tenant.dashboard');
        }

        return back()->withErrors([
            'failed' => 'The provided credentials do not match our records.',
        ])->onlyInput('email');
    })->name('login.post');

    // Register
    Route::get('/register', function () {
        return Inertia::render('Auth/Register');
    })->name('register');

    Route::post('/register', function (Request $request) {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = \App\Models\User::create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'password' => \Illuminate\Support\Facades\Hash::make($validated['password']),
            'status' => 'active',
        ]);

        // Assign default client role
        $user->assignRole('client');

        Auth::login($user);

        return redirect()->route('tenant.dashboard');
    })->name('register.post');

    // Forgot Password
    Route::get('/forgot-password', function () {
        return Inertia::render('Auth/ForgotPassword');
    })->name('password.request');

    Route::post('/forgot-password', function (Request $request) {
        $request->validate(['email' => 'required|email']);

        // TODO: Implement password reset email logic
        return back()->with('status', 'Password reset link sent to your email!');
    })->name('password.email');
});

/*
|--------------------------------------------------------------------------
| Authenticated Routes
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {
    // Logout
    Route::post('/logout', function (Request $request) {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login');
    })->name('logout');

    /*
    |--------------------------------------------------------------------------
    | Tenant Routes (Protected)
    |--------------------------------------------------------------------------
    */
    Route::prefix('tenant')->name('tenant.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

        // Sites (Resource Controller)
        Route::resource('sites', SiteController::class);

        // Boxes (Resource Controller)
        Route::get('boxes/plan', [BoxController::class, 'plan'])->name('boxes.plan');
        Route::get('boxes/plan/edit', [BoxController::class, 'planEdit'])->name('boxes.plan.edit');
        Route::post('boxes/plan/save', [BoxController::class, 'planSave'])->name('boxes.plan.save');
        Route::resource('boxes', BoxController::class);

        // Customers (Resource Controller)
        Route::get('customers/export/excel', [CustomerController::class, 'export'])->name('customers.export');
        Route::resource('customers', CustomerController::class);

        // Contracts (Resource Controller)
        Route::get('contracts/export/excel', [ContractController::class, 'export'])->name('contracts.export');
        Route::get('contracts/{contract}/pdf', [ContractController::class, 'downloadPdf'])->name('contracts.pdf');
        Route::get('contracts/{contract}/sign', [ContractController::class, 'sign'])->name('contracts.sign');
        Route::post('contracts/{contract}/sign', [ContractController::class, 'saveSignatures'])->name('contracts.save-signatures');
        Route::post('contracts/{contract}/terminate', [ContractController::class, 'terminate'])->name('contracts.terminate');
        Route::get('contracts/{contract}/renewal-options', [ContractController::class, 'renewalOptions'])->name('contracts.renewal-options');
        Route::post('contracts/{contract}/renew', [ContractController::class, 'renew'])->name('contracts.renew');
        Route::get('contracts/create/wizard', [ContractController::class, 'createWizard'])->name('contracts.create-wizard');
        Route::resource('contracts', ContractController::class);

        // Invoices (Resource Controller)
        Route::post('invoices/generate', [InvoiceController::class, 'generateInvoices'])->name('invoices.generate');
        Route::get('invoices/overdue/list', [InvoiceController::class, 'overdueInvoices'])->name('invoices.overdue');
        Route::post('invoices/{invoice}/payment', [InvoiceController::class, 'recordPayment'])->name('invoices.record-payment');
        Route::post('invoices/{invoice}/send', [InvoiceController::class, 'sendInvoice'])->name('invoices.send');
        Route::post('invoices/{invoice}/reminder', [InvoiceController::class, 'sendReminder'])->name('invoices.reminder');
        Route::get('invoices/export/excel', [InvoiceController::class, 'export'])->name('invoices.export');
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');
        Route::resource('invoices', InvoiceController::class);

        // Payments (Resource Controller)
        Route::resource('payments', PaymentController::class);

        // Prospects (Resource Controller)
        Route::resource('prospects', ProspectController::class);
        Route::post('prospects/{prospect}/convert', [ProspectController::class, 'convert'])->name('prospects.convert');
        Route::post('prospects/{prospect}/record-contact', [ProspectController::class, 'recordContact'])->name('prospects.record-contact');
        Route::post('prospects/{prospect}/mark-lost', [ProspectController::class, 'markAsLost'])->name('prospects.mark-lost');
        Route::post('prospects/{prospect}/send-sms', [ProspectController::class, 'sendSms'])->name('prospects.send-sms');

        // Signatures (Resource Controller)
        Route::resource('signatures', SignatureController::class)->except(['edit', 'update']);
        Route::post('signatures/{signature}/send', [SignatureController::class, 'send'])->name('signatures.send');
        Route::post('signatures/{signature}/remind', [SignatureController::class, 'remind'])->name('signatures.remind');
        Route::post('signatures/{signature}/cancel', [SignatureController::class, 'cancel'])->name('signatures.cancel');
        Route::get('signatures/{signature}/download-signed', [SignatureController::class, 'downloadSigned'])->name('signatures.download-signed');
        Route::get('signatures/{signature}/download-proof', [SignatureController::class, 'downloadProof'])->name('signatures.download-proof');

        // SEPA Mandates (Resource Controller)
        Route::resource('sepa-mandates', SepaMandateController::class)->except(['edit', 'update']);
        Route::post('sepa-mandates/{sepaMandate}/activate', [SepaMandateController::class, 'activate'])->name('sepa-mandates.activate');
        Route::post('sepa-mandates/{sepaMandate}/suspend', [SepaMandateController::class, 'suspend'])->name('sepa-mandates.suspend');
        Route::post('sepa-mandates/{sepaMandate}/reactivate', [SepaMandateController::class, 'reactivate'])->name('sepa-mandates.reactivate');
        Route::post('sepa-mandates/{sepaMandate}/cancel', [SepaMandateController::class, 'cancel'])->name('sepa-mandates.cancel');
        Route::get('sepa-mandates/{sepaMandate}/download', [SepaMandateController::class, 'download'])->name('sepa-mandates.download');

        // Reminders (Resource Controller)
        Route::resource('reminders', ReminderController::class)->except(['edit', 'update']);
        Route::post('reminders/{reminder}/send', [ReminderController::class, 'send'])->name('reminders.send');
        Route::post('reminders/send-bulk', [ReminderController::class, 'sendBulk'])->name('reminders.send-bulk');
        Route::get('reminders/overdue-invoices', [ReminderController::class, 'overdueInvoices'])->name('reminders.overdue-invoices');

        // Bulk Invoicing
        Route::prefix('bulk-invoicing')->name('bulk-invoicing.')->controller(BulkInvoiceController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/preview', 'preview')->name('preview');
            Route::post('/generate', 'generate')->name('generate');
        });

        // Analytics Routes
        Route::prefix('analytics')->name('analytics.')->group(function () {
            Route::get('/occupancy', [AnalyticsController::class, 'occupancy'])->name('occupancy');
            Route::get('/revenue', [AnalyticsController::class, 'revenue'])->name('revenue');
            Route::get('/marketing', [AnalyticsController::class, 'marketing'])->name('marketing');
            Route::get('/operations', [AnalyticsController::class, 'operations'])->name('operations');
            Route::post('/export', [AnalyticsController::class, 'export'])->name('export');
        });

        // AI Business Advisor Routes (Classic)
        Route::prefix('ai-advisor')->name('ai-advisor.')->controller(\App\Http\Controllers\Tenant\AIAdvisorController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/get', 'getAdvice')->name('get');
            Route::post('/refresh', 'refresh')->name('refresh');
            Route::get('/category/{category}', 'getCategory')->name('category');
            Route::post('/dismiss', 'dismissRecommendation')->name('dismiss');
            Route::get('/quick-metrics', 'getQuickMetrics')->name('quick-metrics');
        });

        // AI Copilot Routes (NEW - ChatGPT-style Interface)
        Route::prefix('copilot')->name('copilot.')->controller(\App\Http\Controllers\Tenant\CopilotController::class)->group(function () {
            // Main Interface
            Route::get('/', 'index')->name('index');

            // Chat API
            Route::post('/chat', 'chat')->name('chat');
            Route::post('/action', 'executeAction')->name('action');

            // Data APIs
            Route::get('/briefing', 'briefing')->name('briefing');
            Route::get('/forecast', 'forecast')->name('forecast');
            Route::get('/churn-risk', 'churnRisk')->name('churn-risk');
            Route::get('/pricing-recommendations', 'pricingRecommendations')->name('pricing-recommendations');

            // Agents Management
            Route::get('/agents/status', 'agentStatus')->name('agents.status');
            Route::post('/agents/run', 'runAgents')->name('agents.run');
        });

        // Dynamic Pricing Routes
        Route::prefix('pricing')->name('pricing.')->group(function () {
            Route::get('/dashboard', [PricingController::class, 'dashboard'])->name('dashboard');
            Route::post('/calculate/{box}', [PricingController::class, 'calculateOptimalPrice'])->name('calculate');
            Route::post('/apply-recommendation', [PricingController::class, 'applyRecommendation'])->name('apply-recommendation');
            Route::get('/strategies', [PricingController::class, 'strategies'])->name('strategies.index');
            Route::post('/strategies', [PricingController::class, 'storeStrategy'])->name('strategies.store');
        });

        // CRM / Leads Routes
        Route::prefix('crm')->name('crm.')->group(function () {
            Route::resource('leads', LeadController::class);
            Route::post('/leads/{lead}/convert', [LeadController::class, 'convertToCustomer'])->name('leads.convert');
            Route::get('/churn-risk', [LeadController::class, 'churnRisk'])->name('churn-risk');
        });

        // Access Control Routes
        Route::prefix('access-control')->name('access-control.')->group(function () {
            Route::get('/dashboard', [AccessControlController::class, 'dashboard'])->name('dashboard');
            Route::get('/locks', [AccessControlController::class, 'locks'])->name('locks.index');
            Route::put('/locks/{lock}', [AccessControlController::class, 'updateLock'])->name('locks.update');
            Route::get('/logs', [AccessControlController::class, 'logs'])->name('logs.index');
        });

        // IoT & Smart Entry Routes
        Route::prefix('iot')->name('iot.')->controller(\App\Http\Controllers\Tenant\IoTController::class)->group(function () {
            // Main Dashboard
            Route::get('/', 'dashboard')->name('dashboard');
            Route::get('/api/dashboard/{siteId}', 'dashboardApi')->name('api.dashboard');

            // Smart Locks
            Route::post('/locks/{lock}/unlock', 'unlockLock')->name('locks.unlock');
            Route::post('/locks/{lock}/lock', 'lockLock')->name('locks.lock');

            // Alerts
            Route::get('/alerts', 'alerts')->name('alerts');
            Route::post('/alerts/{alert}/acknowledge', 'acknowledgeAlert')->name('alerts.acknowledge');
            Route::post('/alerts/{alert}/resolve', 'resolveAlert')->name('alerts.resolve');

            // Sensors
            Route::get('/sensors/{sensor}', 'sensorDetails')->name('sensors.show');
            Route::put('/sensors/{sensor}', 'updateSensor')->name('sensors.update');

            // Access Logs
            Route::get('/access-logs', 'accessLogs')->name('access-logs');

            // Insurance Reports
            Route::get('/reports', 'reports')->name('reports');
            Route::post('/reports/generate', 'generateReport')->name('reports.generate');
        });

        // Notifications Routes
        Route::prefix('notifications')->name('notifications.')->controller(\App\Http\Controllers\Tenant\NotificationController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/preferences', 'updatePreferences')->name('preferences.update');
            Route::get('/email-settings', 'emailSettings')->name('email-settings');
            Route::post('/email-settings', 'updateEmailSettings')->name('email-settings.update');
            Route::get('/sms-settings', 'smsSettings')->name('sms-settings');
            Route::post('/sms-settings', 'updateSmsSettings')->name('sms-settings.update');
            Route::get('/push-settings', 'pushSettings')->name('push-settings');
            Route::post('/push-settings', 'updatePushSettings')->name('push-settings.update');
            Route::post('/test', 'sendTest')->name('test');
            Route::get('/logs', 'logs')->name('logs');

            // API endpoints for NotificationCenter component
            Route::get('/api/list', 'apiList')->name('api.list');
            Route::post('/api/{id}/read', 'markAsRead')->name('api.read');
            Route::post('/api/read-all', 'markAllAsRead')->name('api.read-all');
            Route::get('/api/unread-count', 'unreadCount')->name('api.unread-count');
            Route::delete('/api/{id}', 'deleteNotification')->name('api.delete');
        });

        // FEC Export Routes (French Accounting)
        Route::prefix('fec')->name('fec.')->controller(\App\Http\Controllers\Tenant\FecController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/generate', 'generate')->name('generate');
            Route::get('/{fecExport}/download', 'download')->name('download');
            Route::get('/{fecExport}/validate', 'validateExport')->name('validate');
            Route::delete('/{fecExport}', 'destroy')->name('destroy');
        });

        // Predictive Analytics Routes (ML/AI)
        Route::prefix('analytics/predictive')->name('analytics.predictive.')->controller(\App\Http\Controllers\Tenant\PredictiveController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/occupation-forecast', 'occupationForecast')->name('occupation-forecast');
            Route::get('/churn-predictions', 'churnPredictions')->name('churn-predictions');
            Route::get('/upsell-opportunities', 'upsellOpportunities')->name('upsell-opportunities');
            Route::post('/boxes/{box}/optimize-pricing', 'optimizePricing')->name('optimize-pricing');
        });

        // Integrations / Webhooks Routes
        Route::prefix('integrations')->name('integrations.')->controller(\App\Http\Controllers\Tenant\WebhookController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            // Webhooks
            Route::post('/webhooks', 'store')->name('webhooks.store');
            Route::put('/webhooks/{webhook}', 'update')->name('webhooks.update');
            Route::delete('/webhooks/{webhook}', 'destroy')->name('webhooks.destroy');
            Route::post('/webhooks/{webhook}/test', 'test')->name('webhooks.test');
            Route::post('/webhooks/{webhook}/regenerate-secret', 'regenerateSecret')->name('webhooks.regenerate-secret');
            Route::get('/webhooks/{webhook}/deliveries', 'deliveries')->name('webhooks.deliveries');
            // API Keys
            Route::post('/api-keys', 'storeApiKey')->name('api-keys.store');
            Route::put('/api-keys/{apiKey}', 'updateApiKey')->name('api-keys.update');
            Route::delete('/api-keys/{apiKey}', 'destroyApiKey')->name('api-keys.destroy');
            Route::post('/api-keys/{apiKey}/regenerate-secret', 'regenerateApiKeySecret')->name('api-keys.regenerate-secret');
        });

        // SMS Campaigns Routes (inside CRM)
        Route::prefix('crm/campaigns')->name('crm.campaigns.')->controller(\App\Http\Controllers\Tenant\CampaignController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{campaign}', 'show')->name('show');
            Route::post('/{campaign}/send', 'send')->name('send');
            Route::delete('/{campaign}', 'destroy')->name('destroy');
        });

        // Messages
        Route::prefix('messages')->name('messages.')->controller(\App\Http\Controllers\Tenant\MessageController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{id}', 'show')->name('show');
            Route::post('/send-bulk', 'sendBulk')->name('send-bulk');
        });

        // Settings
        Route::prefix('settings')->name('settings.')->controller(\App\Http\Controllers\Tenant\SettingsController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::post('/general', 'updateGeneral')->name('update-general');
            Route::post('/billing', 'updateBilling')->name('update-billing');
            Route::post('/notifications', 'updateNotifications')->name('update-notifications');
            Route::post('/sepa', 'updateSepa')->name('update-sepa');
        });

        // Plan Routes (Visual Box Layout)
        Route::prefix('plan')->name('plan.')->group(function () {
            Route::get('/', [PlanController::class, 'index'])->name('index');
            Route::get('/interactive', [PlanController::class, 'interactive'])->name('interactive');
            Route::get('/templates', [PlanController::class, 'templates'])->name('templates');
            Route::post('/create', [PlanController::class, 'create'])->name('create');
            Route::get('/editor', [PlanController::class, 'editor'])->name('editor');
            Route::post('/sites/{site}/elements', [PlanController::class, 'saveElements'])->name('save-elements');
            Route::post('/sites/{site}/configuration', [PlanController::class, 'saveConfiguration'])->name('save-configuration');
            Route::post('/sites/{site}/auto-generate', [PlanController::class, 'autoGenerate'])->name('auto-generate');
            Route::post('/sites/{site}/generate-buxida', [PlanController::class, 'generateBuxidaLayout'])->name('generate-buxida');
            Route::get('/boxes/{box}/details', [PlanController::class, 'getBoxDetails'])->name('box-details');
            Route::get('/floors/{site}', [PlanController::class, 'getFloors'])->name('get-floors');
        });

        // Maintenance Module Routes
        Route::prefix('maintenance')->name('maintenance.')->controller(MaintenanceController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{maintenance}', 'show')->name('show');
            Route::get('/{maintenance}/edit', 'edit')->name('edit');
            Route::put('/{maintenance}', 'update')->name('update');
            Route::delete('/{maintenance}', 'destroy')->name('destroy');
            Route::post('/{maintenance}/comment', 'addComment')->name('add-comment');
            Route::post('/{maintenance}/status', 'updateStatus')->name('update-status');
            // Categories
            Route::get('/config/categories', 'categories')->name('categories');
            Route::post('/config/categories', 'storeCategory')->name('categories.store');
            // Vendors
            Route::get('/config/vendors', 'vendors')->name('vendors');
            Route::post('/config/vendors', 'storeVendor')->name('vendors.store');
        });

        // Overdue Management Routes
        Route::prefix('overdue')->name('overdue.')->controller(OverdueController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/workflows', 'workflows')->name('workflows');
            Route::get('/workflows/create', 'createWorkflow')->name('workflows.create');
            Route::post('/workflows', 'storeWorkflow')->name('workflows.store');
            Route::get('/workflows/{workflow}/edit', 'editWorkflow')->name('workflows.edit');
            Route::put('/workflows/{workflow}', 'updateWorkflow')->name('workflows.update');
            Route::delete('/workflows/{workflow}', 'destroyWorkflow')->name('workflows.destroy');
            Route::get('/actions', 'actions')->name('actions');
            Route::post('/actions/{action}/execute', 'executeAction')->name('actions.execute');
            Route::post('/invoices/{invoice}/reminder', 'sendReminder')->name('send-reminder');
            Route::get('/aging-report', 'agingReport')->name('aging-report');
        });

        // Advanced Reporting Routes
        Route::prefix('reports')->name('reports.')->controller(ReportingController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{report}', 'show')->name('show');
            Route::get('/{report}/edit', 'edit')->name('edit');
            Route::put('/{report}', 'update')->name('update');
            Route::delete('/{report}', 'destroy')->name('destroy');
            Route::get('/{report}/export', 'export')->name('export');
            // Pre-built reports
            Route::get('/pre/rent-roll', 'rentRoll')->name('rent-roll');
            Route::get('/pre/occupancy', 'occupancy')->name('occupancy');
            Route::get('/pre/revenue', 'revenue')->name('revenue');
            Route::get('/pre/cash-flow', 'cashFlow')->name('cash-flow');
        });

        // Inspections & Patrols Routes
        Route::prefix('inspections')->name('inspections.')->controller(InspectionController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/list', 'index')->name('list'); // Alias
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{inspection}', 'show')->name('show');
            Route::post('/{inspection}/start', 'start')->name('start');
            Route::post('/{inspection}/complete', 'complete')->name('complete');
            Route::post('/{inspection}/issue', 'addIssue')->name('add-issue');
            Route::post('/issues/{issue}/resolve', 'resolveIssue')->name('resolve-issue');
            // Schedules
            Route::get('/config/schedules', 'schedules')->name('schedules');
            Route::post('/config/schedules', 'storeSchedule')->name('schedules.store');
            // Checkpoints
            Route::get('/config/checkpoints', 'checkpoints')->name('checkpoints'); // Alias
            // Patrols
            Route::get('/patrols/list', 'patrols')->name('patrols');
            Route::get('/patrols/create', 'createPatrol')->name('patrols.create'); // Alias
            Route::post('/patrols/start', 'startPatrol')->name('patrol.start');
            Route::get('/patrols/{patrol}', 'showPatrol')->name('patrol.show');
            Route::post('/patrols/{patrol}/complete', 'completePatrol')->name('patrol.complete');
            Route::post('/checkpoints/{checkpoint}/scan', 'scanCheckpoint')->name('checkpoint.scan');
        });

        // Loyalty Program Routes
        Route::prefix('loyalty')->name('loyalty.')->controller(LoyaltyController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'updateSettings')->name('settings.update');
            Route::post('/settings/store', 'updateSettings')->name('settings.store'); // Alias
            Route::get('/members', 'members')->name('members');
            Route::get('/members/{loyaltyPoints}', 'showMember')->name('members.show');
            Route::post('/members/{loyaltyPoints}/adjust', 'adjustPoints')->name('members.adjust');
            Route::get('/rewards', 'rewards')->name('rewards');
            Route::post('/rewards', 'storeReward')->name('rewards.store');
            Route::put('/rewards/{reward}', 'updateReward')->name('rewards.update');
            Route::delete('/rewards/{reward}', 'destroyReward')->name('rewards.destroy');
            Route::get('/redemptions', 'redemptions')->name('redemptions');
            Route::post('/redemptions/{redemption}/process', 'processRedemption')->name('redemptions.process');
        });

        // Staff Management Routes
        Route::prefix('staff')->name('staff.')->controller(StaffController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/create', 'create')->name('create');
            Route::post('/', 'store')->name('store');
            Route::get('/{staff}', 'show')->name('show');
            Route::get('/{staff}/edit', 'edit')->name('edit');
            Route::put('/{staff}', 'update')->name('update');
            // Schedule
            Route::get('/planning/schedule', 'schedule')->name('schedule');
            Route::get('/planning/shifts', 'schedule')->name('shifts'); // Alias
            Route::post('/planning/shifts', 'storeShift')->name('shifts.store');
            Route::put('/planning/shifts/{shift}', 'updateShift')->name('shifts.update');
            Route::delete('/planning/shifts/{shift}', 'deleteShift')->name('shifts.delete');
            // Tasks
            Route::get('/work/tasks', 'tasks')->name('tasks');
            Route::post('/work/tasks', 'storeTask')->name('tasks.store');
            Route::post('/work/tasks/{task}/status', 'updateTaskStatus')->name('tasks.update-status');
            // Performance
            Route::get('/eval/performance', 'performance')->name('performance');
            Route::post('/eval/performance/{staff}', 'generatePerformanceReport')->name('performance.generate');
        });

        // Storage Calculator Management Routes
        Route::prefix('calculator')->name('calculator.')->controller(TenantCalculatorController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/categories', 'categories')->name('categories');
            Route::post('/categories', 'storeCategory')->name('categories.store');
            Route::put('/categories/{category}', 'updateCategory')->name('categories.update');
            Route::delete('/categories/{category}', 'destroyCategory')->name('categories.destroy');
            Route::post('/items', 'storeItem')->name('items.store');
            Route::put('/items/{item}', 'updateItem')->name('items.update');
            Route::delete('/items/{item}', 'destroyItem')->name('items.destroy');
            Route::get('/widgets', 'widgets')->name('widgets');
            Route::post('/widgets', 'storeWidget')->name('widgets.store');
            Route::put('/widgets/{widget}', 'updateWidget')->name('widgets.update');
            Route::delete('/widgets/{widget}', 'destroyWidget')->name('widgets.destroy');
            Route::get('/sessions', 'sessions')->name('sessions');
            Route::get('/sessions/{session}', 'sessionDetails')->name('sessions.show');
        });

        // Reviews & Reputation Routes
        Route::prefix('reviews')->name('reviews.')->controller(ReviewController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/settings', 'settings')->name('settings'); // Alias
            Route::get('/{review}', 'show')->name('show');
            Route::post('/{review}/moderate', 'moderate')->name('moderate');
            Route::post('/{review}/respond', 'respond')->name('respond');
            Route::get('/manage/requests', 'requests')->name('requests');
            Route::get('/manage/request', 'requests')->name('request'); // Alias
            Route::post('/manage/requests', 'sendRequest')->name('requests.send');
            Route::post('/manage/requests/bulk', 'bulkSendRequests')->name('requests.bulk');
            Route::post('/manage/requests/{reviewRequest}/resend', 'resendRequest')->name('requests.resend');
            Route::get('/analytics/nps', 'npsReport')->name('nps');
        });

        // GDPR & Data Protection Routes
        Route::prefix('gdpr')->name('gdpr.')->controller(GdprController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/requests', 'requests')->name('requests');
            Route::get('/requests/create', 'createRequest')->name('requests.create');
            Route::post('/requests', 'storeRequest')->name('requests.store');
            Route::get('/requests/{gdprRequest}', 'showRequest')->name('requests.show');
            Route::post('/requests/{gdprRequest}/process', 'processRequest')->name('requests.process');
            Route::get('/customers/{customer}/export', 'exportCustomerData')->name('export-data');
            Route::get('/consents', 'consents')->name('consents');
            Route::post('/consents', 'recordConsent')->name('consents.record');
            Route::post('/consents/{consent}/withdraw', 'withdrawConsent')->name('consents.withdraw');
            Route::get('/audit-log', 'auditLog')->name('audit-log');
        });

        // Booking Management Routes
        Route::prefix('bookings')->name('bookings.')->controller(BookingManagementController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/{booking}', 'show')->name('show');
            Route::post('/{booking}/confirm', 'confirm')->name('confirm');
            Route::post('/{booking}/reject', 'reject')->name('reject');
            Route::post('/{booking}/cancel', 'cancel')->name('cancel');
            Route::post('/{booking}/convert', 'convertToContract')->name('convert');

            // Settings
            Route::get('/settings/index', 'settings')->name('settings');
            Route::post('/settings/update', 'updateSettings')->name('settings.update');

            // Widgets
            Route::get('/widgets/index', 'widgets')->name('widgets');
            Route::post('/widgets/create', 'createWidget')->name('widgets.create');
            Route::put('/widgets/{widget}', 'updateWidget')->name('widgets.update');
            Route::delete('/widgets/{widget}', 'deleteWidget')->name('widgets.delete');

            // API Keys
            Route::get('/api-keys/index', 'apiKeys')->name('api-keys');
            Route::post('/api-keys/create', 'createApiKey')->name('api-keys.create');
            Route::put('/api-keys/{apiKey}', 'updateApiKey')->name('api-keys.update');
            Route::delete('/api-keys/{apiKey}', 'deleteApiKey')->name('api-keys.delete');
            Route::post('/api-keys/{apiKey}/regenerate', 'regenerateApiKey')->name('api-keys.regenerate');

            // Promo Codes
            Route::get('/promo-codes/index', 'promoCodes')->name('promo-codes');
            Route::post('/promo-codes/create', 'createPromoCode')->name('promo-codes.create');
            Route::put('/promo-codes/{promoCode}', 'updatePromoCode')->name('promo-codes.update');
            Route::delete('/promo-codes/{promoCode}', 'deletePromoCode')->name('promo-codes.delete');
        });

        // Sustainability Dashboard Routes
        Route::prefix('sustainability')->name('sustainability.')->controller(\App\Http\Controllers\Tenant\SustainabilityController::class)->group(function () {
            // Dashboard
            Route::get('/', 'index')->name('index');
            Route::get('/report', 'report')->name('report');

            // Energy Readings
            Route::get('/energy', 'energy')->name('energy');
            Route::post('/energy', 'storeEnergy')->name('energy.store');
            Route::put('/energy/{reading}', 'updateEnergy')->name('energy.update');
            Route::delete('/energy/{reading}', 'destroyEnergy')->name('energy.destroy');

            // Waste Records
            Route::get('/waste', 'waste')->name('waste');
            Route::post('/waste', 'storeWaste')->name('waste.store');
            Route::put('/waste/{record}', 'updateWaste')->name('waste.update');
            Route::delete('/waste/{record}', 'destroyWaste')->name('waste.destroy');

            // Initiatives
            Route::get('/initiatives', 'initiatives')->name('initiatives');
            Route::post('/initiatives', 'storeInitiative')->name('initiatives.store');
            Route::put('/initiatives/{initiative}', 'updateInitiative')->name('initiatives.update');
            Route::delete('/initiatives/{initiative}', 'destroyInitiative')->name('initiatives.destroy');

            // Goals
            Route::get('/goals', 'goals')->name('goals');
            Route::post('/goals', 'storeGoal')->name('goals.store');
            Route::put('/goals/{goal}', 'updateGoal')->name('goals.update');
            Route::delete('/goals/{goal}', 'destroyGoal')->name('goals.destroy');

            // Certifications
            Route::get('/certifications', 'certifications')->name('certifications');
            Route::post('/certifications', 'storeCertification')->name('certifications.store');
            Route::put('/certifications/{certification}', 'updateCertification')->name('certifications.update');
            Route::delete('/certifications/{certification}', 'destroyCertification')->name('certifications.destroy');
        });

        // Video Calls / Live Agent Routes
        Route::prefix('video-calls')->name('video-calls.')->controller(VideoCallController::class)->group(function () {
            // Dashboard
            Route::get('/', 'index')->name('index');
            Route::get('/schedule', 'schedule')->name('schedule');
            Route::post('/', 'store')->name('store');
            Route::get('/history', 'history')->name('history');

            // Call Management
            Route::get('/{videoCall}', 'show')->name('show');
            Route::get('/{videoCall}/agent-room', 'agentRoom')->name('agent-room');
            Route::post('/{videoCall}/start', 'startCall')->name('start');
            Route::post('/{videoCall}/end', 'endCall')->name('end');
            Route::post('/{videoCall}/cancel', 'cancel')->name('cancel');

            // Chat
            Route::post('/{videoCall}/messages', 'sendMessage')->name('send-message');
            Route::get('/{videoCall}/messages', 'getMessages')->name('get-messages');

            // Agent Status
            Route::post('/agent/status', 'updateAgentStatus')->name('agent-status');
            Route::post('/agent/ping', 'pingAgent')->name('agent-ping');

            // Settings
            Route::get('/config/settings', 'settings')->name('settings');
            Route::post('/config/settings', 'updateSettings')->name('settings.update');

            // Instant Call (walk-in)
            Route::post('/instant', 'createInstant')->name('instant');
        });

        // Valet Storage Routes
        Route::prefix('valet')->name('valet.')->controller(\App\Http\Controllers\Tenant\ValetStorageController::class)->group(function () {
            // Dashboard
            Route::get('/', 'index')->name('index');

            // Items
            Route::get('/items', 'items')->name('items');
            Route::get('/items/create', 'createItem')->name('items.create');
            Route::post('/items', 'storeItem')->name('items.store');
            Route::get('/items/{valetItem}', 'showItem')->name('items.show');
            Route::get('/items/{valetItem}/edit', 'editItem')->name('items.edit');
            Route::put('/items/{valetItem}', 'updateItem')->name('items.update');
            Route::delete('/items/{valetItem}', 'destroyItem')->name('items.destroy');

            // Orders
            Route::get('/orders', 'orders')->name('orders');
            Route::get('/orders/create', 'createOrder')->name('orders.create');
            Route::post('/orders', 'storeOrder')->name('orders.store');
            Route::get('/orders/{valetOrder}', 'showOrder')->name('orders.show');
            Route::put('/orders/{valetOrder}/status', 'updateOrderStatus')->name('orders.update-status');
            Route::put('/orders/{valetOrder}/assign', 'assignDriver')->name('orders.assign-driver');
            Route::post('/orders/{valetOrder}/cancel', 'cancelOrder')->name('orders.cancel');

            // Drivers
            Route::get('/drivers', 'drivers')->name('drivers');
            Route::get('/drivers/create', 'createDriver')->name('drivers.create');
            Route::post('/drivers', 'storeDriver')->name('drivers.store');
            Route::put('/drivers/{valetDriver}/status', 'updateDriverStatus')->name('drivers.update-status');
            Route::post('/drivers/{valetDriver}/location', 'updateDriverLocation')->name('drivers.location');

            // Planning / Routes
            Route::get('/planning', 'planning')->name('planning');
            Route::post('/routes', 'createRoute')->name('routes.store');

            // Zones & Pricing
            Route::get('/zones', 'zones')->name('zones');
            Route::post('/zones', 'storeZone')->name('zones.store');
            Route::put('/zones/{valetZone}', 'updateZone')->name('zones.update');
            Route::delete('/zones/{valetZone}', 'destroyZone')->name('zones.destroy');

            Route::get('/pricing', 'pricing')->name('pricing');
            Route::post('/pricing', 'storePricing')->name('pricing.store');
            Route::put('/pricing/{valetPricing}', 'updatePricing')->name('pricing.update');
            Route::delete('/pricing/{valetPricing}', 'destroyPricing')->name('pricing.destroy');

            // Settings
            Route::get('/settings', 'settings')->name('settings');
            Route::post('/settings', 'updateSettings')->name('settings.update');

            // Customer Inventory
            Route::get('/customer/{customer}/inventory', 'customerInventory')->name('customer.inventory');
        });
    });

    /*
    |--------------------------------------------------------------------------
    | Customer Portal Routes (Protected)
    |--------------------------------------------------------------------------
    */
    Route::prefix('portal')->name('portal.')->group(function () {
        // Dashboard
        Route::get('/dashboard', [PortalDashboardController::class, 'index'])->name('dashboard');

        // Contracts
        Route::get('/contracts', [PortalContractController::class, 'index'])->name('contracts.index');
        Route::get('/contracts/{contract}', [PortalContractController::class, 'show'])->name('contracts.show');
        Route::get('/contracts/{contract}/pdf', [PortalContractController::class, 'downloadPdf'])->name('contracts.pdf');

        // Invoices
        Route::get('/invoices', [PortalInvoiceController::class, 'index'])->name('invoices.index');
        Route::get('/invoices/{invoice}', [PortalInvoiceController::class, 'show'])->name('invoices.show');
        Route::get('/invoices/{invoice}/pdf', [PortalInvoiceController::class, 'downloadPdf'])->name('invoices.pdf');

        // Payments
        Route::get('/payments', [PortalPaymentController::class, 'index'])->name('payments.index');
        Route::get('/payments/{payment}', [PortalPaymentController::class, 'show'])->name('payments.show');

        // Profile
        Route::get('/profile', [PortalProfileController::class, 'edit'])->name('profile.edit');
        Route::put('/profile', [PortalProfileController::class, 'update'])->name('profile.update');
    });

    /*
    |--------------------------------------------------------------------------
    | Mobile Application Routes (PWA)
    |--------------------------------------------------------------------------
    */
    Route::prefix('mobile')->name('mobile.')->group(function () {
        // Dashboard
        Route::get('/', [MobileController::class, 'dashboard'])->name('dashboard');

        // Boxes
        Route::get('/boxes', [MobileController::class, 'boxes'])->name('boxes');

        // Invoices
        Route::get('/invoices', [MobileController::class, 'invoices'])->name('invoices');
        Route::get('/invoices/{invoice}', [MobileController::class, 'invoiceShow'])->name('invoices.show');
        Route::get('/invoices/{invoice}/pdf', [MobileController::class, 'invoicePdf'])->name('invoices.pdf');

        // Payments
        Route::get('/payments', [MobileController::class, 'payments'])->name('payments');
        Route::get('/payments/{payment}', [MobileController::class, 'paymentShow'])->name('payments.show');
        Route::get('/payments/{payment}/receipt', [MobileController::class, 'paymentReceipt'])->name('payments.receipt');

        // Contracts
        Route::get('/contracts', [MobileController::class, 'contracts'])->name('contracts');
        Route::get('/contracts/{contract}', [MobileController::class, 'contractShow'])->name('contracts.show');
        Route::get('/contracts/{contract}/pdf', [MobileController::class, 'contractPdf'])->name('contracts.pdf');
        Route::get('/contracts/{contract}/renew', function ($contract) {
            return Inertia::render('Mobile/Contracts/Renew', ['contract_id' => $contract]);
        })->name('contracts.renew-form');
        Route::post('/contracts/{contract}/renew', [MobileController::class, 'renewContract'])->name('contracts.renew');
        Route::post('/contracts/{contract}/terminate', [MobileController::class, 'terminateContract'])->name('contracts.terminate');

        // Reserve
        Route::get('/reserve', [MobileController::class, 'reserve'])->name('reserve');
        Route::post('/reserve', [MobileController::class, 'storeReservation'])->name('reserve.store');

        // Pay
        Route::get('/pay', [MobileController::class, 'pay'])->name('pay');
        Route::post('/pay', [MobileController::class, 'processPayment'])->name('pay.process');

        // Access
        Route::get('/access', [MobileController::class, 'access'])->name('access');

        // More menu
        Route::get('/more', [MobileController::class, 'more'])->name('more');

        // Profile
        Route::get('/profile', [MobileController::class, 'profile'])->name('profile');
        Route::put('/profile', [MobileController::class, 'updateProfile'])->name('profile.update');
        Route::put('/profile/address', [MobileController::class, 'updateAddress'])->name('profile.update-address');
        Route::put('/profile/password', [MobileController::class, 'changePassword'])->name('profile.change-password');
        Route::delete('/profile', [MobileController::class, 'deleteAccount'])->name('profile.delete');

        // Settings
        Route::get('/settings', [MobileController::class, 'settings'])->name('settings');
        Route::put('/settings', [MobileController::class, 'updateSettings'])->name('settings.update');
        Route::get('/settings/export-data', [MobileController::class, 'exportData'])->name('settings.export-data');

        // Help & Support
        Route::get('/help', function () {
            return Inertia::render('Mobile/Help/Index');
        })->name('help');

        Route::get('/support', [MobileController::class, 'support'])->name('support');
        Route::post('/support/send', [MobileController::class, 'sendSupport'])->name('support.send');

        Route::get('/faq', [MobileController::class, 'faq'])->name('faq');

        // Documents
        Route::get('/documents', [MobileController::class, 'documents'])->name('documents');
        Route::post('/documents/upload', [MobileController::class, 'uploadDocument'])->name('documents.upload');
        Route::get('/documents/{document}/download', [MobileController::class, 'downloadDocument'])->name('documents.download');

        // Payment methods
        Route::get('/payment-methods', [MobileController::class, 'paymentMethods'])->name('payment-methods');
        Route::post('/payment-methods/add-card', [MobileController::class, 'addCard'])->name('payment-methods.add-card');
        Route::put('/payment-methods/{card}/set-default', [MobileController::class, 'setDefaultCard'])->name('payment-methods.set-default');
        Route::delete('/payment-methods/{card}', [MobileController::class, 'deleteCard'])->name('payment-methods.delete-card');
        Route::post('/payment-methods/setup-sepa', [MobileController::class, 'setupSepa'])->name('payment-methods.setup-sepa');
        Route::delete('/payment-methods/sepa', [MobileController::class, 'revokeSepa'])->name('payment-methods.revoke-sepa');
        Route::put('/payment-methods/autopay', [MobileController::class, 'toggleAutoPay'])->name('payment-methods.toggle-autopay');

        // Insurance
        Route::get('/insurance', function () {
            return Inertia::render('Mobile/Insurance/Index');
        })->name('insurance');
    });

    /*
    |--------------------------------------------------------------------------
    | SuperAdmin Routes (Protected by super_admin role)
    |--------------------------------------------------------------------------
    */
    Route::prefix('superadmin')->name('superadmin.')->middleware('role:super_admin')->group(function () {
        // Dashboard
        Route::get('/dashboard', [SuperAdminDashboardController::class, 'index'])->name('dashboard');

        // Tenants Management
        Route::resource('tenants', SuperAdminTenantController::class);
        Route::post('/tenants/{tenant}/suspend', [SuperAdminTenantController::class, 'suspend'])->name('tenants.suspend');
        Route::post('/tenants/{tenant}/activate', [SuperAdminTenantController::class, 'activate'])->name('tenants.activate');
        Route::post('/tenants/{tenant}/impersonate', [SuperAdminTenantController::class, 'impersonate'])->name('tenants.impersonate');

        // Users Management
        Route::resource('users', SuperAdminUserController::class);
        Route::post('/users/{user}/toggle-status', [SuperAdminUserController::class, 'toggleStatus'])->name('users.toggle-status');

        // Subscriptions Management
        Route::get('/subscriptions', [SuperAdminSubscriptionController::class, 'index'])->name('subscriptions.index');
        Route::post('/subscriptions/{tenant}/change-plan', [SuperAdminSubscriptionController::class, 'changePlan'])->name('subscriptions.change-plan');
        Route::post('/subscriptions/{tenant}/extend-trial', [SuperAdminSubscriptionController::class, 'extendTrial'])->name('subscriptions.extend-trial');

        // Analytics
        Route::get('/analytics', [SuperAdminAnalyticsController::class, 'index'])->name('analytics.index');

        // System Settings
        Route::get('/settings', [SuperAdminSettingsController::class, 'index'])->name('settings.index');
        Route::post('/settings/clear-cache', [SuperAdminSettingsController::class, 'clearCache'])->name('settings.clear-cache');
        Route::post('/settings/optimize', [SuperAdminSettingsController::class, 'optimizeApplication'])->name('settings.optimize');
        Route::post('/settings/migrations', [SuperAdminSettingsController::class, 'runMigrations'])->name('settings.migrations');
        Route::post('/settings/maintenance', [SuperAdminSettingsController::class, 'maintenance'])->name('settings.maintenance');

        // Activity Logs / Audit Trail
        Route::get('/activity-logs', [SuperAdminActivityLogController::class, 'index'])->name('activity-logs.index');
        Route::get('/activity-logs/{activityLog}', [SuperAdminActivityLogController::class, 'show'])->name('activity-logs.show');
        Route::delete('/activity-logs/{activityLog}', [SuperAdminActivityLogController::class, 'destroy'])->name('activity-logs.destroy');
        Route::post('/activity-logs/clear-old', [SuperAdminActivityLogController::class, 'clearOld'])->name('activity-logs.clear-old');

        // Announcements
        Route::resource('announcements', SuperAdminAnnouncementController::class);
        Route::post('/announcements/{announcement}/toggle', [SuperAdminAnnouncementController::class, 'toggle'])->name('announcements.toggle');

        // Platform Billing
        Route::get('/billing', [SuperAdminBillingController::class, 'index'])->name('billing.index');
        Route::get('/billing/create', [SuperAdminBillingController::class, 'create'])->name('billing.create');
        Route::post('/billing', [SuperAdminBillingController::class, 'store'])->name('billing.store');
        Route::get('/billing/{platformInvoice}', [SuperAdminBillingController::class, 'show'])->name('billing.show');
        Route::post('/billing/{platformInvoice}/mark-paid', [SuperAdminBillingController::class, 'markAsPaid'])->name('billing.mark-paid');
        Route::post('/billing/{platformInvoice}/cancel', [SuperAdminBillingController::class, 'cancel'])->name('billing.cancel');
        Route::post('/billing/{platformInvoice}/send-reminder', [SuperAdminBillingController::class, 'sendReminder'])->name('billing.send-reminder');
        Route::post('/billing/generate-monthly', [SuperAdminBillingController::class, 'generateMonthlyInvoices'])->name('billing.generate-monthly');

        // Email Templates
        Route::resource('email-templates', SuperAdminEmailTemplateController::class);
        Route::post('/email-templates/{emailTemplate}/toggle', [SuperAdminEmailTemplateController::class, 'toggle'])->name('email-templates.toggle');
        Route::post('/email-templates/{emailTemplate}/preview', [SuperAdminEmailTemplateController::class, 'preview'])->name('email-templates.preview');
        Route::post('/email-templates/{emailTemplate}/duplicate', [SuperAdminEmailTemplateController::class, 'duplicate'])->name('email-templates.duplicate');

        // Backups
        Route::get('/backups', [SuperAdminBackupController::class, 'index'])->name('backups.index');
        Route::post('/backups', [SuperAdminBackupController::class, 'create'])->name('backups.create');
        Route::get('/backups/{backup}', [SuperAdminBackupController::class, 'show'])->name('backups.show');
        Route::get('/backups/{backup}/download', [SuperAdminBackupController::class, 'download'])->name('backups.download');
        Route::delete('/backups/{backup}', [SuperAdminBackupController::class, 'destroy'])->name('backups.destroy');
        Route::post('/backups/clean-old', [SuperAdminBackupController::class, 'cleanOld'])->name('backups.clean-old');

        // Feature Flags
        Route::resource('feature-flags', SuperAdminFeatureFlagController::class)->except(['show']);
        Route::post('/feature-flags/{featureFlag}/toggle', [SuperAdminFeatureFlagController::class, 'toggle'])->name('feature-flags.toggle');

        // Modules & Plans Management
        Route::prefix('modules')->name('modules.')->group(function () {
            Route::get('/', [SuperAdminModuleController::class, 'index'])->name('index');
            Route::get('/create', [SuperAdminModuleController::class, 'create'])->name('create');
            Route::post('/', [SuperAdminModuleController::class, 'store'])->name('store');
            Route::get('/{module}/edit', [SuperAdminModuleController::class, 'edit'])->name('edit');
            Route::put('/{module}', [SuperAdminModuleController::class, 'update'])->name('update');

            // Plans
            Route::get('/plans', [SuperAdminModuleController::class, 'plans'])->name('plans');
            Route::put('/plans/{plan}', [SuperAdminModuleController::class, 'updatePlan'])->name('plans.update');

            // Tenant Module Management
            Route::get('/tenant/{tenant}', [SuperAdminModuleController::class, 'tenantModules'])->name('tenant');
            Route::post('/tenant/{tenant}/enable', [SuperAdminModuleController::class, 'enableModuleForTenant'])->name('tenant.enable');
            Route::delete('/tenant/{tenant}/module/{module}', [SuperAdminModuleController::class, 'disableModuleForTenant'])->name('tenant.disable');
            Route::post('/tenant/{tenant}/change-plan', [SuperAdminModuleController::class, 'changeTenantPlan'])->name('tenant.change-plan');
            Route::post('/tenant/{tenant}/full-demo', [SuperAdminModuleController::class, 'startFullDemo'])->name('tenant.full-demo');

            // Demo History
            Route::get('/demos', [SuperAdminModuleController::class, 'demoHistory'])->name('demos');
            Route::post('/demos/{demo}/extend', [SuperAdminModuleController::class, 'extendDemo'])->name('demos.extend');
            Route::post('/demos/{demo}/convert', [SuperAdminModuleController::class, 'convertDemo'])->name('demos.convert');
            Route::post('/demos/{demo}/cancel', [SuperAdminModuleController::class, 'cancelDemo'])->name('demos.cancel');
        });

        // Stop impersonation
        Route::get('/stop-impersonating', function () {
            $originalUserId = session('impersonating_from');
            if ($originalUserId) {
                session()->forget('impersonating_from');
                Auth::loginUsingId($originalUserId);
                return redirect()->route('superadmin.dashboard')
                    ->with('success', 'Vous êtes de retour sur votre compte.');
            }
            return redirect()->route('superadmin.dashboard');
        })->name('stop-impersonating');

        // Blog Management
        Route::prefix('blog')->name('blog.')->group(function () {
            // Posts
            Route::get('/', [SuperAdminBlogController::class, 'index'])->name('index');
            Route::get('/create', [SuperAdminBlogController::class, 'create'])->name('create');
            Route::post('/', [SuperAdminBlogController::class, 'store'])->name('store');
            Route::get('/{post}/edit', [SuperAdminBlogController::class, 'edit'])->name('edit');
            Route::put('/{post}', [SuperAdminBlogController::class, 'update'])->name('update');
            Route::delete('/{post}', [SuperAdminBlogController::class, 'destroy'])->name('destroy');
            Route::post('/{post}/publish', [SuperAdminBlogController::class, 'publish'])->name('publish');
            Route::post('/{post}/archive', [SuperAdminBlogController::class, 'archive'])->name('archive');

            // Categories
            Route::get('/categories', [SuperAdminBlogController::class, 'categories'])->name('categories');
            Route::post('/categories', [SuperAdminBlogController::class, 'storeCategory'])->name('categories.store');
            Route::put('/categories/{category}', [SuperAdminBlogController::class, 'updateCategory'])->name('categories.update');
            Route::delete('/categories/{category}', [SuperAdminBlogController::class, 'destroyCategory'])->name('categories.destroy');

            // Tags
            Route::get('/tags', [SuperAdminBlogController::class, 'tags'])->name('tags');
            Route::post('/tags', [SuperAdminBlogController::class, 'storeTag'])->name('tags.store');
            Route::put('/tags/{tag}', [SuperAdminBlogController::class, 'updateTag'])->name('tags.update');
            Route::delete('/tags/{tag}', [SuperAdminBlogController::class, 'destroyTag'])->name('tags.destroy');

            // Comments
            Route::get('/comments', [SuperAdminBlogController::class, 'comments'])->name('comments');
            Route::post('/comments/{comment}/approve', [SuperAdminBlogController::class, 'approveComment'])->name('comments.approve');
            Route::post('/comments/{comment}/reject', [SuperAdminBlogController::class, 'rejectComment'])->name('comments.reject');
            Route::post('/comments/{comment}/spam', [SuperAdminBlogController::class, 'spamComment'])->name('comments.spam');
            Route::delete('/comments/{comment}', [SuperAdminBlogController::class, 'destroyComment'])->name('comments.destroy');
            Route::post('/comments/bulk', [SuperAdminBlogController::class, 'bulkCommentAction'])->name('comments.bulk');
        });
    });
});
