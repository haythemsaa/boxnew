<?php

namespace App\Http\Controllers\Portal;

use App\Http\Controllers\Controller;
use App\Models\Contract;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Barryvdh\DomPDF\Facade\Pdf;

class PortalContractController extends Controller
{
    /**
     * Display a listing of the customer's contracts.
     */
    public function index(Request $request): Response
    {
        $user = $request->user();
        $customer = $user->customer;

        if (!$customer) {
            abort(403, 'No customer record found for this user.');
        }

        $contracts = $customer->contracts()
            ->with(['box.site'])
            ->when($request->input('status'), function ($query, $status) {
                $query->where('status', $status);
            })
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        return Inertia::render('Portal/Contracts/Index', [
            'contracts' => $contracts,
            'filters' => [
                'status' => $request->input('status'),
            ],
        ]);
    }

    /**
     * Display the specified contract.
     */
    public function show(Request $request, Contract $contract): Response
    {
        $user = $request->user();
        $customer = $user->customer;

        // Ensure customer can only view their own contracts
        if (!$customer || $contract->customer_id !== $customer->id) {
            abort(403, 'Unauthorized to view this contract.');
        }

        $contract->load(['box.site', 'invoices']);

        return Inertia::render('Portal/Contracts/Show', [
            'contract' => $contract,
        ]);
    }

    /**
     * Download the contract as PDF.
     */
    public function downloadPdf(Request $request, Contract $contract)
    {
        $user = $request->user();
        $customer = $user->customer;

        // Ensure customer can only download their own contracts
        if (!$customer || $contract->customer_id !== $customer->id) {
            abort(403, 'Unauthorized to download this contract.');
        }

        $contract->load(['customer', 'box', 'site']);
        $tenant = $contract->tenant;

        $pdf = Pdf::loadView('pdf.contract', [
            'contract' => $contract,
            'tenant' => $tenant,
        ]);

        $pdf->setPaper('a4', 'portrait');
        return $pdf->download("contract-{$contract->contract_number}.pdf");
    }
}
