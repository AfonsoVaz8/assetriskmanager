<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Asset;
use App\Services\NvdApiService;
use Illuminate\Support\Facades\Log;

class FetchVulnerabilities extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:fetch-vulnerabilities';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Verifica e importa novas vulnerabilidades (CVEs) para ativos com CPE definido.';

    /**
     * Execute the console command.
     */
    public function handle(NvdApiService $nvdService, EuvdApiService $euvdService)
    {
        $assets = Asset::whereNotNull('cpe')->where('cpe', '!=', '')->get();

        foreach ($assets as $asset) {
            $this->info("A verificar vulnerabilidades para: {$asset->name} (CPE: {$asset->cpe})");

            try {
                $result = $nvdService->fetchAndAssignVulnerabilities($asset);
                
                $this->info(" -> " . $result['message']);
                
                // $resultEuvd = $euvdService->fetchAndAssignVulnerabilities($asset);
                // $this->info(" -> EUVD: " . $resultEuvd['message']);

            } catch (\Exception $e) {
                $this->error(" -> Erro ao verificar {$asset->name}: " . $e->getMessage());
                Log::error("Erro no comando FetchVulnerabilities (Ativo: {$asset->id}): " . $e->getMessage());
            }
        }
    }
}