<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tenant\DashboardController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
*/

// Home - Redirect to login
Route::get('/', function () {
    return redirect()->route('login');
});

/*
|--------------------------------------------------------------------------
| Tenant Routes (Protected)
|--------------------------------------------------------------------------
*/

Route::middleware(['auth'])->prefix('tenant')->name('tenant.')->group(function () {
    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');

    // Sites
    Route::get('/sites', function () {
        return inertia('Tenant/Sites/Index');
    })->name('sites.index');

    // Boxes
    Route::get('/boxes', function () {
        return inertia('Tenant/Boxes/Index');
    })->name('boxes.index');

    // Customers
    Route::get('/customers', function () {
        return inertia('Tenant/Customers/Index');
    })->name('customers.index');

    // Contracts
    Route::get('/contracts', function () {
        return inertia('Tenant/Contracts/Index');
    })->name('contracts.index');

    // Invoices
    Route::get('/invoices', function () {
        return inertia('Tenant/Invoices/Index');
    })->name('invoices.index');

    // Messages
    Route::get('/messages', function () {
        return inertia('Tenant/Messages/Index');
    })->name('messages.index');

    // Settings
    Route::get('/settings', function () {
        return inertia('Tenant/Settings');
    })->name('settings');
});

// Temporary login route (for testing - will be replaced with auth later)
Route::get('/login', function () {
    return inertia('Auth/Login');
})->name('login');

// Temporary logout route (for testing)
Route::post('/logout', function () {
    auth()->logout();
    return redirect()->route('login');
})->name('logout');
