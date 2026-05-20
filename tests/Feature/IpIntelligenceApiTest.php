<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IpIntelligenceApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_normalize_an_ip_intelligence_payload(): void
    {
        $user = User::factory()->create();

        $response = $this
            ->actingAs($user, 'sanctum')
            ->postJson('/api/ip-intelligence/normalize', [
                'ip' => '8.8.8.8',
                'source' => 'Example Provider',
                'collected_at' => '2026-04-29T12:00:00Z',
                'raw_response' => [
                    'ip_str' => '8.8.8.8',
                    'hostnames' => ['dns.google'],
                    'domains' => ['google.com'],
                    'asn' => 'AS15169',
                    'ports' => [53],
                    'vulns' => [
                        'CVE-2025-0001' => [
                            'cvss' => 9.8,
                            'summary' => 'Remote code execution',
                        ],
                    ],
                ],
            ]);

        $response->assertOk()
            ->assertJson([
                'ip' => '8.8.8.8',
                'hostnames' => ['dns.google'],
                'domains' => ['google.com'],
                'asn' => 'AS15169',
                'metadata' => [
                    'source' => 'Example Provider',
                    'collected_at' => '2026-04-29T12:00:00Z',
                ],
            ])
            ->assertJsonPath('services.0.port', '53')
            ->assertJsonPath('vulnerabilities.0.cve', 'CVE-2025-0001');
    }

    public function test_normalize_endpoint_requires_authentication(): void
    {
        $this->postJson('/api/ip-intelligence/normalize', [
            'ip' => '8.8.8.8',
            'raw_response' => [],
        ])->assertUnauthorized();
    }
}
