<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class MobileCustomerAuth
{
    /**
     * Handle an incoming request.
     * Ensures the user is authenticated as a customer (has a customer record)
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if customer is logged in via session
        $customerId = session('mobile_customer_id');

        if (!$customerId) {
            if ($request->wantsJson() || $request->header('X-Inertia')) {
                return redirect()->route('mobile.login');
            }
            return redirect()->route('mobile.login');
        }

        // Get the customer
        $customer = \App\Models\Customer::find($customerId);

        if (!$customer) {
            session()->forget('mobile_customer_id');
            return redirect()->route('mobile.login')
                ->with('error', 'Session expirée. Veuillez vous reconnecter.');
        }

        // Share customer with all views
        $request->merge(['mobile_customer' => $customer]);
        view()->share('mobileCustomer', $customer);

        return $next($request);
    }
}
