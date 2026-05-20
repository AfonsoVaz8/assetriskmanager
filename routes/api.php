<?php

use App\Http\Controllers\Api\IpIntelligenceController;
use App\Http\Controllers\Api\IncidentController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\ReportApiController;
use App\Http\Controllers\Api\AssetApiController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
*/

Route::middleware('auth:sanctum')->group(function () {

    Route::get('/user', function (Request $request) {
        return $request->user();
    });

    Route::get('/reports', [ReportApiController::class, 'index']);

    Route::post('/reports/generate', [ReportApiController::class, 'generate']);

    Route::get('/assets/vulnerabilities', [ReportApiController::class, 'assetVulnerabilities']);

    Route::get('/assets', [AssetApiController::class, 'index']);

});
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/incidents', [IncidentController::class, 'index']);
    Route::get('/incidents/{incident}', [IncidentController::class, 'show']);
    Route::patch('/incidents/{incident}/status', [IncidentController::class, 'updateStatus']);
    Route::post('/incidents/{incident}/reopen', [IncidentController::class, 'reopen']);
    Route::post('/ip-intelligence/normalize', [IpIntelligenceController::class, 'normalize']);
});
