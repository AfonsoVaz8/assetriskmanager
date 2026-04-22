<?php

namespace App\Livewire;

use App\Models\Asset;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;

class AssetEditForm extends Component
{
    use AuthorizesRequests;

    public $asset;
    public $assetTypes;

    public function mount($asset, $assetTypes)
    {
        $this->asset = $asset;
        $this->assetTypes = $assetTypes;
    }


    public function render()
    {
        $this->authorize('update', $this->asset);

        return view('livewire.asset-edit-form', [
            'asset' => $this->asset,
            'assetTypes' => $this->assetTypes
        ]);
    }
}