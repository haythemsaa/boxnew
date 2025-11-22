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
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

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
        Route::resource('boxes', BoxController::class);

        // Customers (Resource Controller)
        Route::resource('customers', CustomerController::class);

        // Contracts (Resource Controller)
        Route::resource('contracts', ContractController::class);
        Route::get('contracts/{contract}/pdf', [ContractController::class, 'downloadPdf'])->name('contracts.pdf');

        // Invoices (Resource Controller)
        Route::resource('invoices', InvoiceController::class);
        Route::get('invoices/{invoice}/pdf', [InvoiceController::class, 'downloadPdf'])->name('invoices.pdf');

        // Payments (Resource Controller)
        Route::resource('payments', PaymentController::class);

        // Messages
        Route::get('/messages', function () {
            return Inertia::render('Tenant/Messages/Index');
        })->name('messages.index');

        // Settings
        Route::get('/settings', function () {
            return Inertia::render('Tenant/Settings');
        })->name('settings');
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
});
