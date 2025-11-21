<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use App\Http\Controllers\Tenant\DashboardController;
use App\Http\Controllers\Tenant\SiteController;
use Inertia\Inertia;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Home - Redirect based on auth status
Route::get('/', function () {
    return Auth::check() ? redirect()->route('tenant.dashboard') : redirect()->route('login');
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
            return redirect()->intended(route('tenant.dashboard'));
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

        // Boxes
        Route::get('/boxes', function () {
            return Inertia::render('Tenant/Boxes/Index');
        })->name('boxes.index');

        // Customers
        Route::get('/customers', function () {
            return Inertia::render('Tenant/Customers/Index');
        })->name('customers.index');

        // Contracts
        Route::get('/contracts', function () {
            return Inertia::render('Tenant/Contracts/Index');
        })->name('contracts.index');

        // Invoices
        Route::get('/invoices', function () {
            return Inertia::render('Tenant/Invoices/Index');
        })->name('invoices.index');

        // Messages
        Route::get('/messages', function () {
            return Inertia::render('Tenant/Messages/Index');
        })->name('messages.index');

        // Settings
        Route::get('/settings', function () {
            return Inertia::render('Tenant/Settings');
        })->name('settings');
    });
});
