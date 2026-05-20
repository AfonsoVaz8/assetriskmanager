<?php

namespace App\Services;

use App\Models\AssetThreat;
use App\Models\DiscoveredHost;
use App\Models\DiscoveredHostFinding;
use App\Models\Threat;

class DiscoveredHostThreatSyncService
{
    public function sync(DiscoveredHost $host): void
    {
        $host->loadMissing(['asset', 'scope', 'findings']);

        if (!$host->asset) {
            return;
        }

        $rules = data_get($host->scope?->settings, 'threat_rules', []);

        if (!data_get($rules, 'enabled', false)) {
            $this->cleanupHostThreats($host);
            return;
        }

        $activeSourceKeys = [];

        foreach ($host->findings->where('active', true) as $finding) {
            $configuration = $this->configurationForFinding($rules, $finding->kind);

            if (!$configuration['enabled']) {
                continue;
            }

            if (!$this->matchesRule($finding, $configuration)) {
                continue;
            }

            $sourceKey = $this->buildThreatSourceKey($host, $finding);
            $activeSourceKeys[] = $sourceKey;

            $threat = $this->resolveThreatModel($finding->kind);

            AssetThreat::query()->updateOrCreate(
                [
                    'asset_id' => $host->asset_id,
                    'source' => 'attack_surface',
                    'source_key' => $sourceKey,
                ],
                [
                    'threat_id' => $threat->id,
                    'probability' => $configuration['probability'],
                    'availability_impact' => $configuration['availability_impact'],
                    'integrity_impact' => $configuration['integrity_impact'],
                    'confidentiality_impact' => $configuration['confidentiality_impact'],
                    'auto_generated' => true,
                    'source_context' => [
                        'discovered_host_id' => $host->id,
                        'finding_id' => $finding->id,
                        'finding_kind' => $finding->kind,
                        'finding_title' => $finding->title,
                        'finding_description' => $finding->description,
                        'finding_source' => $finding->source,
                        'finding_source_key' => $finding->source_key,
                        'finding_severity' => $finding->severity,
                        'reason' => $this->reasonForFinding($finding),
                    ] + ($finding->context ?? []),
                ]
            );
        }

        $this->cleanupHostThreats($host, $activeSourceKeys);
    }

    private function configurationForFinding(array $rules, string $kind): array
    {
        $config = data_get($rules, $kind, []);

        return [
            'enabled' => (bool) data_get($config, 'enabled', false),
            'only_if_not_allowed' => (bool) data_get($config, 'only_if_not_allowed', true),
            'min_cvss' => is_numeric(data_get($config, 'min_cvss'))
                ? (float) data_get($config, 'min_cvss')
                : null,
            'min_severity' => (string) data_get($config, 'min_severity', ''),
            'probability' => $this->normalizeRiskValue(data_get($config, 'probability', $kind === 'cve_detected' ? 4 : 3)),
            'availability_impact' => $this->normalizeRiskValue(data_get($config, 'availability_impact', $kind === 'cve_detected' ? 3 : ($kind === 'web_issue' ? 2 : 2))),
            'integrity_impact' => $this->normalizeRiskValue(data_get($config, 'integrity_impact', $kind === 'cve_detected' ? 4 : ($kind === 'web_issue' ? 3 : 3))),
            'confidentiality_impact' => $this->normalizeRiskValue(data_get($config, 'confidentiality_impact', 3)),
        ];
    }

    private function matchesRule(DiscoveredHostFinding $finding, array $configuration): bool
    {
        return match ($finding->kind) {
            'open_port' => $this->matchesOpenPortRule($finding, $configuration),
            'cve_detected' => $this->matchesCveRule($finding, $configuration),
            'web_issue' => $this->matchesWebIssueRule($finding, $configuration),
            default => false,
        };
    }

    private function matchesOpenPortRule(DiscoveredHostFinding $finding, array $configuration): bool
    {
        if (!$configuration['only_if_not_allowed']) {
            return true;
        }

        return data_get($finding->context, 'allowed_by_asset_policy') !== true;
    }

    private function matchesCveRule(DiscoveredHostFinding $finding, array $configuration): bool
    {
        $minCvss = $configuration['min_cvss'];
        $cvss = data_get($finding->context, 'cvss');

        if ($minCvss !== null) {
            if (!is_numeric($cvss) || (float) $cvss < $minCvss) {
                return false;
            }
        }

        $minSeverity = strtolower(trim($configuration['min_severity']));

        if ($minSeverity !== '') {
            $severity = strtolower((string) data_get($finding->context, 'severity', $finding->severity));

            if ($this->severityRank($severity) < $this->severityRank($minSeverity)) {
                return false;
            }
        }

        return true;
    }

    private function matchesWebIssueRule(DiscoveredHostFinding $finding, array $configuration): bool
    {
        $minSeverity = strtolower(trim($configuration['min_severity']));

        if ($minSeverity === '') {
            return true;
        }

        $severity = strtolower((string) data_get($finding->context, 'severity', $finding->severity));

        return $this->severityRank($severity) >= $this->severityRank($minSeverity);
    }

    private function severityRank(string $severity): int
    {
        return match ($severity) {
            'informational', 'info' => 1,
            'low' => 2,
            'medium', 'moderate' => 3,
            'high' => 4,
            'critical' => 5,
            default => 0,
        };
    }

    private function resolveThreatModel(string $kind): Threat
    {
        return match ($kind) {
            'open_port' => Threat::query()->firstOrCreate(
                ['name' => 'Detected External Unexpected Open Port'],
                ['description' => 'An externally discovered host exposed a port that matches the configured threat classification rules.']
            ),
            'cve_detected' => Threat::query()->firstOrCreate(
                ['name' => 'Detected External Vulnerability Exposure'],
                ['description' => 'An externally discovered host exposed a vulnerability that matches the configured threat classification rules.']
            ),
            'web_issue' => Threat::query()->firstOrCreate(
                ['name' => 'Detected External Web Exposure'],
                ['description' => 'An externally discovered web service exposed a web scanner finding that matches the configured threat classification rules.']
            ),
            default => Threat::query()->firstOrCreate(
                ['name' => 'Detected External Security Finding'],
                ['description' => 'An externally discovered host produced a security finding that was classified as a threat.']
            ),
        };
    }

    private function buildThreatSourceKey(DiscoveredHost $host, DiscoveredHostFinding $finding): string
    {
        return sprintf(
            'discovered_host:%d:%s:%s',
            $host->id,
            $finding->kind,
            $finding->source_key
        );
    }

    private function cleanupHostThreats(DiscoveredHost $host, array $activeSourceKeys = []): void
    {
        $query = AssetThreat::query()
            ->where('asset_id', $host->asset_id)
            ->where('source', 'attack_surface')
            ->where('auto_generated', true)
            ->where('source_key', 'like', 'discovered_host:'.$host->id.':%');

        if ($activeSourceKeys !== []) {
            $query->whereNotIn('source_key', $activeSourceKeys);
        }

        $query->delete();
    }

    private function reasonForFinding(DiscoveredHostFinding $finding): string
    {
        return $finding->description ?: $finding->title;
    }

    private function normalizeRiskValue(mixed $value): int
    {
        $value = (int) $value;

        return max(1, min(5, $value));
    }
}
