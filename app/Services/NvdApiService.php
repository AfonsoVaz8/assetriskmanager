<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Vulnerability;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class NvdApiService
{
    public function fetchAndAssignVulnerabilities(Asset $asset): array
    {
        if (empty($asset->cpe)) {
            return ['success' => false, 'message' => 'O ativo não tem um CPE definido.'];
        }

        try {
            $response = Http::timeout(30)->get('https://services.nvd.nist.gov/rest/json/cves/2.0', [
                'cpeName' => $asset->cpe
            ]);

            if ($response->failed()) {
                Log::error("Erro na API da NVD para o CPE: {$asset->cpe}. Status: " . $response->status());
                return ['success' => false, 'message' => 'Erro ao comunicar com a NVD. Tente novamente mais tarde.'];
            }

            $data = $response->json();
            $cvesEncontrados = $data['vulnerabilities'] ?? [];

            if (empty($cvesEncontrados)) {
                return ['success' => true, 'message' => 'Nenhuma vulnerabilidade conhecida encontrada para este CPE!', 'count' => 0];
            }

            $novasVulnerabilidadesCount = 0;

            foreach ($cvesEncontrados as $item) {
                $cveData = $item['cve'];
                $cveId = $cveData['id'];
                
                $description = 'Sem descrição';
                foreach ($cveData['descriptions'] as $desc) {
                    if ($desc['lang'] === 'en') {
                        $description = $desc['value'];
                        break;
                    }
                }

                $scoreAppreciation = $this->extractAndMapCvssScore($cveData['metrics'] ?? []);

                $vulnerability = Vulnerability::firstOrCreate(
                    ['cve_id' => $cveId],
                    [
                        'description' => $description,
                        'source' => 'NVD'
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
                    ]);
                    $novasVulnerabilidadesCount++;
                }
            }

            return [
                'success' => true, 
                'message' => "Pesquisa concluída! Foram importadas {$novasVulnerabilidadesCount} novas vulnerabilidades.",
                'count' => $novasVulnerabilidadesCount
            ];

        } catch (\Exception $e) {
            Log::error("Exceção ao processar NVD: " . $e->getMessage());
            return ['success' => false, 'message' => 'Ocorreu um erro interno ao processar os dados da NVD.'];
        }
    }

    private function extractAndMapCvssScore(array $metrics): int
    {
        $baseScore = 0;

        if (isset($metrics['cvssMetricV31'][0]['cvssData']['baseScore'])) {
            $baseScore = $metrics['cvssMetricV31'][0]['cvssData']['baseScore'];
        } elseif (isset($metrics['cvssMetricV30'][0]['cvssData']['baseScore'])) {
            $baseScore = $metrics['cvssMetricV30'][0]['cvssData']['baseScore'];
        } elseif (isset($metrics['cvssMetricV2'][0]['cvssData']['baseScore'])) {
            $baseScore = $metrics['cvssMetricV2'][0]['cvssData']['baseScore'];
        }

        if ($baseScore == 0) return 1;
        if ($baseScore < 4) return 2;
        if ($baseScore < 7) return 3;
        if ($baseScore < 9) return 4;
        return 5;
    }
}