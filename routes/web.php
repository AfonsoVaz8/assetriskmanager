<?php

use App\Http\Controllers\AssetController;
use App\Http\Controllers\AttackSurfaceScopeController;
use App\Http\Controllers\AssetTypeController;
use App\Http\Controllers\ControlController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\FileImportController;
use App\Http\Controllers\IncidentController;
use App\Http\Controllers\IntegrationController;
use App\Http\Controllers\PermanentContactPointController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\SecurityOfficerController;
use App\Http\Controllers\ThreatEventController;
use App\Http\Controllers\ThreatController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', function () {
    return redirect()->route("dashboard");
});

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified'
])->group(function () {
    Route::get("dashboard", DashboardController::class)->name("dashboard");
    Route::resource("permanent-contact-points", PermanentContactPointController::class);
    Route::resource("security-officer", SecurityOfficerController::class);
    Route::resource("asset-types", AssetTypeController::class);
    Route::resource("departments", DepartmentController::class);
    Route::resource("users", UserController::class);
    Route::resource("threats", ThreatController::class);
    Route::resource("controls", ControlController::class);
    Route::resource("assets", AssetController::class);
    Route::get('assets/{asset}/discovered-host-details', [AssetController::class, 'viewDiscoveredHostDetails'])->name('assets.discovered-host-details');
    Route::middleware("ensureSecurityOfficer")->group(function () {
        Route::get('threat-events', [ThreatEventController::class, 'index'])->name('threat-events.index');
        Route::get('threat-events/{threatEvent}', [ThreatEventController::class, 'show'])->name('threat-events.show');
        Route::get('incidents', [IncidentController::class, 'index'])->name('incidents.index');
        Route::get('incidents/{incident}', [IncidentController::class, 'show'])->name('incidents.show');
        Route::patch('incidents/{incident}/status', [IncidentController::class, 'updateStatus'])->name('incidents.update-status');
        Route::post('incidents/{incident}/reopen', [IncidentController::class, 'reopen'])->name('incidents.reopen');
        Route::post('integrations/{integration}/sync', [IntegrationController::class, 'sync'])->name('integrations.sync');
        Route::resource("integrations", IntegrationController::class);
        Route::post('attack-surface-scopes/{attack_surface_scope}/approve', [AttackSurfaceScopeController::class, 'approve'])->name('attack-surface-scopes.approve');
        Route::post('attack-surface-scopes/{attack_surface_scope}/disable', [AttackSurfaceScopeController::class, 'disable'])->name('attack-surface-scopes.disable');
        Route::post('attack-surface-scopes/{attack_surface_scope}/run', [AttackSurfaceScopeController::class, 'run'])->name('attack-surface-scopes.run');
        Route::post('attack-surface-scopes/{attack_surface_scope}/enrich-active', [AttackSurfaceScopeController::class, 'enrichActive'])->name('attack-surface-scopes.enrich-active');
        Route::post('attack-surface-scopes/{attack_surface_scope}/enrich-selected', [AttackSurfaceScopeController::class, 'enrichSelected'])->name('attack-surface-scopes.enrich-selected');
        Route::post('attack-surface-scopes/{attack_surface_scope}/hosts/{discovered_host}/enrich', [AttackSurfaceScopeController::class, 'enrichHost'])->name('attack-surface-scopes.hosts.enrich');
        Route::post('attack-surface-scopes/{attack_surface_scope}/hosts/{discovered_host}/add-to-assets', [AttackSurfaceScopeController::class, 'addHostToAssets'])->name('attack-surface-scopes.hosts.add-to-assets');
        Route::get('attack-surface-scopes/{attack_surface_scope}/hosts/{discovered_host}', [AttackSurfaceScopeController::class, 'showHost'])->name('attack-surface-scopes.hosts.show');
        Route::resource('attack-surface-scopes', AttackSurfaceScopeController::class);
        Route::get("reports", ReportController::class)->name("reports");
        Route::view("exports","file-export.index")->name("exports");
        Route::view("imports", "file-import.index")->name("import");
        Route::post("imports", FileImportController::class)->name("import-file");
    });
    Route::get('phpinfo', function () {
        if (config("app.debug")){
            phpinfo();
        }
        else{
            abort(404);
        }
    })->name('phpinfo');
});
