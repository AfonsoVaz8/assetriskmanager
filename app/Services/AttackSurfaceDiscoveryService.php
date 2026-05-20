<?php

namespace App\Services;

use App\Enums\AssetOperationType;
use App\Enums\AttackSurfaceRunStatus;
use App\Enums\AttackSurfaceScopeStatus;
use App\Enums\AttackSurfaceScopeType;
use App\Models\Asset;
use App\Models\AssetLog;
use App\Models\AssetType;
use App\Models\AttackSurfaceRun;
use App\Models\AttackSurfaceScope;
use App\Models\DiscoveredHost;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use InvalidArgumentException;
use RuntimeException;

class AttackSurfaceDiscoveryService
{
    private const DEFAULT_PORTS = [80, 443, 22];
    private const MAX_TARGETS = 256;
    private const MAX_DOMAIN_HOSTNAMES = 200;

    public function __construct(
        private readonly SafeHostDiscoveryProbe $probe,
        private readonly IpIntelligenceNormalizer $normalizer,
    ) {
    }

    public function createRun(AttackSurfaceScope $scope): AttackSurfaceRun
    {
        $config = $this->buildRunConfig($scope);
        $targets = $this->resolveTargets($scope);

        if (empty($targets)) {
            throw new RuntimeException('The selected discovery scope does not contain any eligible IP targets.');
        }

        return $scope->runs()->create([
            'status' => AttackSurfaceRunStatus::QUEUED,
            'strategy' => 'safe_tcp_discovery',
            'target_count' => count($targets),
            'config_snapshot' => array_merge($config, [
                'resolved_targets' => $targets,
            ]),
        ]);
    }

    public function executeRun(AttackSurfaceRun $run): AttackSurfaceRun
    {
        $run->loadMissing('scope.submittedBy');
        $scope = $run->scope;

        if (!$scope || $scope->status !== AttackSurfaceScopeStatus::APPROVED) {
            throw new RuntimeException('Only approved discovery scopes can be executed.');
        }

        $targets = Arr::get($run->config_snapshot, 'resolved_targets');

        if (!is_array($targets) || $targets === []) {
            $targets = $this->resolveTargets($scope);
        }
        $config = $this->buildRunConfig($scope);
        $ports = Arr::get($config, 'ports', self::DEFAULT_PORTS);
        $timeout = (float) Arr::get($config, 'timeout_seconds', 1.0);

        $activeHosts = 0;
        $createdAssets = 0;
        $errors = 0;

        $run->forceFill([
            'status' => AttackSurfaceRunStatus::RUNNING,
            'started_at' => now(),
            'finished_at' => null,
            'error' => null,
            'target_count' => count($targets),
        ])->save();

        foreach ($targets as $target) {
            try {
                $result = $this->probe->probe($target['ip_address'], $ports, $timeout);
                $asset = Asset::query()->where('ip_address', $target['ip_address'])->first();
                $wasAutoCreated = false;

                if (!$asset && $result['status'] === 'active' && Arr::get($config, 'auto_create_assets', false)) {
                    $asset = $this->createAutoDiscoveredAsset($scope, $target['ip_address']);
                    $wasAutoCreated = $asset !== null;
                    $createdAssets += $wasAutoCreated ? 1 : 0;
                }

                $reverseDns = @gethostbyaddr($target['ip_address']);
                $fqdn = $target['fqdn'] ?? ($reverseDns && $reverseDns !== $target['ip_address'] ? $reverseDns : null);
                $hostnames = collect($target['hostnames'] ?? [])
                    ->filter(fn ($hostname) => is_string($hostname) && trim($hostname) !== '')
                    ->map(fn (string $hostname) => trim($hostname))
                    ->unique()
                    ->values()
                    ->all();

                if ($fqdn && !in_array($fqdn, $hostnames, true)) {
                    array_unshift($hostnames, $fqdn);
                }

                $normalized = $this->normalizer->normalize(
                    ip: $target['ip_address'],
                    raw: [
                        'ip' => $target['ip_address'],
                        'hostnames' => $hostnames,
                        'ports' => $result['open_ports'],
                    ],
                    source: 'Safe TCP Probe',
                    collectedAt: now()->toIso8601String(),
                );

                DiscoveredHost::query()->updateOrCreate(
                    [
                        'attack_surface_run_id' => $run->id,
                        'ip_address' => $target['ip_address'],
                    ],
                    [
                        'attack_surface_scope_id' => $scope->id,
                        'asset_id' => $asset?->id,
                        'fqdn' => $fqdn,
                        'status' => $result['status'],
                        'origin' => $target['origin'],
                        'discovery_method' => 'safe_tcp_probe',
                        'open_ports' => $result['open_ports'],
                        'was_auto_created' => $wasAutoCreated,
                        'first_seen_at' => now(),
                        'last_seen_at' => now(),
                        'error' => $result['error'],
                        'raw_payload' => [
                            'ip' => $target['ip_address'],
                            'hostnames' => $hostnames,
                            'open_ports' => $result['open_ports'],
                            'source' => 'safe_tcp_probe',
                        ],
                        'normalized_payload' => $normalized,
                    ]
                );

                if ($result['status'] === 'active') {
                    $activeHosts++;
                }
            } catch (\Throwable $exception) {
                $errors++;

                DiscoveredHost::query()->updateOrCreate(
                    [
                        'attack_surface_run_id' => $run->id,
                        'ip_address' => $target['ip_address'],
                    ],
                    [
                        'attack_surface_scope_id' => $scope->id,
                        'fqdn' => $target['fqdn'] ?? null,
                        'status' => 'error',
                        'origin' => $target['origin'],
                        'discovery_method' => 'safe_tcp_probe',
                        'error' => $exception->getMessage(),
                        'last_seen_at' => now(),
                        'raw_payload' => [
                            'ip' => $target['ip_address'],
                            'hostnames' => $target['hostnames'] ?? [],
                            'source' => 'safe_tcp_probe',
                        ],
                        'normalized_payload' => null,
                    ]
                );
            }
        }

        $finalStatus = $errors > 0
            ? AttackSurfaceRunStatus::PARTIAL
            : AttackSurfaceRunStatus::COMPLETED;

        $run->forceFill([
            'status' => $finalStatus,
            'active_host_count' => $activeHosts,
            'created_asset_count' => $createdAssets,
            'error_count' => $errors,
            'finished_at' => now(),
            'error' => $errors > 0 ? 'One or more targets could not be processed.' : null,
        ])->save();

        $scope->forceFill(['last_run_at' => now()])->save();

        return $run->fresh(['scope', 'discoveredHosts']);
    }

    public function resolveTargets(AttackSurfaceScope $scope): array
    {
        return match ($scope->type) {
            AttackSurfaceScopeType::REGISTERED_ASSETS => $this->resolveRegisteredAssetTargets(),
            AttackSurfaceScopeType::CIDR_RANGE => $this->resolveCidrTargets($scope),
            AttackSurfaceScopeType::HOSTNAME_TARGET => $this->resolveHostnameTargets($scope),
            AttackSurfaceScopeType::DOMAIN_EXPANSION => $this->resolveDomainExpansionTargets($scope),
        };
    }

    private function resolveRegisteredAssetTargets(): array
    {
        return Asset::query()
            ->whereNotNull('ip_address')
            ->where('ip_address', '!=', '')
            ->get(['ip_address'])
            ->map(fn (Asset $asset) => [
                'ip_address' => $asset->ip_address,
                'origin' => 'registered_asset',
                'hostnames' => [],
            ])
            ->unique('ip_address')
            ->values()
            ->all();
    }

    private function resolveCidrTargets(AttackSurfaceScope $scope): array
    {
        $cidr = (string) Arr::get($scope->scope_definition, 'cidr');

        if (!$this->isValidIpv4Cidr($cidr)) {
            throw new InvalidArgumentException('Only valid IPv4 CIDR ranges are supported in the current discovery phase.');
        }

        [$network, $prefix] = explode('/', $cidr);
        $prefix = (int) $prefix;
        $mask = -1 << (32 - $prefix);
        $networkLong = ip2long($network);
        $start = $networkLong & $mask;
        $hostCount = 1 << (32 - $prefix);

        if ($hostCount > self::MAX_TARGETS) {
            throw new InvalidArgumentException('The provided CIDR range is too large for the current safe discovery limits.');
        }

        $targets = [];
        $end = $start + $hostCount - 1;

        for ($current = $start; $current <= $end; $current++) {
            $ip = long2ip($current);

            if ($prefix <= 30 && ($current === $start || $current === $end)) {
                continue;
            }

            $targets[] = [
                'ip_address' => $ip,
                'origin' => 'range_discovery',
                'hostnames' => [],
            ];
        }

        return $targets;
    }

    private function resolveHostnameTargets(AttackSurfaceScope $scope): array
    {
        $hostname = trim((string) Arr::get($scope->scope_definition, 'hostname'));

        if (!$this->isValidHostname($hostname)) {
            throw new InvalidArgumentException('A valid hostname or domain is required for hostname discovery scopes.');
        }

        $addresses = collect($this->resolveHostnameIpAddresses($hostname));

        if ($addresses->isEmpty()) {
            throw new RuntimeException('The provided hostname/domain did not resolve to any IP addresses.');
        }

        if ($addresses->count() > self::MAX_TARGETS) {
            throw new InvalidArgumentException('The provided hostname/domain resolved to too many IPs for the current safe discovery limits.');
        }

        return $addresses
            ->map(fn (string $ip) => [
                'ip_address' => $ip,
                'origin' => 'hostname_discovery',
                'fqdn' => $hostname,
                'hostnames' => [$hostname],
            ])
            ->all();
    }

    private function resolveDomainExpansionTargets(AttackSurfaceScope $scope): array
    {
        $domain = trim((string) Arr::get($scope->scope_definition, 'domain'));

        if (!$this->isValidHostname($domain)) {
            throw new InvalidArgumentException('A valid base domain is required for domain expansion scopes.');
        }

        $hostnames = $this->enumerateSubdomains($domain);

        if ($hostnames === []) {
            throw new RuntimeException('No subdomains could be discovered for the provided domain.');
        }

        if (count($hostnames) > self::MAX_DOMAIN_HOSTNAMES) {
            $hostnames = array_slice($hostnames, 0, self::MAX_DOMAIN_HOSTNAMES);
        }

        $targetsByIp = [];

        foreach ($hostnames as $hostname) {
            foreach ($this->resolveHostnameIpAddresses($hostname) as $ipAddress) {
                $targetsByIp[$ipAddress] ??= [
                    'ip_address' => $ipAddress,
                    'origin' => 'domain_expansion',
                    'fqdn' => $hostname,
                    'hostnames' => [],
                ];

                $targetsByIp[$ipAddress]['hostnames'][] = $hostname;
            }
        }

        if ($targetsByIp === []) {
            throw new RuntimeException('The discovered subdomains did not resolve to any IP addresses.');
        }

        $targets = collect($targetsByIp)
            ->map(function (array $target) {
                $target['hostnames'] = collect($target['hostnames'])
                    ->filter(fn ($hostname) => is_string($hostname) && trim($hostname) !== '')
                    ->map(fn (string $hostname) => trim($hostname))
                    ->unique()
                    ->values()
                    ->all();

                $target['fqdn'] = $target['hostnames'][0] ?? $target['fqdn'];

                return $target;
            })
            ->sortBy('ip_address')
            ->values();

        if ($targets->count() > self::MAX_TARGETS) {
            throw new InvalidArgumentException('The discovered domain expansion resolved to too many IPs for the current safe discovery limits.');
        }

        return $targets->all();
    }

    private function buildRunConfig(AttackSurfaceScope $scope): array
    {
        $settings = $scope->settings ?? [];
        $ports = collect(Arr::get($settings, 'ports', self::DEFAULT_PORTS))
            ->map(fn ($port) => (int) $port)
            ->filter(fn (int $port) => $port > 0 && $port <= 65535)
            ->unique()
            ->values()
            ->all();

        return [
            'ports' => !empty($ports) ? $ports : self::DEFAULT_PORTS,
            'timeout_seconds' => max(0.2, min((float) Arr::get($settings, 'timeout_seconds', 1.0), 3.0)),
            'auto_create_assets' => (bool) Arr::get($settings, 'auto_create_assets', false),
            'auto_create_asset_type_id' => Arr::get($settings, 'auto_create_asset_type_id'),
            'auto_create_manager_id' => Arr::get($settings, 'auto_create_manager_id'),
        ];
    }

    private function createAutoDiscoveredAsset(AttackSurfaceScope $scope, string $ipAddress): ?Asset
    {
        $config = $this->buildRunConfig($scope);
        $assetTypeId = Arr::get($config, 'auto_create_asset_type_id');
        $managerId = Arr::get($config, 'auto_create_manager_id');

        if (!$assetTypeId || !$managerId) {
            return null;
        }

        $assetType = AssetType::query()->find($assetTypeId);
        $manager = User::query()->find($managerId);

        if (!$assetType || !$manager) {
            return null;
        }

        return DB::transaction(function () use ($scope, $ipAddress, $assetType, $manager) {
            $asset = Asset::query()->firstOrCreate(
                ['ip_address' => $ipAddress],
                [
                    'name' => "Auto-discovered {$ipAddress}",
                    'asset_type_id' => $assetType->id,
                    'manager_id' => $manager->id,
                    'description' => "Automatically created from attack surface discovery scope {$scope->name}.",
                    'sku' => 'AUTO-' . str_replace('.', '-', $ipAddress),
                    'manufacturer' => 'Unknown',
                    'location' => 'External Attack Surface',
                    'manufacturer_contract_type' => 'NONE',
                    'mac_address' => null,
                    'fqdn' => null,
                    'availability_appreciation' => 0,
                    'integrity_appreciation' => 0,
                    'confidentiality_appreciation' => 0,
                    'export' => false,
                    'active' => true,
                ]
            );

            if ($asset->wasRecentlyCreated) {
                AssetLog::query()->create([
                    'asset_id' => $asset->id,
                    'user_id' => $scope->submitted_by_user_id,
                    'ip' => $ipAddress,
                    'operation_type' => AssetOperationType::CREATE,
                ]);
            }

            return $asset;
        });
    }

    private function isValidIpv4Cidr(string $cidr): bool
    {
        if (!preg_match('/^(\d{1,3}\.){3}\d{1,3}\/\d{1,2}$/', $cidr)) {
            return false;
        }

        [$ip, $prefix] = explode('/', $cidr);
        $prefix = (int) $prefix;

        return filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false
            && $prefix >= 24
            && $prefix <= 32;
    }

    private function isValidHostname(string $hostname): bool
    {
        $hostname = rtrim(strtolower($hostname), '.');

        if ($hostname === '' || strlen($hostname) > 253) {
            return false;
        }

        if (filter_var($hostname, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME) === false) {
            return false;
        }

        return str_contains($hostname, '.');
    }

    private function enumerateSubdomains(string $domain): array
    {
        $domain = rtrim(strtolower($domain), '.');
        $homeDirectory = sys_get_temp_dir().DIRECTORY_SEPARATOR.'subfinder-home-'.bin2hex(random_bytes(8));
        $configDirectory = $homeDirectory.DIRECTORY_SEPARATOR.'.config'.DIRECTORY_SEPARATOR.'subfinder';
        $configFile = $configDirectory.DIRECTORY_SEPARATOR.'config.yaml';
        $providerConfigFile = $configDirectory.DIRECTORY_SEPARATOR.'provider-config.yaml';

        File::ensureDirectoryExists($configDirectory);
        File::put($configFile, "{}\n");
        File::put($providerConfigFile, "{}\n");

        $command = [
            'subfinder',
            '-silent',
            '-duc',
            '-all',
            '-d',
            $domain,
            '-config',
            $configFile,
            '-pc',
            $providerConfigFile,
        ];

        try {
            $result = Process::env([
                'HOME' => $homeDirectory,
            ])->timeout(90)->run($command);

            if (!$result->successful()) {
                $errorOutput = trim($result->errorOutput());
                $standardOutput = trim($result->output());

                throw new RuntimeException($errorOutput !== ''
                    ? $errorOutput
                    : ($standardOutput !== '' ? $standardOutput : 'Subfinder scan failed.'));
            }

            $lines = preg_split('/\r\n|\r|\n/', trim($result->output())) ?: [];

            return collect($lines)
                ->map(fn ($line) => rtrim(strtolower(trim((string) $line)), '.'))
                ->filter(fn (string $hostname) => $hostname !== '' && $this->isValidHostname($hostname))
                ->filter(fn (string $hostname) => $hostname === $domain || str_ends_with($hostname, '.'.$domain))
                ->prepend($domain)
                ->unique()
                ->values()
                ->all();
        } finally {
            File::deleteDirectory($homeDirectory);
        }
    }

    private function resolveHostnameIpAddresses(string $hostname): array
    {
        $ipv4Addresses = gethostbynamel($hostname) ?: [];
        $ipv6Addresses = [];

        if (function_exists('dns_get_record')) {
            foreach (dns_get_record($hostname, DNS_AAAA) ?: [] as $record) {
                $ipv6 = trim((string) ($record['ipv6'] ?? ''));

                if ($ipv6 !== '' && filter_var($ipv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
                    $ipv6Addresses[] = $ipv6;
                }
            }
        }

        return collect(array_merge($ipv4Addresses, $ipv6Addresses))
            ->filter(fn ($ip) => filter_var($ip, FILTER_VALIDATE_IP) !== false)
            ->unique()
            ->values()
            ->all();
    }
}
