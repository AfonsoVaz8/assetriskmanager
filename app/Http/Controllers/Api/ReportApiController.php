<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AnnualReport;
use App\Models\Asset;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Carbon\Carbon;

class ReportApiController extends Controller
{

    public function index()
    {
        $reports = AnnualReport::orderBy('created_at', 'desc')->get();

        // Adiciona o URL completo para download a cada relatório
        $reports->map(function ($report) {
            $report->download_url = asset('storage/' . $report->file_path);
            return $report;
        });

        return response()->json([
            'status' => 'success',
            'data' => $reports
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'type' => 'required|in:CNCS,Cybersecurity'
        ]);

        $year = Carbon::now()->year;
        $type = $request->type;

        if ($type === 'CNCS') {
            $assets = Asset::all();
            $pdf = Pdf::loadView('reports.cncs_document', ['year' => $year, 'assets' => $assets]);
            $fileName = 'cncs_report_' . $year . '_' . time() . '.pdf';
            $typeStr = 'CNCS';
        } else {
            $assets = Asset::with(['threats.threat', 'threats.controls'])->get();
            $pdf = Pdf::loadView('reports.cybersecurity_document', ['year' => $year, 'assets' => $assets]);
            $fileName = 'cybersecurity_report_' . $year . '_' . time() . '.pdf';
            $typeStr = 'Cibersegurança';
        }

        $filePath = 'reports/' . $fileName;
        Storage::disk('public')->put($filePath, $pdf->output());

        $report = AnnualReport::create([
            'year' => $year,
            'file_path' => $filePath,
            'type' => $typeStr
        ]);

        return response()->json([
            'status' => 'success',
            'message' => 'Relatório gerado com sucesso.',
            'download_url' => asset('storage/' . $report->file_path),
            'data' => $report
        ], 201);
    }

    public function assetVulnerabilities()
    {
        $assets = Asset::with(['threats.threat', 'type'])
            ->has('threats')
            ->get()
            ->map(function ($asset) {
                return [
                    'asset_id' => $asset->id,
                    'asset_name' => $asset->name,
                    'asset_type' => $asset->type->name ?? 'N/A',
                    'total_appreciation' => $asset->totalAppreciation(), //
                    'vulnerabilities' => $asset->threats->map(function ($assetThreat) use ($asset) {
                        return [
                            'threat_id' => $assetThreat->threat->id,
                            'name' => $assetThreat->threat->name,
                            'description' => $assetThreat->threat->description,
                            'probability' => $assetThreat->probability,
                            'impact' => [
                                'confidentiality' => $assetThreat->confidentiality_impact,
                                'availability' => $assetThreat->availability_impact,
                                'integrity' => $assetThreat->integrity_impact,
                            ],
                            'absolute_risk' => $assetThreat->absoluteRisk(), //
                            'total_risk' => $assetThreat->totalRisk($asset->totalAppreciation()), //
                            'residual_risk' => $assetThreat->residual_risk,
                            'risk_accepted' => (bool)$assetThreat->residual_risk_accepted,
                        ];
                    })
                ];
            });

        return response()->json([
            'status' => 'success',
            'count' => $assets->count(),
            'data' => $assets
        ]);
    }
}
