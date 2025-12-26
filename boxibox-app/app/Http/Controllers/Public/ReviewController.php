<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\ReviewRequest;
use App\Services\ReviewRequestService;
use Illuminate\Http\Request;
use Inertia\Inertia;

class ReviewController extends Controller
{
    public function __construct(
        protected ReviewRequestService $reviewService
    ) {}

    /**
     * Handle review link redirect with tracking
     */
    public function redirect($token)
    {
        $reviewUrl = $this->reviewService->handleClick($token);

        if (!$reviewUrl) {
            abort(404, 'Lien invalide ou expiré.');
        }

        return redirect()->away($reviewUrl);
    }

    /**
     * Handle unsubscribe request
     */
    public function unsubscribe($token)
    {
        $success = $this->reviewService->handleUnsubscribe($token);

        return Inertia::render('Public/Review/Unsubscribed', [
            'success' => $success,
        ]);
    }

    /**
     * Show thank you page after review
     */
    public function thankYou($token)
    {
        $request = ReviewRequest::where('tracking_token', $token)->first();

        if ($request && $request->status === 'clicked') {
            // Assume they left a review if they came back to thank you page
            $request->markReviewed();
        }

        return Inertia::render('Public/Review/ThankYou', [
            'hasIncentive' => $request?->site?->reviewSettings?->offer_incentive ?? false,
        ]);
    }
}
