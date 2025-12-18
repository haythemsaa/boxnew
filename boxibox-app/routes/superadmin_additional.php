<?php

/**
 * Routes SuperAdmin additionnelles pour la gestion complète de la plateforme
 * À inclure dans web.php dans le groupe superadmin
 */

use App\Http\Controllers\SuperAdmin\SubscriptionPlanController;
use App\Http\Controllers\SuperAdmin\TenantManagementController;
use App\Http\Controllers\SuperAdmin\ImprovedDashboardController;

// INSTRUCTIONS POUR AJOUTER CES ROUTES DANS web.php:
//
// 1. Ajouter ces imports en haut du fichier:
// use App\Http\Controllers\SuperAdmin\SubscriptionPlanController as SuperAdminPlanController;
// use App\Http\Controllers\SuperAdmin\TenantManagementController as SuperAdminTenantManagementController;
// use App\Http\Controllers\SuperAdmin\ImprovedDashboardController;
//
// 2. Remplacer la route dashboard dans le groupe superadmin:
// Route::get('/dashboard', [ImprovedDashboardController::class, 'index'])->name('dashboard');
//
// 3. Ajouter ces routes dans le groupe Route::prefix('superadmin')->name('superadmin.')->middleware('role:super_admin'):

/*
        // Plans d'abonnement (Subscription Plans)
        Route::prefix('plans')->name('plans.')->group(function () {
            Route::get('/', [SuperAdminPlanController::class, 'index'])->name('index');
            Route::get('/create', [SuperAdminPlanController::class, 'create'])->name('create');
            Route::post('/', [SuperAdminPlanController::class, 'store'])->name('store');
            Route::get('/{plan}/edit', [SuperAdminPlanController::class, 'edit'])->name('edit');
            Route::put('/{plan}', [SuperAdminPlanController::class, 'update'])->name('update');
            Route::delete('/{plan}', [SuperAdminPlanController::class, 'destroy'])->name('destroy');
            Route::post('/{plan}/duplicate', [SuperAdminPlanController::class, 'duplicate'])->name('duplicate');
            Route::post('/{plan}/toggle', [SuperAdminPlanController::class, 'toggle'])->name('toggle');
        });

        // Gestion complète des tenants (Tenant Management)
        Route::prefix('tenant-management')->name('tenant-management.')->group(function () {
            // Clients
            Route::get('/{tenant}/customers', [SuperAdminTenantManagementController::class, 'customers'])->name('customers');
            Route::post('/{tenant}/customers', [SuperAdminTenantManagementController::class, 'createCustomer'])->name('customers.create');

            // Boxes
            Route::get('/{tenant}/boxes', [SuperAdminTenantManagementController::class, 'boxes'])->name('boxes');
            Route::post('/{tenant}/boxes', [SuperAdminTenantManagementController::class, 'createBox'])->name('boxes.create');

            // Contrats
            Route::get('/{tenant}/contracts', [SuperAdminTenantManagementController::class, 'contracts'])->name('contracts');
            Route::post('/{tenant}/contracts', [SuperAdminTenantManagementController::class, 'createContract'])->name('contracts.create');

            // Abonnement
            Route::get('/{tenant}/subscription', [SuperAdminTenantManagementController::class, 'subscription'])->name('subscription');
            Route::post('/{tenant}/subscription/change', [SuperAdminTenantManagementController::class, 'changeSubscription'])->name('subscription.change');
            Route::post('/{tenant}/subscription/suspend', [SuperAdminTenantManagementController::class, 'suspendSubscription'])->name('subscription.suspend');
            Route::post('/{tenant}/subscription/reactivate', [SuperAdminTenantManagementController::class, 'reactivateSubscription'])->name('subscription.reactivate');

            // Factures plateforme
            Route::post('/{tenant}/invoices', [SuperAdminTenantManagementController::class, 'createPlatformInvoice'])->name('invoices.create');

            // Finances
            Route::get('/{tenant}/financials', [SuperAdminTenantManagementController::class, 'financials'])->name('financials');

            // Limites
            Route::post('/{tenant}/limits', [SuperAdminTenantManagementController::class, 'updateLimits'])->name('limits.update');
        });
*/
