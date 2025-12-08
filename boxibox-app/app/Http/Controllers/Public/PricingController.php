<?php

namespace App\Http\Controllers\Public;

use App\Http\Controllers\Controller;
use App\Models\PricingPlan;
use Inertia\Inertia;

class PricingController extends Controller
{
    public function index()
    {
        return Inertia::render('Public/Pricing', [
            'plans' => PricingPlan::all(),
            'widgets' => PricingPlan::widgets(),
            'featureLabels' => PricingPlan::FEATURE_LABELS,
            'supportLabels' => PricingPlan::SUPPORT_LABELS,
        ]);
    }

    public function compare()
    {
        return Inertia::render('Public/PricingCompare', [
            'plans' => PricingPlan::all(),
            'widgets' => PricingPlan::widgets(),
            'featureLabels' => PricingPlan::FEATURE_LABELS,
            'supportLabels' => PricingPlan::SUPPORT_LABELS,
        ]);
    }
}
