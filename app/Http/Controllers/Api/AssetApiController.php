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
}
