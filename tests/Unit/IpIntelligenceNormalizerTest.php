<?php

namespace Tests\Unit;

use App\Services\IpIntelligenceNormalizer;
use PHPUnit\Framework\TestCase;

class IpIntelligenceNormalizerTest extends TestCase
{
    public function test_it_normalizes_a_shodan_like_payload_into_the_fixed_schema(): void
    {
        $payload = [
            'ip_str' => '8.8.8.8',
            'hostnames' => ['dns.google'],
            'domains' => ['google.com'],
            'asn' => 'AS15169',
            'isp' => 'Google LLC',
            'org' => 'Google Public DNS',
            'country_name' => 'United States',
            'city' => 'Mountain View',
            'region_code' => 'CA',
            'latitude' => 37.4056,
            'longitude' => -122.0775,
            'ports' => [53, 443],
            'cpes' => ['cpe:2.3:a:google:dns:*:*:*:*:*:*:*:*'],
            'os' => 'Linux',
            'tags' => ['dns', 'resolver'],
            'data' => [
                [
                    'port' => 443,
                    'transport' => 'tcp',
                    'product' => 'nginx',
                    'version' => '1.25.0',
                    'data' => 'HTTP/1.1 200 OK',
                    'ssl' => [
                        'cert' => [
                            'subject' => ['CN' => 'dns.google'],
                            'issuer' => ['CN' => 'GTS CA'],
                            'issued' => '2026-01-01T00:00:00Z',
                            'expires' => '2026-12-31T23:59:59Z',
                            'sha1' => 'ABC123',
                        ],
                    ],
                ],
            ],
            'vulns' => [
                'CVE-2025-0001' => [
                    'cvss' => 9.8,
                    'summary' => 'Remote code execution',
                ],
            ],
        ];

        $normalized = (new IpIntelligenceNormalizer())->normalize(
            ip: '8.8.8.8',
            raw: $payload,
            source: 'Shodan Host API',
            collectedAt: '2026-04-29T10:00:00Z',
        );

        $this->assertSame('8.8.8.8', $normalized['ip']);
        $this->assertSame(['dns.google'], $normalized['hostnames']);
        $this->assertSame(['google.com'], $normalized['domains']);
        $this->assertSame('AS15169', $normalized['asn']);
        $this->assertSame('Google LLC', $normalized['isp']);
        $this->assertSame('Google Public DNS', $normalized['organization']);
        $this->assertSame('United States', $normalized['country']);
        $this->assertSame('Mountain View', $normalized['city']);
        $this->assertSame('37.4056', $normalized['latitude']);
        $this->assertSame('-122.0775', $normalized['longitude']);
        $this->assertSame('CA', $normalized['region']);
        $this->assertCount(2, $normalized['services']);
        $this->assertSame(['cpe:2.3:a:google:dns:*:*:*:*:*:*:*:*'], $normalized['technologies']);
        $this->assertSame('Linux', $normalized['operating_system']);
        $this->assertSame('Shodan Host API', $normalized['metadata']['source']);
        $this->assertSame('2026-04-29T10:00:00Z', $normalized['metadata']['collected_at']);
        $this->assertSame('CVE-2025-0001', $normalized['vulnerabilities'][0]['cve']);
        $this->assertSame('9.8', $normalized['vulnerabilities'][0]['cvss']);
    }

    public function test_it_returns_not_found_and_empty_arrays_when_fields_do_not_exist(): void
    {
        $normalized = (new IpIntelligenceNormalizer())->normalize(
            ip: '203.0.113.10',
            raw: ['foo' => 'bar'],
        );

        $this->assertSame('203.0.113.10', $normalized['ip']);
        $this->assertSame([], $normalized['hostnames']);
        $this->assertSame([], $normalized['domains']);
        $this->assertSame('Not Found', $normalized['asn']);
        $this->assertSame('Not Found', $normalized['isp']);
        $this->assertSame('Not Found', $normalized['organization']);
        $this->assertSame('Not Found', $normalized['country']);
        $this->assertSame('Not Found', $normalized['city']);
        $this->assertSame('Not Found', $normalized['region']);
        $this->assertSame('Not Found', $normalized['latitude']);
        $this->assertSame('Not Found', $normalized['longitude']);
        $this->assertSame('Not Found', $normalized['network']);
        $this->assertSame([], $normalized['services']);
        $this->assertSame([], $normalized['technologies']);
        $this->assertSame('Not Found', $normalized['operating_system']);
        $this->assertSame([], $normalized['certificates']);
        $this->assertSame([], $normalized['vulnerabilities']);
        $this->assertSame('Not Found', $normalized['reputation']['score']);
        $this->assertSame([], $normalized['reputation']['tags']);
        $this->assertSame('Not Found', $normalized['metadata']['source']);
        $this->assertSame('Not Found', $normalized['metadata']['collected_at']);
    }
}
