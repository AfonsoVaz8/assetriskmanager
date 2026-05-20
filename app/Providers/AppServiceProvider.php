<?php

namespace App\Providers;

use App\Domain\ThreatMonitoring\Services\MicrosoftGraphProvider;
use App\Domain\ThreatMonitoring\Services\ThreatProviderManager;
use App\Models\Asset;
use App\Observers\AssetObserver;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register()
    {
        $this->app->bind(ThreatProviderManager::class, function ($app) {
            return new ThreatProviderManager([
                $app->make(MicrosoftGraphProvider::class),
            ]);
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        Asset::observe(AssetObserver::class);
    }
}
