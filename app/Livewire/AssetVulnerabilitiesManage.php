<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Asset;
use App\Services\NvdApiService;
use App\Services\EuvdApiService; // <-- NÃO ESQUECER ESTE USE
use Illuminate\Support\Facades\Log;

class AssetVulnerabilitiesManage extends Component
{
    public Asset $asset;
    public $source = 'nvd';
    public $message = '';
    public $messageType = '';
    public $confirmingVulnerabilityRemoval = false;
    public $vulnerabilityIdToRemove = null;

    public function importVulnerabilities(NvdApiService $nvdService, EuvdApiService $euvdService)
    {
        $this->message = '';

        try {
            if ($this->source === 'nvd') {
                $result = $nvdService->fetchAndAssignVulnerabilities($this->asset);
            } elseif ($this->source === 'euvd') {
                $result = $euvdService->fetchAndAssignVulnerabilities($this->asset);
            } else {
                $result = ['success' => false, 'message' => 'Fonte desconhecida.'];
            }

            $this->messageType = $result['success'] ? 'success' : 'error';
            $this->message = $result['message'];

        } catch (\Exception $e) {
            Log::error("Erro no componente Livewire: " . $e->getMessage());
            $this->messageType = 'error';
            $this->message = 'Erro: ' . $e->getMessage();
        }
    }

    public function confirmVulnerabilityRemoval($id)
    {
        $this->vulnerabilityIdToRemove = $id;
        $this->confirmingVulnerabilityRemoval = true;
    }


    public function removeVulnerability() 
    {
        try {
            if ($this->vulnerabilityIdToRemove) {
                $this->asset->vulnerabilities()->detach($this->vulnerabilityIdToRemove);
                
                $this->messageType = 'success';
                $this->message = 'Vulnerabilidade removida com sucesso do ativo.';
            }
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error("Erro ao remover vulnerabilidade: " . $e->getMessage());
            $this->messageType = 'error';
            $this->message = 'Ocorreu um erro ao tentar remover a vulnerabilidade.';
        }

        $this->confirmingVulnerabilityRemoval = false;
        $this->vulnerabilityIdToRemove = null;
    }

    public function render()
    {
        $assetVulnerabilities = $this->asset->vulnerabilities()->get();

        return view('livewire.asset-vulnerabilities-manage', [
            'assetVulnerabilities' => $assetVulnerabilities
        ]);
    }
}