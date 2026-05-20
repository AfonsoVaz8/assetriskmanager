<?php

namespace App\Console\Commands;

use App\Enums\AttackSurfaceScopeStatus;
use App\Jobs\RunAttackSurfaceDiscovery as RunAttackSurfaceDiscoveryJob;
use App\Models\AttackSurfaceScope;
use App\Services\AttackSurfaceDiscoveryService;
use Illuminate\Console\Command;

class RunAttackSurfaceDiscovery extends Command
{
    protected $signature = 'attack-surface:run {scope_id : The approved discovery scope to execute}';

    protected $description = 'Dispatch a controlled external attack surface discovery run for an approved scope.';

    public function handle(AttackSurfaceDiscoveryService $service): int
    {
        $scope = AttackSurfaceScope::query()->find($this->argument('scope_id'));

        if (!$scope) {
            $this->error('Scope not found.');
            return self::FAILURE;
        }

        if ($scope->status !== AttackSurfaceScopeStatus::APPROVED) {
            $this->error('Only approved discovery scopes can be executed.');
            return self::FAILURE;
        }

        $run = $service->createRun($scope);

        RunAttackSurfaceDiscoveryJob::dispatch($run->id);

        $this->info("Attack surface discovery run {$run->id} dispatched.");

        return self::SUCCESS;
    }
}
