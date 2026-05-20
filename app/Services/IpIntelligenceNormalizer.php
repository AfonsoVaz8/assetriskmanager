<?php

namespace App\Services;

class IpIntelligenceNormalizer
{
    public function normalize(string $ip, array $raw, ?string $source = null, ?string $collectedAt = null): array
    {
        return [
            'ip' => $this->stringOrNotFound(
                $this->firstScalarValue($raw, ['ip', 'ip_str', 'ipaddress', 'ip_address']) ?? $ip
            ),
            'hostnames' => $this->extractStringList($raw, ['hostnames', 'hostname', 'host_name']),
            'domains' => $this->extractStringList($raw, ['domains', 'domain', 'fqdns', 'fqdn']),
            'asn' => $this->stringOrNotFound($this->firstScalarValue($raw, ['asn', 'asn_number', 'as_number'])),
            'isp' => $this->stringOrNotFound($this->firstScalarValue($raw, ['isp', 'provider_name'])),
            'organization' => $this->stringOrNotFound($this->firstScalarValue($raw, ['org', 'organization', 'organisation', 'owner'])),
            'country' => $this->stringOrNotFound($this->extractLocationScalar($raw, ['country', 'country_name', 'countrycode', 'country_code'])),
            'city' => $this->stringOrNotFound($this->extractLocationScalar($raw, ['city'])),
            'region' => $this->stringOrNotFound($this->extractLocationScalar($raw, ['region', 'region_name', 'region_code', 'state', 'province'])),
            'latitude' => $this->stringOrNotFound($this->extractLocationScalar($raw, ['latitude', 'lat'])),
            'longitude' => $this->stringOrNotFound($this->extractLocationScalar($raw, ['longitude', 'lon', 'lng'])),
            'network' => $this->stringOrNotFound($this->firstScalarValue($raw, ['network', 'cidr', 'netblock', 'network_range'])),
            'services' => $this->extractServices($raw),
            'technologies' => $this->extractTechnologies($raw),
            'operating_system' => $this->stringOrNotFound($this->firstScalarValue($raw, ['operating_system', 'os', 'os_name', 'platform'])),
            'certificates' => $this->extractCertificates($raw),
            'vulnerabilities' => $this->extractVulnerabilities($raw),
            'reputation' => [
                'score' => $this->stringOrNotFound($this->extractReputationScore($raw)),
                'tags' => $this->extractStringList($raw, ['tags', 'tag', 'labels', 'reputation_tags']),
            ],
            'metadata' => [
                'source' => $this->stringOrNotFound(
                    $source ?? $this->firstScalarValue($raw, ['source', '_source', 'provider'])
                ),
                'collected_at' => $this->stringOrNotFound(
                    $collectedAt ?? $this->firstScalarValue($raw, ['collected_at', 'collected', 'fetched_at', 'scanned_at', 'observed_at'])
                ),
            ],
        ];
    }

    private function extractServices(array $raw): array
    {
        $services = [];

        foreach ($this->findServiceObjects($raw) as $candidate) {
            $services[] = [
                'port' => $this->stringOrNotFound($this->firstScalarValue($candidate, ['port'])),
                'protocol' => $this->stringOrNotFound($this->firstScalarValue($candidate, ['protocol', 'transport'])),
                'service' => $this->stringOrNotFound($this->firstScalarValue($candidate, ['service', 'service_name', 'name'])),
                'state' => $this->stringOrNotFound($this->firstScalarValue($candidate, ['state', 'status'])),
                'product' => $this->stringOrNotFound($this->firstScalarValue($candidate, ['product', 'software'])),
                'version' => $this->stringOrNotFound($this->firstScalarValue($candidate, ['version'])),
                'banner' => $this->stringOrNotFound($this->firstScalarValue($candidate, ['banner', 'data', 'response'])),
            ];
        }

        $knownPorts = array_filter(array_map(
            fn (array $service) => $service['port'] !== 'Not Found' ? $service['port'] : null,
            $services
        ));

        foreach ($this->extractStringList($raw, ['ports', 'open_ports']) as $port) {
            if (in_array($port, $knownPorts, true)) {
                continue;
            }

            $services[] = [
                'port' => $port,
                'protocol' => 'Not Found',
                'service' => 'Not Found',
                'state' => 'Not Found',
                'product' => 'Not Found',
                'version' => 'Not Found',
                'banner' => 'Not Found',
            ];
        }

        $unique = [];
        foreach ($services as $service) {
            $key = json_encode($service);
            $unique[$key] = $service;
        }

        return array_values($unique);
    }

    private function extractTechnologies(array $raw): array
    {
        return $this->extractStringList($raw, [
            'technologies',
            'technology',
            'tech',
            'stack',
            'products',
            'cpes',
            'cpe',
            'frameworks',
        ]);
    }

    private function extractCertificates(array $raw): array
    {
        $certificates = [];

        foreach ($this->findCertificateObjects($raw) as $candidate) {
            $certificates[] = [
                'subject' => $this->stringOrNotFound($this->firstValueAsString($candidate, ['subject', 'subject_dn', 'subjectdn'])),
                'issuer' => $this->stringOrNotFound($this->firstValueAsString($candidate, ['issuer', 'issuer_dn', 'issuerdn'])),
                'valid_from' => $this->stringOrNotFound($this->firstScalarValue($candidate, ['valid_from', 'not_before', 'issued_at', 'issued'])),
                'valid_to' => $this->stringOrNotFound($this->firstScalarValue($candidate, ['valid_to', 'not_after', 'expires_at', 'expires'])),
                'fingerprint' => $this->stringOrNotFound($this->firstScalarValue($candidate, ['fingerprint', 'sha1', 'sha256'])),
            ];
        }

        $unique = [];
        foreach ($certificates as $certificate) {
            $key = json_encode($certificate);
            $unique[$key] = $certificate;
        }

        return array_values($unique);
    }

    private function extractVulnerabilities(array $raw): array
    {
        $vulnerabilities = [];

        foreach ($this->extractVulnerabilityContainers($raw) as $container) {
            if (is_array($container) && array_is_list($container)) {
                foreach ($container as $item) {
                    if (is_string($item) && $this->looksLikeCve($item)) {
                        $vulnerabilities[] = $this->normalizeVulnerability(['cve' => $item]);
                    }

                    if (is_array($item)) {
                        $vulnerabilities[] = $this->normalizeVulnerability($item);
                    }
                }

                continue;
            }

            if (!is_array($container)) {
                continue;
            }

            foreach ($container as $key => $value) {
                if (is_string($key) && $this->looksLikeCve($key)) {
                    $payload = is_array($value) ? $value : [];
                    $payload['cve'] = $payload['cve'] ?? $key;
                    $vulnerabilities[] = $this->normalizeVulnerability($payload);
                    continue;
                }

                if (is_array($value) && $this->firstScalarValue($value, ['cve', 'cve_id', 'id'])) {
                    $vulnerabilities[] = $this->normalizeVulnerability($value);
                }
            }
        }

        foreach ($this->findObjectsWithKeys($raw, ['cve', 'cve_id']) as $candidate) {
            $vulnerabilities[] = $this->normalizeVulnerability($candidate);
        }

        $unique = [];
        foreach ($vulnerabilities as $vulnerability) {
            if ($vulnerability['cve'] === 'Not Found') {
                continue;
            }

            $key = json_encode($vulnerability);
            $unique[$key] = $vulnerability;
        }

        return array_values($unique);
    }

    private function normalizeVulnerability(array $candidate): array
    {
        return [
            'cve' => $this->stringOrNotFound($this->firstScalarValue($candidate, ['cve', 'cve_id', 'id'])),
            'severity' => $this->stringOrNotFound($this->firstScalarValue($candidate, ['severity', 'cvss_severity', 'cvss_rank'])),
            'cvss' => $this->stringOrNotFound($this->firstScalarValue($candidate, ['cvss', 'cvss_score', 'score'])),
            'description' => $this->stringOrNotFound($this->firstScalarValue($candidate, ['description', 'summary', 'details'])),
        ];
    }

    private function extractReputationScore(array $raw): string|int|float|null
    {
        foreach ($this->findObjectsWithKeys($raw, ['reputation', 'abuse_confidence_score', 'threat_score']) as $candidate) {
            if (is_array($candidate)) {
                $score = $this->firstScalarValue($candidate, ['score', 'reputation_score', 'abuse_confidence_score', 'threat_score']);
                if ($score !== null) {
                    return $score;
                }
            }
        }

        return $this->firstScalarValue($raw, ['reputation_score', 'abuse_confidence_score', 'threat_score']);
    }

    private function extractLocationScalar(array $raw, array $aliases): string|int|float|bool|null
    {
        $topLevel = $this->firstDirectScalarValue($raw, $aliases);

        if ($topLevel !== null) {
            return $topLevel;
        }

        foreach (['location', 'geo', 'geolocation', 'address', 'coordinates'] as $containerAlias) {
            foreach ($this->collectValuesByAliases($raw, [$containerAlias]) as $value) {
                if (!is_array($value)) {
                    continue;
                }

                $nested = $this->firstScalarValue($value, $aliases);

                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return null;
    }

    private function extractStringList(array $raw, array $aliases): array
    {
        $values = [];

        foreach ($this->collectValuesByAliases($raw, $aliases) as $value) {
            foreach ($this->flattenToStrings($value) as $string) {
                $values[] = $string;
            }
        }

        $values = array_values(array_unique(array_filter($values, fn (string $value) => $value !== '')));

        return array_values($values);
    }

    private function findServiceObjects(array $data): array
    {
        $results = [];

        if ($this->isAssoc($data) && $this->containsAnyAlias($data, ['port', 'protocol', 'transport', 'service', 'service_name', 'product', 'version', 'banner', 'data', 'response'])) {
            $results[] = $data;
        }

        foreach ($data as $value) {
            if (!is_array($value)) {
                continue;
            }

            if (array_is_list($value)) {
                foreach ($value as $item) {
                    if (is_array($item)) {
                        $results = array_merge($results, $this->findServiceObjects($item));
                    }
                }

                continue;
            }

            $results = array_merge($results, $this->findServiceObjects($value));
        }

        return $results;
    }

    private function findCertificateObjects(array $data): array
    {
        return $this->findObjectsWithKeys($data, [
            'subject',
            'subject_dn',
            'issuer',
            'issuer_dn',
            'fingerprint',
            'sha1',
            'sha256',
            'valid_from',
            'valid_to',
            'not_before',
            'not_after',
        ]);
    }

    private function extractVulnerabilityContainers(array $data): array
    {
        return $this->collectValuesByAliases($data, ['vulns', 'vulnerabilities', 'cves']);
    }

    private function findObjectsWithKeys(array $data, array $aliases): array
    {
        $results = [];

        if ($this->isAssoc($data) && $this->containsAnyAlias($data, $aliases)) {
            $results[] = $data;
        }

        foreach ($data as $value) {
            if (is_array($value)) {
                $results = array_merge($results, $this->findObjectsWithKeys($value, $aliases));
            }
        }

        return $results;
    }

    private function collectValuesByAliases(array $data, array $aliases): array
    {
        $results = [];

        foreach ($data as $key => $value) {
            if (is_string($key) && $this->aliasMatches($key, $aliases)) {
                $results[] = $value;
            }

            if (is_array($value)) {
                $results = array_merge($results, $this->collectValuesByAliases($value, $aliases));
            }
        }

        return $results;
    }

    private function firstScalarValue(array $data, array $aliases): string|int|float|bool|null
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && $this->aliasMatches($key, $aliases) && is_scalar($value)) {
                return $value;
            }

            if (is_array($value)) {
                $nested = $this->firstScalarValue($value, $aliases);
                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return null;
    }

    private function firstDirectScalarValue(array $data, array $aliases): string|int|float|bool|null
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && $this->aliasMatches($key, $aliases) && is_scalar($value)) {
                return $value;
            }
        }

        return null;
    }

    private function firstValueAsString(array $data, array $aliases): ?string
    {
        foreach ($data as $key => $value) {
            if (is_string($key) && $this->aliasMatches($key, $aliases)) {
                return $this->valueToString($value);
            }

            if (is_array($value)) {
                $nested = $this->firstValueAsString($value, $aliases);
                if ($nested !== null) {
                    return $nested;
                }
            }
        }

        return null;
    }

    private function flattenToStrings(mixed $value): array
    {
        if (is_scalar($value)) {
            return [$this->valueToString($value)];
        }

        if (!is_array($value)) {
            return [];
        }

        $strings = [];

        foreach ($value as $item) {
            $strings = array_merge($strings, $this->flattenToStrings($item));
        }

        return $strings;
    }

    private function valueToString(mixed $value): string
    {
        if (is_bool($value)) {
            return $value ? 'true' : 'false';
        }

        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE) ?: 'Not Found';
        }

        return trim((string) $value);
    }

    private function stringOrNotFound(mixed $value): string
    {
        if ($value === null) {
            return 'Not Found';
        }

        $string = $this->valueToString($value);

        return $string !== '' ? $string : 'Not Found';
    }

    private function containsAnyAlias(array $data, array $aliases): bool
    {
        foreach (array_keys($data) as $key) {
            if (is_string($key) && $this->aliasMatches($key, $aliases)) {
                return true;
            }
        }

        return false;
    }

    private function aliasMatches(string $key, array $aliases): bool
    {
        $normalized = $this->normalizeKey($key);

        foreach ($aliases as $alias) {
            if ($normalized === $this->normalizeKey($alias)) {
                return true;
            }
        }

        return false;
    }

    private function normalizeKey(string $value): string
    {
        return strtolower((string) preg_replace('/[^a-z0-9]/', '', $value));
    }

    private function looksLikeCve(string $value): bool
    {
        return (bool) preg_match('/^CVE-\d{4}-\d+$/i', trim($value));
    }

    private function isAssoc(array $value): bool
    {
        return !array_is_list($value);
    }
}
