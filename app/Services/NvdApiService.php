<?php

namespace App\Services;

use App\Models\Asset;
use App\Models\Threat;
use App\Models\AssetThreat;
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

            $novasAmeaçasCount = 0;
            $listaNovasAmeaças = []; 

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

                $threat = Threat::firstOrCreate(
                    ['name' => $cveId],
                    ['description' => $description]
                );

                $exists = AssetThreat::where('asset_id', $asset->id)
                                     ->where('threat_id', $threat->id)
                                     ->exists();

                if (!$exists) {
                    AssetThreat::create([
                        'asset_id' => $asset->id,
                        'threat_id' => $threat->id,
                        'probability' => $scoreAppreciation, 
                        'confidentiality_impact' => $scoreAppreciation,
                        'integrity_impact' => $scoreAppreciation,
                        'availability_impact' => $scoreAppreciation,
                        'residual_risk_accepted' => false,
                    ]);
                    $novasAmeaçasCount++;
                    $listaNovasAmeaças[] = $threat; // ADICIONAR À LISTA
                }
            }

            return [
                'success' => true, 
                'message' => "Pesquisa concluída! Foram importadas {$novasAmeaçasCount} novas vulnerabilidades.",
                'count' => $novasAmeaçasCount,
                'new_threats' => $listaNovasAmeaças // DEVOLVER A LISTA AQUI
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