<?php

namespace Tests\Feature;

use App\Console\Commands\SyncAssetsFromShodan;
use App\Jobs\SyncAssetFromShodan;
use App\Models\Asset;
use App\Models\AssetShodanReport;
use App\Models\AssetType;
use App\Models\Integration;
use App\Models\User;
use App\Services\IpIntelligenceNormalizer;
use App\Services\ShodanClient;
use App\Services\ShodanIntegrationResolver;
use App\Services\ShodanThreatSyncService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Queue;
use Mockery;
use Tests\TestCase;

class ShodanSyncTest extends TestCase
{
    use RefreshDatabase;

    public function test_sync_job_persists_report_payload(): void
    {
        $asset = $this->createAssetWithIp();
        $integration = $this->createShodanIntegration();

        $payload = [
            'ip_str' => $asset->ip_address,
            'hostnames' => ['example.test'],
            'org' => 'Acme Corp',
            'ports' => [22, 443, 22],
            'vulns' => [
                'CVE-2024-0001' => ['cvss' => 9.8],
                'CVE-2023-9999' => ['cvss' => 7.5],
            ],
            'last_update' => '2024-01-01T10:00:00.000000',
            'data' => [
                ['port' => 22],
                ['port' => 443],
            ],
            '_source_label' => 'Shodan Host API',
        ];

        $client = Mockery::mock(ShodanClient::class);
        $client->shouldReceive('isEnabled')->andReturn(true);
        $client->shouldReceive('fetchHost')->with($integration, $asset->ip_address)->andReturn($payload);

        $this->app->instance(ShodanClient::class, $client);

        (new SyncAssetFromShodan($asset->id))->handle(
            $client,
            app(ShodanIntegrationResolver::class),
            app(ShodanThreatSyncService::class),
            app(IpIntelligenceNormalizer::class),
        );

        $this->assertDatabaseHas('asset_shodan_reports', [
            'asset_id' => $asset->id,
            'status' => 'synced',
        ]);

        $report = AssetShodanReport::firstOrFail();
        $this->assertSame([22, 443], $report->open_ports);
        $this->assertEqualsCanonicalizing(['CVE-2024-0001', 'CVE-2023-9999'], $report->vulnerabilities);
        $this->assertNotNull($report->last_seen_at);
        $this->assertSame($asset->ip_address, data_get($report->normalized_payload, 'ip'));
        $this->assertSame(['example.test'], data_get($report->normalized_payload, 'hostnames'));
        $this->assertSame('Acme Corp', data_get($report->normalized_payload, 'organization'));
    }

    public function test_command_dispatches_jobs_for_assets_with_ip(): void
    {
        $asset = $this->createAssetWithIp();
        $this->createShodanIntegration();

        Queue::fake();

        $this->artisan('shodan:sync-assets')
            ->assertExitCode(SyncAssetsFromShodan::SUCCESS);

        Queue::assertPushed(SyncAssetFromShodan::class, function (SyncAssetFromShodan $job) use ($asset) {
            return $job->assetId() === $asset->id;
        });
    }

    private function createAssetWithIp(string $ip = '203.0.113.10'): Asset
    {
        $user = User::factory()->create();
        $assetType = AssetType::create([
            'name' => 'Type-' . uniqid(),
        ]);

        return Asset::create([
            'name' => 'Test Asset',
            'asset_type_id' => $assetType->id,
            'manager_id' => $user->id,
            'description' => 'Test asset description',
            'sku' => 'SKU-' . uniqid(),
            'manufacturer' => 'Acme',
            'location' => 'Datacenter',
            'manufacturer_contract_type' => 'NONE',
            'manufacturer_contract_beginning_date' => null,
            'manufacturer_contract_ending_date' => null,
            'mac_address' => 'AA:BB:CC:DD:EE:FF',
            'ip_address' => $ip,
            'availability_appreciation' => 3,
            'integrity_appreciation' => 3,
            'confidentiality_appreciation' => 3,
            'export' => true,
            'active' => true,
        ]);
    }

    private function createShodanIntegration(): Integration
    {
        return Integration::create([
            'name' => 'Shodan',
            'provider' => 'shodan',
            'status' => 'active',
            'credentials' => [
                'api_key' => 'test-key',
                'base_url' => 'https://api.shodan.io',
            ],
        ]);
    }
}
