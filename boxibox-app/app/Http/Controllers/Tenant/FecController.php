<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\FecExport;
use App\Services\FecExportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response as InertiaResponse;

class FecController extends Controller
{
    protected FecExportService $fecService;

    public function __construct(FecExportService $fecService)
    {
        $this->fecService = $fecService;
    }

    /**
     * Afficher la page d'export FEC
     */
    public function index(Request $request): InertiaResponse
    {
        $tenant = $request->user()->tenant;

        $exports = FecExport::where('tenant_id', $tenant->id)
            ->with('generatedBy:id,name')
            ->orderBy('fiscal_year', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Annees fiscales disponibles (5 dernieres annees)
        $currentYear = Carbon::now()->year;
        $availableYears = range($currentYear, $currentYear - 4);

        return Inertia::render('Tenant/Accounting/FecExport', [
            'exports' => $exports,
            'availableYears' => $availableYears,
            'currentYear' => $currentYear,
        ]);
    }

    /**
     * Generer un nouvel export FEC
     */
    public function generate(Request $request)
    {
        $request->validate([
            'fiscal_year' => 'required|integer|min:2020|max:' . Carbon::now()->year,
            'period_start' => 'nullable|date',
            'period_end' => 'nullable|date|after_or_equal:period_start',
        ]);

        $tenant = $request->user()->tenant;

        // Verifier si un export existe deja pour cette annee
        $existingExport = FecExport::where('tenant_id', $tenant->id)
            ->where('fiscal_year', $request->fiscal_year)
            ->where('status', 'ready')
            ->first();

        if ($existingExport) {
            return back()->with('warning', 'Un export FEC existe deja pour cette annee fiscale.');
        }

        try {
            $periodStart = $request->period_start
                ? Carbon::parse($request->period_start)
                : null;
            $periodEnd = $request->period_end
                ? Carbon::parse($request->period_end)
                : null;

            $fecExport = $this->fecService->generate(
                $tenant,
                $request->fiscal_year,
                $periodStart,
                $periodEnd
            );

            return back()->with('success', sprintf(
                'Export FEC genere avec succes. %d ecritures comptables exportees.',
                $fecExport->entries_count
            ));

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors de la generation du FEC: ' . $e->getMessage());
        }
    }

    /**
     * Telecharger un export FEC
     */
    public function download(FecExport $fecExport)
    {
        // Verifier que l'export appartient au tenant
        if ($fecExport->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        if (!$fecExport->isReady()) {
            return back()->with('error', "L'export FEC n'est pas pret au telechargement.");
        }

        try {
            $data = $this->fecService->download($fecExport);

            return response($data['content'])
                ->header('Content-Type', 'text/plain; charset=utf-8')
                ->header('Content-Disposition', 'attachment; filename="' . $data['filename'] . '"')
                ->header('Cache-Control', 'no-cache, no-store, must-revalidate')
                ->header('Pragma', 'no-cache')
                ->header('Expires', '0');

        } catch (\Exception $e) {
            return back()->with('error', 'Erreur lors du telechargement: ' . $e->getMessage());
        }
    }

    /**
     * Valider un export FEC
     */
    public function validateExport(FecExport $fecExport)
    {
        if ($fecExport->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        $validation = $this->fecService->validate($fecExport);

        return response()->json($validation);
    }

    /**
     * Supprimer un export FEC
     */
    public function destroy(FecExport $fecExport)
    {
        if ($fecExport->tenant_id !== auth()->user()->tenant_id) {
            abort(403);
        }

        // Supprimer le fichier
        if ($fecExport->file_path && Storage::exists($fecExport->file_path)) {
            Storage::delete($fecExport->file_path);
        }

        $fecExport->delete();

        return back()->with('success', 'Export FEC supprime.');
    }
}
