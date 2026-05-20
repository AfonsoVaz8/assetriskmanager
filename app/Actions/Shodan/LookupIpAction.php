<?php

namespace App\Actions\Shodan;

use Illuminate\Support\Facades\Http;
use Illuminate\Http\Client\RequestException;

class LookupIpAction
{
    public function execute(string $ip): array
    {
        $apiKey = config('services.shodan.key');

        if (empty($apiKey)) {
            throw new \RuntimeException('SHODAN_API_KEY não está configurada.');
        }

        $response = Http::timeout(15)
            ->acceptJson()
            ->get("https://api.shodan.io/shodan/host/{$ip}", [
                'key' => $apiKey,
            ]);

        if ($response->failed()) {
            throw new RequestException($response);
        }

        return $response->json();
    }
}