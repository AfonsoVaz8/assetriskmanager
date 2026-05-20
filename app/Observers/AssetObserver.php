<?php

namespace App\Observers;

use App\Jobs\SyncAssetFromShodan;
use App\Models\Asset;

class AssetObserver
{
    public function created(Asset $asset): void
    {
        $this->dispatchSync($asset);
    }

    public function updated(Asset $asset): void
    {
        if ($asset->wasChanged(['ip_address', 'allowed_open_ports'])) {
            $this->dispatchSync($asset);
        }
    }

    protected function dispatchSync(Asset $asset): void
    {
        if (blank($asset->ip_address)) {
            return;
        }

        SyncAssetFromShodan::dispatch($asset->id);
    }
}
