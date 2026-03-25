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

    protected $listeners = ['managerUpdated' => 'updateManager'];

    public function mount($asset, $assetTypes)
    {
        $this->asset = $asset;
        $this->assetTypes = $assetTypes;
    }

    public function updateManager($managerId)
    {
        $this->asset->update(['manager_id' => $managerId]);
        $this->asset->refresh();
        session()->flash('status', __('Manager updated successfully'));
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