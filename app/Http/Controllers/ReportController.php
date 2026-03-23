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

class ReportController extends Controller
{
    /**
     * Handle the incoming request.
     *
     * @param Request $request
     * @return Application|Factory|View|BinaryFileResponse
     */

    public function __invoke(Request $request)
    {
        $export = $request->input("export", "");
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
                $fileName = 'cncs_report_' . $year . '_' . time() . '.pdf'; // Agora é .pdf
                $filePath = 'reports/' . $fileName;

                $assets = Asset::all(); 

                $pdf = Pdf::loadView('reports.cncs_document', [
                    'year' => $year,
                    'assets' => $assets
                ]);

                Storage::disk('public')->put($filePath, $pdf->output());

                AnnualReport::create([
                    'year' => $year,
                    'file_path' => $filePath,
                    'type' => 'CNCS'
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
                )
                );
                if (!empty($asset->links_to_id)) {
                    $edges_array[] = array("data" => array("source" => $asset->id, "target" => $asset->links_to_id));
                }
                
            }
            $annualReports = AnnualReport::orderBy('year', 'desc')->get();
            return view("reports.index", [
                "assets" => Asset::all(),
                "nodes_array" => $nodes_array,
                "edges_array" => $edges_array,
                "annualReports" => $annualReports // <- ADICIONAR ESTA LINHA
            ]);
        }
    }
}
