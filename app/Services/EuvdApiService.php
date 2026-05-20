<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Vulnerability;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class EuvdApiService
{
    protected $baseUrl = 'https://cve.circl.lu/api/cvefor'; 

    public function fetchAndAssignVulnerabilities(Asset $asset): array
    {
        if (empty($asset->cpe)) {
            return ['success' => false, 'message' => 'O ativo não tem um CPE definido.'];
        }

        try {
            $response = Http::timeout(30)
                ->withHeaders([
                    'User-Agent' => 'AssetRiskManager/1.0',
                    'Accept' => 'application/json'
                ])
                ->get("{$this->baseUrl}/{$asset->cpe}");

            if ($response->status() === 404 || $response->status() === 400) {
                return ['success' => true, 'message' => 'Nenhuma vulnerabilidade (EUVD) encontrada para este CPE!', 'count' => 0];
            }

            if ($response->failed()) {
                Log::error("Erro EUVD | Status: " . $response->status() . " | Body: " . $response->body());
                return ['success' => false, 'message' => "Erro da API (Status: {$response->status()}). Verifique os logs em storage/logs para mais detalhes."];
            }

            $cvesEncontrados = $response->json();

            if (empty($cvesEncontrados) || !is_array($cvesEncontrados)) {
                return ['success' => true, 'message' => 'Nenhuma vulnerabilidade (EUVD) encontrada para este CPE!', 'count' => 0];
            }

            $novasVulnerabilidadesCount = 0;

            foreach ($cvesEncontrados as $item) {
                $cveId = $item['id'] ?? null;
                if (!$cveId) continue; 

                $description = $item['summary'] ?? 'Sem descrição detalhada.';
                $scoreAppreciation = $this->mapCvssScore($item['cvss'] ?? 0);

                $vulnerability = Vulnerability::firstOrCreate(
                    ['cve_id' => $cveId],
                    [
                        'description' => $description,
                        'source' => 'EUVD'
                    ]
                );

                $exists = $asset->vulnerabilities()->where('vulnerability_id', $vulnerability->id)->exists();

                if (!$exists) {
                    $asset->vulnerabilities()->attach($vulnerability->id, [
                        'probability' => $scoreAppreciation, 
                        'confidentiality_impact' => $scoreAppreciation,
                        'integrity_impact' => $scoreAppreciation,
                        'availability_impact' => $scoreAppreciation,
                        'residual_risk_accepted' => false,
                        'notes' => null
                    ]);
                    $novasVulnerabilidadesCount++;
                }
            }

            return [
                'success' => true, 
                'message' => "Pesquisa EUVD concluída! Foram importadas {$novasVulnerabilidadesCount} novas vulnerabilidades.",
                'count' => $novasVulnerabilidadesCount
            ];

        } catch (\Exception $e) {
            Log::error("Exceção ao processar EUVD: " . $e->getMessage());
            return ['success' => false, 'message' => 'Ocorreu um erro interno de rede ao processar os dados da EUVD.'];
        }
    }

    private function mapCvssScore($score): int
    {
        $baseScore = (float) $score;

        if ($baseScore == 0) return 1;
        if ($baseScore < 4.0) return 2;
        if ($baseScore < 7.0) return 3;
        if ($baseScore < 9.0) return 4;
        return 5;
    }
}