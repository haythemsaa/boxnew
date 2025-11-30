<?php

namespace App\Http\Controllers\Signature;

use App\Http\Controllers\Controller;
use App\Models\Signature;
use App\Models\Contract;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Barryvdh\DomPDF\Facade\Pdf;

class PublicSignatureController extends Controller
{
    /**
     * Display the signing page for a signature request.
     */
    public function show(string $token)
    {
        $signature = Signature::where('signature_token', $token)
            ->with(['contract.box', 'contract.site', 'customer', 'tenant'])
            ->first();

        if (!$signature) {
            return Inertia::render('Signature/NotFound');
        }

        // Check if already signed
        if ($signature->status === 'signed') {
            return Inertia::render('Signature/AlreadySigned', [
                'signature' => $signature,
                'signedAt' => $signature->signed_at->format('d/m/Y H:i'),
            ]);
        }

        // Check if expired
        if ($signature->is_expired) {
            return Inertia::render('Signature/Expired', [
                'signature' => $signature,
            ]);
        }

        // Check if refused
        if ($signature->status === 'refused') {
            return Inertia::render('Signature/Refused', [
                'signature' => $signature,
            ]);
        }

        // Mark as viewed if not already
        $signature->markAsViewed();

        return Inertia::render('Signature/Sign', [
            'signature' => $signature,
            'contract' => $signature->contract,
            'customer' => $signature->customer,
            'tenant' => $signature->tenant,
            'daysUntilExpiry' => $signature->days_until_expiry,
        ]);
    }

    /**
     * Process the signature submission.
     */
    public function sign(Request $request, string $token)
    {
        $signature = Signature::where('signature_token', $token)
            ->with(['contract', 'customer', 'tenant'])
            ->first();

        if (!$signature) {
            return response()->json(['error' => 'Signature non trouvée'], 404);
        }

        if ($signature->status === 'signed') {
            return response()->json(['error' => 'Document déjà signé'], 400);
        }

        if ($signature->is_expired) {
            return response()->json(['error' => 'La demande de signature a expiré'], 400);
        }

        $request->validate([
            'signature_data' => 'required|string', // Base64 encoded signature image
            'signer_name' => 'required|string|max:255',
            'accepted_terms' => 'required|accepted',
        ]);

        // Save signature image
        $signatureImage = $this->saveSignatureImage($request->signature_data, $signature);

        // Generate signed PDF
        $signedPdfPath = $this->generateSignedPdf($signature, $signatureImage, $request->signer_name);

        // Mark as signed
        $signature->markAsSigned(
            $request->ip(),
            $request->userAgent()
        );

        // Update paths
        $signature->update([
            'signed_document_path' => $signedPdfPath,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Document signé avec succès',
            'redirect' => route('signature.confirmation', $token),
        ]);
    }

    /**
     * Display confirmation page after signing.
     */
    public function confirmation(string $token)
    {
        $signature = Signature::where('signature_token', $token)
            ->with(['contract.box', 'customer', 'tenant'])
            ->first();

        if (!$signature || $signature->status !== 'signed') {
            return redirect()->route('signature.show', $token);
        }

        return Inertia::render('Signature/Confirmation', [
            'signature' => $signature,
            'contract' => $signature->contract,
            'customer' => $signature->customer,
        ]);
    }

    /**
     * Refuse to sign the document.
     */
    public function refuse(Request $request, string $token)
    {
        $signature = Signature::where('signature_token', $token)->first();

        if (!$signature) {
            return response()->json(['error' => 'Signature non trouvée'], 404);
        }

        if (in_array($signature->status, ['signed', 'refused'])) {
            return response()->json(['error' => 'Action non autorisée'], 400);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        $signature->markAsRefused();
        $signature->update(['notes' => $request->reason]);

        return response()->json([
            'success' => true,
            'message' => 'Signature refusée',
        ]);
    }

    /**
     * Download the contract PDF for review.
     */
    public function downloadContract(string $token)
    {
        $signature = Signature::where('signature_token', $token)
            ->with(['contract.box', 'contract.site', 'customer', 'tenant'])
            ->first();

        if (!$signature) {
            abort(404);
        }

        // Generate contract PDF
        $pdf = Pdf::loadView('pdf.contract', [
            'contract' => $signature->contract,
            'customer' => $signature->customer,
            'tenant' => $signature->tenant,
            'box' => $signature->contract->box,
            'site' => $signature->contract->site,
        ]);

        return $pdf->download('contrat-' . $signature->contract->contract_number . '.pdf');
    }

    /**
     * Save the signature image to storage.
     */
    private function saveSignatureImage(string $base64Data, Signature $signature): string
    {
        // Remove data URL prefix if present
        $base64Data = preg_replace('/^data:image\/\w+;base64,/', '', $base64Data);
        $imageData = base64_decode($base64Data);

        $filename = 'signatures/' . $signature->id . '_' . time() . '.png';
        Storage::disk('local')->put($filename, $imageData);

        return $filename;
    }

    /**
     * Generate a signed PDF with the signature embedded.
     */
    private function generateSignedPdf(Signature $signature, string $signatureImagePath, string $signerName): string
    {
        $contract = $signature->contract;
        $customer = $signature->customer;
        $tenant = $signature->tenant;

        $pdf = Pdf::loadView('pdf.contract-signed', [
            'contract' => $contract,
            'customer' => $customer,
            'tenant' => $tenant,
            'box' => $contract->box,
            'site' => $contract->site,
            'signatureImagePath' => Storage::disk('local')->path($signatureImagePath),
            'signerName' => $signerName,
            'signedAt' => now()->format('d/m/Y H:i:s'),
            'ipAddress' => request()->ip(),
        ]);

        $filename = 'contracts/signed/' . $contract->contract_number . '_signed_' . time() . '.pdf';
        Storage::disk('local')->put($filename, $pdf->output());

        return $filename;
    }
}
