<?php

namespace App\Services;

use App\Models\AssetType;
use App\Models\DiscoveredHost;
use Illuminate\Support\Collection;

class AssetTypeInferenceService
{
    public function infer(DiscoveredHost $host): array
    {
        $signals = $this->buildSignals($host);
        $scores = [];
        $reasons = [];

        $this->scoreRouter($signals, $scores, $reasons);
        $this->scoreSwitch($signals, $scores, $reasons);
        $this->scoreNas($signals, $scores, $reasons);
        $this->scoreIpCamera($signals, $scores, $reasons);
        $this->scoreRecorder($signals, $scores, $reasons);
        $this->scoreServer($signals, $scores, $reasons);
        $this->scoreWorkstation($signals, $scores, $reasons);

        $bestType = collect($scores)
            ->sortDesc()
            ->keys()
            ->first();

        $bestScore = (int) ($bestType ? ($scores[$bestType] ?? 0) : 0);

        if (!$bestType || $bestScore <= 0) {
            $bestType = 'Other';
            $bestScore = 1;
            $reasons[$bestType] = [__('No strong fingerprint matched a more specific asset type, so the host is treated as Other until reviewed.')];
        }

        $assetType = AssetType::query()
            ->whereRaw('LOWER(name) = ?', [strtolower($bestType)])
            ->first();

        if (!$assetType) {
            $assetType = AssetType::query()
                ->whereRaw('LOWER(name) = ?', ['other'])
                ->first();
        }

        return [
            'asset_type' => $assetType,
            'asset_type_name' => $assetType?->name ?? $bestType,
            'confidence' => $this->confidenceForScore($bestScore),
            'score' => $bestScore,
            'reasons' => array_values(array_unique($reasons[$bestType] ?? [])),
            'all_scores' => $scores,
        ];
    }

    private function buildSignals(DiscoveredHost $host): array
    {
        $host->loadMissing('enrichmentRuns');

        $payloads = $host->enrichmentRuns
            ->where('status', 'synced')
            ->pluck('normalized_payload')
            ->filter(fn ($payload) => is_array($payload))
            ->values();

        if ($payloads->isEmpty() && is_array($host->normalized_payload)) {
            $payloads = collect([$host->normalized_payload]);
        }

        $services = $payloads
            ->flatMap(fn (array $payload) => data_get($payload, 'services', []))
            ->filter(fn ($service) => is_array($service))
            ->values();

        $ports = collect($host->open_ports ?? [])
            ->merge($services->pluck('port'))
            ->map(fn ($port) => (int) $port)
            ->filter(fn (int $port) => $port > 0)
            ->unique()
            ->values();

        $serviceText = $services
            ->map(function (array $service) {
                return implode(' ', array_filter([
                    strtolower(trim((string) data_get($service, 'service', ''))),
                    strtolower(trim((string) data_get($service, 'product', ''))),
                    strtolower(trim((string) data_get($service, 'version', ''))),
                    strtolower(trim((string) data_get($service, 'banner', ''))),
                ]));
            })
            ->implode(' ');

        $technologyText = $payloads
            ->flatMap(fn (array $payload) => data_get($payload, 'technologies', []))
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value) => strtolower(trim($value)))
            ->implode(' ');

        $hostnameText = $payloads
            ->flatMap(fn (array $payload) => data_get($payload, 'hostnames', []))
            ->prepend($host->fqdn)
            ->filter(fn ($value) => is_string($value) && trim($value) !== '')
            ->map(fn (string $value) => strtolower(trim($value)))
            ->implode(' ');

        $operatingSystem = strtolower(trim((string) $payloads
            ->map(fn (array $payload) => (string) data_get($payload, 'operating_system', ''))
            ->first(fn ($value) => trim((string) $value) !== '' && trim((string) $value) !== 'Not Found')));

        return [
            'ports' => $ports,
            'ports_lookup' => array_flip($ports->all()),
            'text' => trim($serviceText.' '.$technologyText.' '.$hostnameText.' '.$operatingSystem),
            'services' => $services,
            'operating_system' => $operatingSystem,
        ];
    }

    private function scoreRouter(array $signals, array &$scores, array &$reasons): void
    {
        if ($this->textMatchesAny($signals['text'], ['router', 'mikrotik', 'edgeos', 'openwrt', 'fortigate', 'pfsense', 'sophos', 'junos', 'palo alto', 'checkpoint'])) {
            $this->addScore($scores, $reasons, 'Router', 4, __('Router/firewall product keywords were observed in the service or technology fingerprint.'));
        }

        if ($this->hasAnyPort($signals, [8291, 8728, 8729, 179])) {
            $this->addScore($scores, $reasons, 'Router', 2, __('Ports commonly associated with routers or network gateways were exposed.'));
        }
    }

    private function scoreSwitch(array $signals, array &$scores, array &$reasons): void
    {
        if ($this->textMatchesAny($signals['text'], ['switch', 'aruba', 'procurve', 'catalyst', 'juniper ex'])) {
            $this->addScore($scores, $reasons, 'Switch', 4, __('Switch-oriented product keywords were observed in the service or technology fingerprint.'));
        }

        if ($this->hasAnyPort($signals, [161, 162]) && $this->textMatchesAny($signals['text'], ['snmp', 'switch'])) {
            $this->addScore($scores, $reasons, 'Switch', 2, __('SNMP exposure combined with switch-oriented service hints was observed.'));
        }
    }

    private function scoreNas(array $signals, array &$scores, array &$reasons): void
    {
        if ($this->textMatchesAny($signals['text'], ['nas', 'synology', 'qnap', 'truenas', 'freenas', 'openmediavault'])) {
            $this->addScore($scores, $reasons, 'NAS', 5, __('NAS/storage product keywords were observed in the enrichment fingerprint.'));
        }

        if ($this->hasAnyPort($signals, [111, 139, 445, 2049, 5000, 5001])) {
            $this->addScore($scores, $reasons, 'NAS', 2, __('Ports commonly associated with file sharing or NAS management were exposed.'));
        }
    }

    private function scoreIpCamera(array $signals, array &$scores, array &$reasons): void
    {
        if ($this->textMatchesAny($signals['text'], ['ip camera', 'network camera', 'camera', 'hikvision', 'dahua', 'axis', 'onvif'])) {
            $this->addScore($scores, $reasons, 'IP Camera', 5, __('Camera-specific product keywords were observed in the enrichment fingerprint.'));
        }

        if ($this->hasAnyPort($signals, [554, 8554]) && $this->textMatchesAny($signals['text'], ['rtsp', 'camera', 'video'])) {
            $this->addScore($scores, $reasons, 'IP Camera', 2, __('RTSP/video exposure suggests the host may be an IP camera.'));
        }
    }

    private function scoreRecorder(array $signals, array &$scores, array &$reasons): void
    {
        if ($this->textMatchesAny($signals['text'], ['nvr', 'dvr', 'xmeye'])) {
            $type = $this->textMatchesAny($signals['text'], ['dvr']) ? 'DVR' : 'NVR';
            $this->addScore($scores, $reasons, $type, 5, __('Recorder-oriented product keywords were observed in the enrichment fingerprint.'));
        }

        if ($this->hasAnyPort($signals, [37777, 37778])) {
            $this->addScore($scores, $reasons, 'NVR', 2, __('Ports commonly used by video recorder products were exposed.'));
        }
    }

    private function scoreServer(array $signals, array &$scores, array &$reasons): void
    {
        if ($this->textMatchesAny($signals['text'], [
            'apache', 'nginx', 'iis', 'mysql', 'mariadb', 'postgres', 'postgresql', 'mssql', 'mongodb',
            'redis', 'ftp', 'smtp', 'imap', 'pop3', 'ssh', 'rdp', 'docker', 'kubernetes', 'jenkins',
            'gitlab', 'bitbucket', 'elasticsearch', 'kafka', 'openvpn', 'wireguard', 'rabbitmq'
        ])) {
            $this->addScore($scores, $reasons, 'Server', 4, __('Server or application-service fingerprints were observed in the enrichment data.'));
        }

        if ($this->hasAnyPort($signals, [22, 25, 80, 110, 143, 443, 465, 587, 993, 995, 1433, 3306, 3389, 5432, 6379, 8080, 8443, 9200])) {
            $this->addScore($scores, $reasons, 'Server', 2, __('Ports typically associated with externally exposed servers were observed.'));
        }
    }

    private function scoreWorkstation(array $signals, array &$scores, array &$reasons): void
    {
        if ($this->textMatchesAny($signals['operating_system'], ['windows']) && $this->hasAnyPort($signals, [3389]) && !$this->hasAnyPort($signals, [80, 443, 25, 1433, 3306, 5432])) {
            $this->addScore($scores, $reasons, 'Workstation', 3, __('Windows with RDP exposure and without stronger server-oriented signals suggests a workstation-like host.'));
        }
    }

    private function addScore(array &$scores, array &$reasons, string $type, int $score, string $reason): void
    {
        $scores[$type] = ($scores[$type] ?? 0) + $score;
        $reasons[$type] ??= [];
        $reasons[$type][] = $reason;
    }

    private function hasAnyPort(array $signals, array $ports): bool
    {
        foreach ($ports as $port) {
            if (isset($signals['ports_lookup'][$port])) {
                return true;
            }
        }

        return false;
    }

    private function textMatchesAny(string $text, array $needles): bool
    {
        foreach ($needles as $needle) {
            if ($needle !== '' && str_contains($text, strtolower($needle))) {
                return true;
            }
        }

        return false;
    }

    private function confidenceForScore(int $score): string
    {
        return match (true) {
            $score >= 6 => 'high',
            $score >= 3 => 'medium',
            default => 'low',
        };
    }
}
