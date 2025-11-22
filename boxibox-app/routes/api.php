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
| Webhook Routes (No Authentication Required)
|--------------------------------------------------------------------------
*/

Route::post('/webhooks/stripe', [WebhookController::class, 'handleStripe'])->name('webhooks.stripe');

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
Route::post('/chatbot', [\App\Http\Controllers\API\ChatbotController::class, 'chat'])->name('api.chatbot.chat');
Route::post('/chatbot/recommend-size', [\App\Http\Controllers\API\ChatbotController::class, 'recommendSize'])->name('api.chatbot.recommend-size');

/*
|--------------------------------------------------------------------------
| Push Notifications API (Requires Auth)
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/notifications/register-token', [\App\Http\Controllers\API\NotificationController::class, 'registerToken'])->name('api.notifications.register');
    Route::post('/notifications/unregister-token', [\App\Http\Controllers\API\NotificationController::class, 'unregisterToken'])->name('api.notifications.unregister');
    Route::get('/notifications/tokens', [\App\Http\Controllers\API\NotificationController::class, 'getTokens'])->name('api.notifications.tokens');
    Route::get('/notifications/preferences', [\App\Http\Controllers\API\NotificationController::class, 'getPreferences'])->name('api.notifications.preferences.get');
    Route::put('/notifications/preferences', [\App\Http\Controllers\API\NotificationController::class, 'updatePreferences'])->name('api.notifications.preferences.update');
});

Route::prefix('v1')->group(function () {
    /*
    |--------------------------------------------------------------------------
    | Authentication Routes
    |--------------------------------------------------------------------------
    */
    Route::post('/auth/login', [AuthController::class, 'login'])->name('api.v1.auth.login');
    Route::post('/auth/register', [AuthController::class, 'register'])->name('api.v1.auth.register');

    /*
    |--------------------------------------------------------------------------
    | Protected Routes (Require Authentication)
    |--------------------------------------------------------------------------
    */
    Route::middleware('auth:sanctum')->group(function () {
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
