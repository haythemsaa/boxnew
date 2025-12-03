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
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

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
| Box Size Calculator (Public)
|--------------------------------------------------------------------------
*/

Route::prefix('calculator')->name('calculator.')->group(function () {
    Route::get('/', [BoxCalculatorController::class, 'index'])->name('index');
    Route::post('/calculate', [BoxCalculatorController::class, 'calculate'])->name('calculate');
});

// Home - Redirect based on auth status and role
Route::get('/', function () {
    if (!Auth::check()) {
        return redirect()->route('login');
    }

    $user = Auth::user();

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
    // Main booking page by slug
    Route::get('/{slug}', [PublicBookingController::class, 'show'])->name('show');

    // Fallback by tenant ID
    Route::get('/tenant/{tenantId}', [PublicBookingController::class, 'showByTenant'])->name('by-tenant');

    // API endpoints for booking form
    Route::get('/api/sites/{siteId}/boxes', [PublicBookingController::class, 'getAvailableBoxes'])->name('get-boxes');
    Route::post('/api/promo-code/validate', [PublicBookingController::class, 'validatePromoCode'])->name('validate-promo');
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

            // Redirect based on user role
            $defaultRoute = $user->isClient()
                ? route('portal.dashboard')
                : route('tenant.dashboard');

            return redirect()->intended($defaultRoute);
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

        // Predictive Analytics Routes (ML/AI)
        Route::prefix('analytics/predictive')->name('analytics.predictive.')->controller(\App\Http\Controllers\Tenant\PredictiveController::class)->group(function () {
            Route::get('/', 'index')->name('index');
            Route::get('/occupation-forecast', 'occupationForecast')->name('occupation-forecast');
            Route::get('/churn-predictions', 'churnPredictions')->name('churn-predictions');
            Route::get('/upsell-opportunities', 'upsellOpportunities')->name('upsell-opportunities');
            Route::post('/boxes/{box}/optimize-pricing', 'optimizePricing')->name('optimize-pricing');
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
            Route::get('/boxes/{box}/details', [PlanController::class, 'getBoxDetails'])->name('box-details');
            Route::get('/floors/{site}', [PlanController::class, 'getFloors'])->name('get-floors');
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
});
