<?php

namespace App\Livewire;

use App\Models\Asset;
use App\Models\User;
use Livewire\Component;

class AssetManagerManage extends Component
{
    public Asset $asset;
    public $manager_id;
    public $search = '';

    public function mount(Asset $asset)
    {
        $this->asset = $asset;
        $this->manager_id = old('manager_id', $asset->manager_id);
    }

    public function render()
    {
        $users = User::query()
            ->where(function($query) {
                $query->where('name', 'like', '%' . $this->search . '%')
                      ->orWhere('email', 'like', '%' . $this->search . '%');
            })
            ->when($this->manager_id, fn($q) => $q->orWhere('id', $this->manager_id))
            ->orderBy('name')
            ->limit(20)
            ->get();

        return view('livewire.asset-manager-manage', [
            'users' => $users,
        ]);
    }
}