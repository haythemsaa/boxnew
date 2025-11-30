<?php

namespace App\Http\Controllers\Booking;

use App\Http\Controllers\Controller;
use App\Models\Box;
use App\Models\Site;
use App\Models\Contract;
use App\Models\Customer;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use App\Notifications\WelcomeCustomerNotification;

class BookingController extends Controller
{
    /**
     * Display the booking search page.
     */
    public function index(Request $request): Response
    {
        $sites = Site::where('is_active', true)
            ->select('id', 'name', 'city', 'address')
            ->get();

        // Get available boxes with filters
        $query = Box::where('status', 'available')
            ->with(['site']);

        // Filter by site
        if ($request->filled('site_id')) {
            $query->where('site_id', $request->input('site_id'));
        }

        // Filter by size category
        if ($request->filled('size_category')) {
            $query->where('size_category', $request->input('size_category'));
        }

        // Filter by price range
        if ($request->filled('min_price')) {
            $query->where('current_price', '>=', $request->input('min_price'));
        }
        if ($request->filled('max_price')) {
            $query->where('current_price', '<=', $request->input('max_price'));
        }

        // Sort by price or size
        $sortBy = $request->input('sort_by', 'price_asc');
        switch ($sortBy) {
            case 'price_desc':
                $query->orderBy('current_price', 'desc');
                break;
            case 'size_asc':
                $query->orderBy('volume', 'asc');
                break;
            case 'size_desc':
                $query->orderBy('volume', 'desc');
                break;
            default:
                $query->orderBy('current_price', 'asc');
        }

        $availableBoxes = $query->paginate(12);

        return Inertia::render('Booking/Search', [
            'sites' => $sites,
            'boxes' => $availableBoxes,
            'filters' => [
                'site_id' => $request->input('site_id'),
                'size_category' => $request->input('size_category'),
                'min_price' => $request->input('min_price'),
                'max_price' => $request->input('max_price'),
                'sort_by' => $sortBy,
            ],
        ]);
    }

    /**
     * Display box details for booking.
     */
    public function show(Request $request, Box $box): Response
    {
        // Ensure box is available
        if ($box->status !== 'available') {
            abort(404, 'This box is not available for booking.');
        }

        $box->load(['site']);

        // Get similar boxes at the same site
        $similarBoxes = Box::where('site_id', $box->site_id)
            ->where('id', '!=', $box->id)
            ->where('status', 'available')
            ->where('size_category', $box->size_category)
            ->limit(4)
            ->get();

        return Inertia::render('Booking/Details', [
            'box' => $box,
            'similarBoxes' => $similarBoxes,
        ]);
    }

    /**
     * Display the booking checkout form.
     */
    public function checkout(Request $request, Box $box): Response
    {
        // Ensure box is available
        if ($box->status !== 'available') {
            return redirect()->route('booking.index')
                ->with('error', 'This box is no longer available.');
        }

        $box->load(['site']);

        // Calculate initial costs
        $monthlyPrice = $box->current_price;
        $depositAmount = $monthlyPrice; // Typically 1 month deposit
        $firstMonthRent = $monthlyPrice;
        $totalDue = $depositAmount + $firstMonthRent;

        return Inertia::render('Booking/Checkout', [
            'box' => $box,
            'pricing' => [
                'monthly_price' => $monthlyPrice,
                'deposit_amount' => $depositAmount,
                'first_month_rent' => $firstMonthRent,
                'total_due' => $totalDue,
            ],
        ]);
    }

    /**
     * Process the booking and create contract.
     */
    public function store(Request $request, Box $box): RedirectResponse
    {
        // Ensure box is still available
        if ($box->status !== 'available') {
            return back()->with('error', 'This box is no longer available.');
        }

        $validated = $request->validate([
            // Customer type
            'customer_type' => ['required', 'in:individual,company'],

            // Individual fields
            'first_name' => ['required_if:customer_type,individual', 'nullable', 'string', 'max:255'],
            'last_name' => ['required_if:customer_type,individual', 'nullable', 'string', 'max:255'],
            'date_of_birth' => ['nullable', 'date'],

            // Company fields
            'company_name' => ['required_if:customer_type,company', 'nullable', 'string', 'max:255'],
            'company_registration' => ['nullable', 'string', 'max:100'],
            'vat_number' => ['nullable', 'string', 'max:50'],

            // Contact information
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['required', 'string', 'max:20'],
            'mobile' => ['nullable', 'string', 'max:20'],

            // Address
            'address' => ['required', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:100'],
            'postal_code' => ['required', 'string', 'max:20'],
            'country' => ['required', 'string', 'max:100'],

            // Contract details
            'start_date' => ['required', 'date', 'after_or_equal:today'],
            'billing_frequency' => ['required', 'in:monthly,quarterly,yearly'],
            'payment_method' => ['required', 'in:bank_transfer,credit_card,direct_debit'],
            'auto_renew' => ['boolean'],

            // Agreement
            'agree_terms' => ['required', 'accepted'],
        ]);

        DB::beginTransaction();

        try {
            // Get tenant ID from box
            $tenantId = $box->tenant_id;

            // Check if customer already exists by email
            $customer = Customer::where('tenant_id', $tenantId)
                ->where('email', $validated['email'])
                ->first();

            // Create or update customer
            if (!$customer) {
                $customer = Customer::create([
                    'tenant_id' => $tenantId,
                    'type' => $validated['customer_type'],
                    'customer_number' => Customer::generateCustomerNumber(),
                    'first_name' => $validated['first_name'] ?? null,
                    'last_name' => $validated['last_name'] ?? null,
                    'date_of_birth' => $validated['date_of_birth'] ?? null,
                    'company_name' => $validated['company_name'] ?? null,
                    'company_registration' => $validated['company_registration'] ?? null,
                    'vat_number' => $validated['vat_number'] ?? null,
                    'email' => $validated['email'],
                    'phone' => $validated['phone'],
                    'mobile' => $validated['mobile'],
                    'address' => $validated['address'],
                    'city' => $validated['city'],
                    'postal_code' => $validated['postal_code'],
                    'country' => $validated['country'],
                    'payment_method' => $validated['payment_method'],
                    'status' => 'active',
                ]);
            }

            // Create contract
            $contract = Contract::create([
                'tenant_id' => $tenantId,
                'customer_id' => $customer->id,
                'site_id' => $box->site_id,
                'box_id' => $box->id,
                'contract_number' => Contract::generateContractNumber(),
                'status' => 'active',
                'start_date' => $validated['start_date'],
                'end_date' => null, // Open-ended
                'monthly_price' => $box->current_price,
                'deposit_amount' => $box->current_price,
                'deposit_status' => 'pending',
                'billing_frequency' => $validated['billing_frequency'],
                'billing_day' => now()->day,
                'payment_method' => $validated['payment_method'],
                'auto_renew' => $validated['auto_renew'] ?? false,
                'signed_by_customer' => true,
                'signed_at' => now(),
                'signature_ip' => $request->ip(),
            ]);

            // Update box status to occupied
            $box->update([
                'status' => 'occupied',
            ]);

            // Send welcome notification to customer
            if ($customer->user) {
                $customer->user->notify(new WelcomeCustomerNotification($customer));
            }

            DB::commit();

            return redirect()->route('booking.confirmation', $contract->id)
                ->with('success', 'Your booking has been confirmed!');

        } catch (\Exception $e) {
            DB::rollBack();

            return back()->withInput()
                ->with('error', 'There was an error processing your booking. Please try again.');
        }
    }

    /**
     * Display booking confirmation.
     */
    public function confirmation(Request $request, Contract $contract): Response
    {
        $contract->load(['customer', 'box.site']);

        return Inertia::render('Booking/Confirmation', [
            'contract' => $contract,
        ]);
    }
}
