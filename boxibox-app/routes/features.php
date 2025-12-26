<?php

/**
 * New Features Routes
 * - Waitlist Management
 * - Auction System (Lien Sales)
 * - Review Request System
 *
 * Include this file in web.php:
 * require __DIR__.'/features.php';
 */

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Tenant\WaitlistController;
use App\Http\Controllers\Tenant\AuctionController;
use App\Http\Controllers\Tenant\ReviewRequestController;
use App\Http\Controllers\Public\WaitlistController as PublicWaitlistController;
use App\Http\Controllers\Public\ReviewController as PublicReviewController;

/*
|--------------------------------------------------------------------------
| Public Waitlist Routes
|--------------------------------------------------------------------------
*/

Route::prefix('waitlist')->name('waitlist.')->group(function () {
    Route::get('/{siteSlug}', [PublicWaitlistController::class, 'create'])->name('create');
    Route::post('/{siteSlug}', [PublicWaitlistController::class, 'store'])->name('store');
    Route::get('/confirmation/{uuid}', [PublicWaitlistController::class, 'confirmation'])->name('confirmation');
    Route::get('/status/{uuid}', [PublicWaitlistController::class, 'status'])->name('status');
    Route::get('/click/{uuid}', [PublicWaitlistController::class, 'trackClick'])->name('click');
    Route::get('/unsubscribe/{uuid}', [PublicWaitlistController::class, 'unsubscribe'])->name('unsubscribe');
});

/*
|--------------------------------------------------------------------------
| Public Review Routes
|--------------------------------------------------------------------------
*/

Route::prefix('review')->name('review.')->group(function () {
    Route::get('/r/{token}', [PublicReviewController::class, 'redirect'])->name('redirect');
    Route::get('/unsubscribe/{token}', [PublicReviewController::class, 'unsubscribe'])->name('unsubscribe');
    Route::get('/thank-you/{token}', [PublicReviewController::class, 'thankYou'])->name('thank-you');
});

/*
|--------------------------------------------------------------------------
| Tenant Waitlist Routes
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'verified'])->prefix('tenant')->name('tenant.')->group(function () {

    // Waitlist Management
    Route::prefix('waitlist')->name('waitlist.')->group(function () {
        Route::get('/', [WaitlistController::class, 'index'])->name('index');
        Route::get('/settings', [WaitlistController::class, 'settings'])->name('settings');
        Route::post('/settings', [WaitlistController::class, 'updateSettings'])->name('settings.update');
        Route::post('/', [WaitlistController::class, 'store'])->name('store');
        Route::get('/{entry}', [WaitlistController::class, 'show'])->name('show');
        Route::put('/{entry}', [WaitlistController::class, 'update'])->name('update');
        Route::post('/{entry}/cancel', [WaitlistController::class, 'cancel'])->name('cancel');
        Route::post('/{entry}/notify', [WaitlistController::class, 'notify'])->name('notify');
    });

    // Auction Management (Lien Sales)
    Route::prefix('auctions')->name('auctions.')->group(function () {
        Route::get('/', [AuctionController::class, 'index'])->name('index');
        Route::get('/settings', [AuctionController::class, 'settings'])->name('settings');
        Route::post('/settings', [AuctionController::class, 'updateSettings'])->name('settings.update');
        Route::post('/', [AuctionController::class, 'store'])->name('store');
        Route::get('/{auction}', [AuctionController::class, 'show'])->name('show');
        Route::put('/{auction}', [AuctionController::class, 'update'])->name('update');
        Route::post('/{auction}/photos', [AuctionController::class, 'uploadPhotos'])->name('photos');
        Route::post('/{auction}/notice', [AuctionController::class, 'sendNotice'])->name('notice');
        Route::post('/{auction}/schedule', [AuctionController::class, 'schedule'])->name('schedule');
        Route::post('/{auction}/start', [AuctionController::class, 'start'])->name('start');
        Route::post('/{auction}/end', [AuctionController::class, 'end'])->name('end');
        Route::post('/{auction}/cancel', [AuctionController::class, 'cancel'])->name('cancel');
        Route::post('/{auction}/redeem', [AuctionController::class, 'redeem'])->name('redeem');
    });

    // Review Request Management
    Route::prefix('reviews')->name('reviews.')->group(function () {
        Route::get('/', [ReviewRequestController::class, 'index'])->name('index');
        Route::get('/settings', [ReviewRequestController::class, 'settings'])->name('settings');
        Route::post('/settings', [ReviewRequestController::class, 'updateSettings'])->name('settings.update');
        Route::get('/opt-outs', [ReviewRequestController::class, 'optOuts'])->name('opt-outs');
        Route::delete('/opt-outs/{optOut}', [ReviewRequestController::class, 'removeOptOut'])->name('opt-outs.remove');
        Route::post('/', [ReviewRequestController::class, 'store'])->name('store');
        Route::post('/bulk-send', [ReviewRequestController::class, 'bulkSend'])->name('bulk-send');
        Route::get('/{reviewRequest}', [ReviewRequestController::class, 'show'])->name('show');
        Route::post('/{reviewRequest}/send', [ReviewRequestController::class, 'send'])->name('send');
        Route::post('/{reviewRequest}/cancel', [ReviewRequestController::class, 'cancel'])->name('cancel');
    });
});
