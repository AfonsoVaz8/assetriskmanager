<?php

namespace App\Http\Controllers;

use App\Exports\AssetListExport;
use App\Exports\CNCSExport;
use App\Exports\RiskMapExport;
use App\Models\Asset;
use App\Models\AssetThreat;
use Illuminate\Contracts\Foundation\Application;
use Illuminate\Contracts\View\Factory;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Response as ResponseAlias;
use App\Models\AnnualReport;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Storage;
use App\Services\GlpiApiService;

class ReportController extends Controller
{
    public function __invoke(Request $request)
    {
        $export = $request->input("export", "");
        $glpiService = new GlpiApiService();

        if (!empty($export)) {
            if ($export === "risk_map") {
                return Excel::download(new RiskMapExport, config("constants.exports.risk_map_file_name"));
            }
            if ($export === "asset_list") {
                return Excel::download(new AssetListExport, config("constants.exports.asset_list_file_name"));
            }
            if ($export === "cncs") {
                return Excel::download(new CNCSExport, config("constants.exports.asset_list_cncs_file_name"));
            }

            if (str_starts_with($export, "cncs_save")) {
                $year = Carbon::now()->year;
                $fileName = 'cncs_report_' . $year . '_' . time() . '.pdf';
                $filePath = 'reports/' . $fileName;

                $assets = Asset::with(['threats.threat', 'threats.controls', 'informationClassification', 'riskClassification'])->get();

                $rawIncidents = $glpiService->getIncidentsByYear($year);

                $enrichedIncidents = $rawIncidents->map(function ($incident) use ($assets) {
                    $matchingAsset = $assets->first(function ($asset) use ($incident) {
                        return str_contains(strtolower($incident['name']), strtolower($asset->name))
                            || (isset($incident['raw']['items_id']) && $incident['raw']['itemtype'] === 'Computer');
                    });

                    if ($matchingAsset) {
                        $incident['information_classification'] = $matchingAsset->informationClassification?->name ?? 'Não Classificado';
                        $incident['risk_classification'] = $matchingAsset->riskClassification?->name ?? 'Sem Risco';

                        $incident['confidentiality'] = $matchingAsset->confidentiality_impact ?? 1;
                        $incident['integrity']     = $matchingAsset->integrity_impact ?? 1;
                        $incident['availability']  = $matchingAsset->availability_impact ?? 1;
                    } else {
                        $incident['information_classification'] = 'N/A';
                        $incident['risk_classification'] = 'N/A';
                        $incident['confidentiality'] = 0;
                        $incident['integrity']     = 0;
                        $incident['availability']  = 0;
                    }

                    $incident['total'] = $incident['confidentiality'] + $incident['integrity'] + $incident['availability'];

                    return $incident;
                });

                $quarterlyIncidents = $enrichedIncidents->groupBy(function ($incident) {
                    $quarter = Carbon::parse($incident['date'])->quarter;
                    return 'Q' . $quarter;
                });

                $reportStats = [
                    'Q1' => $quarterlyIncidents->get('Q1', collect([]))->count(),
                    'Q2' => $quarterlyIncidents->get('Q2', collect([]))->count(),
                    'Q3' => $quarterlyIncidents->get('Q3', collect([]))->count(),
                    'Q4' => $quarterlyIncidents->get('Q4', collect([]))->count(),
                    'Total' => $enrichedIncidents->count(),
                ];

                $pdf = Pdf::loadView('reports.cncs_document', [
                    'year' => $year,
                    'assets' => $assets,
                    'groupedIncidents' => $quarterlyIncidents,
                    'stats' => $reportStats
                ]);

                Storage::disk('public')->put($filePath, $pdf->output());

                AnnualReport::create([
                    'year' => $year,
                    'file_path' => $filePath,
                    'type' => 'CNCS'
                ]);

                return $pdf->download($fileName);
            }

            if (str_starts_with($export, "cybersecurity_save")) {
                $year = Carbon::now()->year;
                $fileName = 'cybersecurity_report_' . $year . '_' . time() . '.pdf';
                $filePath = 'reports/' . $fileName;

                $assets = Asset::all();

                $pdf = Pdf::loadView('reports.cybersecurity_document', [
                    'year' => $year,
                    'assets' => $assets
                ]);

                Storage::disk('public')->put($filePath, $pdf->output());

                AnnualReport::create([
                    'year' => $year,
                    'file_path' => $filePath,
                    'type' => 'Cibersegurança'
                ]);

                return $pdf->download($fileName);
            }

            abort(ResponseAlias::HTTP_BAD_REQUEST);
        }
        else {
            $nodes_array = array();
            $edges_array = array();
            foreach (Asset::all() as $asset) {
                $data = trim(sprintf("%s\n%s\n%s\n%s", $asset->name, $asset->description, $asset->ip_address, $asset->fqdn));
                $nodes_array[] = array("data" => array(
                    "id" => $asset->id,
                    "data" => $data,
                    "width" => 12 * max(array_map("strlen", explode("\n", $data))),
                    "height" => 30 * count(explode("\n", $data)),
                    "link" => route("assets.edit", $asset->id),
                    "color" => AssetThreat::totalRiskColor($asset->highestRemainingRisk())
                ));
                if (!empty($asset->links_to_id)) {
                    $edges_array[] = array("data" => array("source" => $asset->id, "target" => $asset->links_to_id));
                }
            }

            $reportQuery = AnnualReport::query();

            if ($request->filled('filter_year')) {
                $reportQuery->where('year', $request->input('filter_year'));
            }

            if ($request->filled('filter_type')) {
                $reportQuery->where('type', $request->input('filter_type'));
            }

            if ($request->filled('filter_date')) {
                $reportQuery->whereDate('created_at', $request->input('filter_date'));
            }

            $annualReports = $reportQuery->orderBy('created_at', 'desc')->get();

            return view("reports.index", [
                "assets" => Asset::all(),
                "nodes_array" => $nodes_array,
                "edges_array" => $edges_array,
                "annualReports" => $annualReports
            ]);
        }
    }
}
