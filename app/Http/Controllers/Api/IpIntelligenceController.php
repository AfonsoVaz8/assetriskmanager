<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\NormalizeIpIntelligenceRequest;
use App\Services\IpIntelligenceNormalizer;
use Illuminate\Http\JsonResponse;

class IpIntelligenceController extends Controller
{
    public function normalize(
        NormalizeIpIntelligenceRequest $request,
        IpIntelligenceNormalizer $normalizer
    ): JsonResponse {
        return response()->json(
            $normalizer->normalize(
                ip: (string) $request->string('ip'),
                raw: $request->input('raw_response', []),
                source: $request->input('source'),
                collectedAt: $request->input('collected_at'),
            )
        );
    }
}
