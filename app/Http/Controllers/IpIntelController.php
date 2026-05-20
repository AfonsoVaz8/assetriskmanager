<?php

namespace App\Http\Controllers;

use App\Actions\Shodan\LookupIpAction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class IpIntelController extends Controller
{
    public function lookup(Request $request, LookupIpAction $lookupIpAction): JsonResponse
    {
        $validated = $request->validate([
            'ip' => ['required', 'ip'],
        ]);

        try {
            $data = $lookupIpAction->execute($validated['ip']);

            return response()->json([
                'success' => true,
                'ip' => $validated['ip'],
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            return response()->json([
                'success' => false,
                'message' => 'Erro ao consultar o Shodan.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}