<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Asset;
use Illuminate\Http\Request;

class AssetApiController extends Controller
{
    /**
     * Display a listing of the resource.
     * Supports filtering by ip address
     *
     * @param Request $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        $ip = $request->query('ip');

        if ($ip) {
            // Validate IP format
            if (!filter_var($ip, FILTER_VALIDATE_IP)) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Invalid IP address format'
                ], 400);
            }

            $asset = Asset::where('ip_address', $ip)->first();

            if (!$asset) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Asset not found for the provided IP address'
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'data' => $asset
            ]);
        }

        // Return all assets if no filter
        $assets = Asset::all();

        return response()->json([
            'status' => 'success',
            'data' => $assets
        ]);
    }

    /**
     * Display the specified asset with all related information.
     *
     * @param int $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        $asset = Asset::with(['threats.threat', 'type'])->find($id);

        if (!$asset) {
            return response()->json([
                'status' => 'error',
                'message' => 'Asset not found'
            ], 404);
        }

        $mapped = [
            'asset_id' => $asset->id,
            'asset_name' => $asset->name,
            'asset_type' => $asset->type->name ?? 'N/A',
            'sku' => $asset->sku,
            'description' => $asset->description,
            'manufacturer' => $asset->manufacturer,
            'location' => $asset->location,
            'version' => $asset->version,
            'mac_address' => $asset->mac_address,
            'fqdn' => $asset->fqdn,
            'ip_address' => $asset->ip_address,
            'total_appreciation' => $asset->totalAppreciation(),
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
                    'absolute_risk' => $assetThreat->absoluteRisk(),
                    'total_risk' => $assetThreat->totalRisk($asset->totalAppreciation()),
                    'residual_risk' => $assetThreat->residual_risk,
                    'risk_accepted' => (bool)$assetThreat->residual_risk_accepted,
                ];
            })->values(),
        ];

        return response()->json([
            'status' => 'success',
            'data' => $mapped
        ]);
    }
}
