<?php

namespace App\Domain\ThreatMonitoring\Services;

use App\Domain\ThreatMonitoring\DTO\ThreatAssessmentResult;
use App\Domain\ThreatMonitoring\Enums\ThreatConfidence;
use App\Domain\ThreatMonitoring\Enums\ThreatSeverity;
use App\Models\ThreatEvent;
use Carbon\Carbon;
use Illuminate\Support\Facades\Cache;

class ThreatAnalysisEngine
{
    private const FAILURE_LOOKBACK_MINUTES = 120;

    private const TRUSTED_NETWORKS = [
        '193.136.0.0/15',
    ];

    private const TRUSTED_COUNTRIES = [
        'PT',
    ];

    private const SENSITIVE_APPS = [
        'Microsoft Azure CLI',
        'Azure Portal',
        'Microsoft Graph',
        'Exchange Online',
        'Office 365 Exchange Online',
        'Azure Active Directory PowerShell',
        'Microsoft Intune',
    ];

    private const DEFAULT_ANALYSIS_POLICY = [
        'severity_high_threshold' => 60,
        'severity_medium_threshold' => 30,
        'successful_signin_points' => 5,
        'successful_external_signin_points' => 10,
        'ip_reputation_high_points' => 50,
        'ip_reputation_nonzero_points' => 25,
        'unusual_country_points' => 15,
        'sensitive_application_points' => 15,
        'single_factor_auth_points' => 20,
        'conditional_access_not_applied_points' => 15,
        'missing_os_context_points' => 5,
        'missing_browser_context_points' => 5,
        'failure_then_success_points' => 25,
        'graph_high_risk_points' => 70,
        'graph_medium_risk_points' => 40,
        'graph_low_risk_points' => 15,
        'account_at_risk_points' => 20,
        'confirmed_compromise_points' => 25,
    ];

    public function __construct(
        private readonly IpReputationClient $ipReputationClient,
        private readonly RelatedSignInResolver $relatedSignInResolver,
    )
    {
    }

    public function assess(ThreatEvent $event): ThreatAssessmentResult
    {
        return $event->event_type === 'risk_detection'
            ? $this->assessRiskDetection($event)
            : $this->assessSignIn($event);
    }

    private function assessSignIn(ThreatEvent $event): ThreatAssessmentResult
    {
        $findings = [];
        $score = 0;
        $isSuccess = $event->status === 'success';
        $normalized = $event->normalized_payload ?? [];

        if ($isSuccess) {
            $this->addFinding($findings, $score, 'successful_signin', 'Successful sign-in detected', $this->policyValue($event, 'successful_signin_points'));
        }

        if ($event->ip_address && filter_var($event->ip_address, FILTER_VALIDATE_IP)) {
            if ($this->detectExternalSignins($event) && !$this->isTrustedIp($event) && $isSuccess) {
                $this->addFinding(
                    $findings,
                    $score,
                    'successful_external_signin',
                    sprintf('Successful sign-in from IP %s outside the configured trusted networks', $event->ip_address),
                    $this->policyValue($event, 'successful_external_signin_points'),
                    [
                        'ip_address' => $event->ip_address,
                        'location' => $event->location_label ?? 'unknown',
                        'country_code' => $event->country_code ?? 'unknown',
                        'application' => $event->application_name ?? 'unknown',
                        'trusted_networks' => $this->trustedNetworks($event),
                    ]
                );
            }

            if (!$this->isTrustedIp($event) && $this->ipReputationClient->enabled()) {
                $reputation = Cache::remember(
                    'threat-ip-reputation:' . $event->ip_address,
                    now()->addHours(6),
                    fn (): array => $this->ipReputationClient->lookup($event->ip_address)
                );

                if (($reputation['ok'] ?? false) === true) {
                    $abuseScore = (int) ($reputation['abuse_confidence_score'] ?? 0);
                    $country = $reputation['country_code'] ?? 'n/a';
                    $isp = $reputation['isp'] ?? 'n/a';

                    if ($abuseScore >= 50) {
                        $this->addFinding(
                            $findings,
                            $score,
                            'ip_reputation_high',
                            sprintf('IP %s has high AbuseIPDB score (%d%%), country=%s, isp=%s', $event->ip_address, $abuseScore, $country, $isp),
                            $this->policyValue($event, 'ip_reputation_high_points'),
                            [
                                'ip_address' => $event->ip_address,
                                'abuse_confidence_score' => $abuseScore,
                                'country_code' => $country,
                                'isp' => $isp,
                            ]
                        );
                    } elseif ($abuseScore > 0) {
                        $this->addFinding(
                            $findings,
                            $score,
                            'ip_reputation_nonzero',
                            sprintf('IP %s has AbuseIPDB score (%d%%), country=%s, isp=%s', $event->ip_address, $abuseScore, $country, $isp),
                            $this->policyValue($event, 'ip_reputation_nonzero_points'),
                            [
                                'ip_address' => $event->ip_address,
                                'abuse_confidence_score' => $abuseScore,
                                'country_code' => $country,
                                'isp' => $isp,
                            ]
                        );
                    }
                }
            }
        }

        if (
            $this->detectUnusualCountries($event)
            && $isSuccess
            && $event->country_code
            && !in_array($event->country_code, $this->trustedCountries($event), true)
        ) {
            $this->addFinding(
                $findings,
                $score,
                'unusual_country',
                sprintf('Successful sign-in from country outside trusted set: %s (%s)', $event->country_code, $event->location_label ?? 'unknown'),
                $this->policyValue($event, 'unusual_country_points'),
                [
                    'country_code' => $event->country_code,
                    'location' => $event->location_label ?? 'unknown',
                    'trusted_countries' => $this->trustedCountries($event),
                ]
            );
        }

        if ($isSuccess && $event->application_name && in_array($event->application_name, self::SENSITIVE_APPS, true)) {
            $this->addFinding(
                $findings,
                $score,
                'sensitive_application',
                sprintf('Sensitive application used: %s', $event->application_name),
                $this->policyValue($event, 'sensitive_application_points')
            );
        }

        if ($isSuccess && strtolower((string) ($normalized['authentication_requirement'] ?? '')) === 'singlefactorauthentication') {
            $this->addFinding(
                $findings,
                $score,
                'single_factor_auth',
                'Successful sign-in used single-factor authentication',
                $this->policyValue($event, 'single_factor_auth_points')
            );
        }

        if ($isSuccess && strtolower((string) ($normalized['conditional_access_status'] ?? '')) === 'notapplied') {
            $this->addFinding(
                $findings,
                $score,
                'conditional_access_not_applied',
                'Conditional Access was not applied on successful sign-in',
                $this->policyValue($event, 'conditional_access_not_applied_points')
            );
        }

        if ($isSuccess && blank($normalized['operating_system'] ?? null)) {
            $this->addFinding($findings, $score, 'missing_os_context', 'Operating system field is empty on successful sign-in', $this->policyValue($event, 'missing_os_context_points'));
        }

        if ($isSuccess && blank($normalized['browser'] ?? null)) {
            $this->addFinding($findings, $score, 'missing_browser_context', 'Browser field is empty on successful sign-in', $this->policyValue($event, 'missing_browser_context_points'));
        }

        $previousFailures = $this->previousFailures($event);
        if ($previousFailures->isNotEmpty()) {
            $this->addFinding(
                $findings,
                $score,
                'failure_then_success',
                sprintf('%d failed sign-in(s) detected before successful sign-in within %d minutes', $previousFailures->count(), self::FAILURE_LOOKBACK_MINUTES),
                $this->policyValue($event, 'failure_then_success_points'),
                [
                    'failure_count' => $previousFailures->count(),
                    'lookback_minutes' => self::FAILURE_LOOKBACK_MINUTES,
                    'failure_ips' => $previousFailures->pluck('ip_address')->filter()->unique()->values()->all(),
                    'failure_locations' => $previousFailures->pluck('location_label')->filter()->unique()->values()->all(),
                    'first_failure_at' => optional($previousFailures->first()->occurred_at)->toIso8601String(),
                    'last_failure_at' => optional($previousFailures->last()->occurred_at)->toIso8601String(),
                    'current_success_ip' => $event->ip_address,
                    'current_success_location' => $event->location_label,
                ]
            );
        }

        return new ThreatAssessmentResult(
            severity: $this->classifySeverity($event, $score),
            confidence: $this->classifyConfidence($findings),
            score: $score,
            findings: $findings,
        );
    }

    private function assessRiskDetection(ThreatEvent $event): ThreatAssessmentResult
    {
        $findings = [];
        $score = 0;
        $relatedSignIn = $this->relatedSignInResolver->forRiskDetection($event);
        $riskLevel = strtolower((string) $event->risk_level);
        $riskState = strtolower((string) $event->risk_state);
        $riskDetail = strtolower((string) $event->risk_detail);
        $isMitigated = in_array($riskState, ['dismissed', 'remediated'], true)
            || $riskDetail === 'admindismissedallriskforuser';

        if ($riskLevel === 'high' && !$isMitigated) {
            $this->addFinding(
                $findings,
                $score,
                'graph_high_risk',
                $this->graphRiskDescription('high', $event),
                $this->policyValue($event, 'graph_high_risk_points'),
                [
                    'risk_level' => $event->risk_level,
                    'risk_state' => $event->risk_state,
                    'risk_detail' => $event->risk_detail,
                    'principal' => $event->principal_display ?: $event->principal,
                    'ip_address' => $event->ip_address,
                    'location' => $event->location_label,
                    'related_sign_in_status' => data_get($relatedSignIn, 'status'),
                    'related_sign_in_at' => data_get($relatedSignIn, 'occurred_at'),
                    'related_sign_in_match' => data_get($relatedSignIn, 'match_strategy'),
                    'related_sign_in_app' => data_get($relatedSignIn, 'application_name'),
                    'related_sign_in_failure_reason' => data_get($relatedSignIn, 'failure_reason'),
                ]
            );
        } elseif (in_array($riskLevel, ['medium', 'moderate'], true) && !$isMitigated) {
            $this->addFinding(
                $findings,
                $score,
                'graph_medium_risk',
                $this->graphRiskDescription('medium', $event),
                $this->policyValue($event, 'graph_medium_risk_points'),
                [
                    'risk_level' => $event->risk_level,
                    'risk_state' => $event->risk_state,
                    'risk_detail' => $event->risk_detail,
                    'principal' => $event->principal_display ?: $event->principal,
                    'ip_address' => $event->ip_address,
                    'location' => $event->location_label,
                    'related_sign_in_status' => data_get($relatedSignIn, 'status'),
                    'related_sign_in_at' => data_get($relatedSignIn, 'occurred_at'),
                    'related_sign_in_match' => data_get($relatedSignIn, 'match_strategy'),
                    'related_sign_in_app' => data_get($relatedSignIn, 'application_name'),
                    'related_sign_in_failure_reason' => data_get($relatedSignIn, 'failure_reason'),
                ]
            );
        } elseif ($riskLevel === 'low' && !$isMitigated) {
            $this->addFinding(
                $findings,
                $score,
                'graph_low_risk',
                $this->graphRiskDescription('low', $event),
                $this->policyValue($event, 'graph_low_risk_points'),
                [
                    'risk_level' => $event->risk_level,
                    'risk_state' => $event->risk_state,
                    'risk_detail' => $event->risk_detail,
                    'principal' => $event->principal_display ?: $event->principal,
                    'ip_address' => $event->ip_address,
                    'location' => $event->location_label,
                    'related_sign_in_status' => data_get($relatedSignIn, 'status'),
                    'related_sign_in_at' => data_get($relatedSignIn, 'occurred_at'),
                    'related_sign_in_match' => data_get($relatedSignIn, 'match_strategy'),
                    'related_sign_in_app' => data_get($relatedSignIn, 'application_name'),
                    'related_sign_in_failure_reason' => data_get($relatedSignIn, 'failure_reason'),
                ]
            );
        }

        if ($isMitigated) {
            $this->addFinding(
                $findings,
                $score,
                'graph_risk_mitigated',
                $this->graphMitigatedDescription($event),
                0,
                [
                    'risk_level' => $event->risk_level,
                    'risk_state' => $event->risk_state,
                    'risk_detail' => $event->risk_detail,
                    'principal' => $event->principal_display ?: $event->principal,
                    'ip_address' => $event->ip_address,
                    'location' => $event->location_label,
                ]
            );
        }

        if ($riskState === 'atrisk') {
            $this->addFinding(
                $findings,
                $score,
                'account_at_risk',
                'Identity remains in atRisk state',
                $this->policyValue($event, 'account_at_risk_points'),
                [
                    'risk_state' => $event->risk_state,
                    'principal' => $event->principal_display ?: $event->principal,
                ]
            );
        }

        if (in_array($riskDetail, ['adminconfirmedsignincompromised', 'userreportedcompromised'], true)) {
            $this->addFinding(
                $findings,
                $score,
                'confirmed_compromise_signal',
                'Risk details indicate likely account compromise',
                $this->policyValue($event, 'confirmed_compromise_points'),
                [
                    'risk_detail' => $event->risk_detail,
                    'principal' => $event->principal_display ?: $event->principal,
                ]
            );
        }

        return new ThreatAssessmentResult(
            severity: $this->classifySeverity($event, $score),
            confidence: $this->classifyConfidence($findings),
            score: $score,
            findings: $findings,
        );
    }

    private function classifySeverity(ThreatEvent $event, int $score): ThreatSeverity
    {
        $highThreshold = $this->policyValue($event, 'severity_high_threshold');
        $mediumThreshold = $this->policyValue($event, 'severity_medium_threshold');

        return match (true) {
            $score >= $highThreshold => ThreatSeverity::HIGH,
            $score >= $mediumThreshold => ThreatSeverity::MEDIUM,
            $score >= 1 => ThreatSeverity::LOW,
            default => ThreatSeverity::INFORMATIONAL,
        };
    }

    private function policyValue(ThreatEvent $event, string $key): int
    {
        $value = data_get($event->integration?->settings, "analysis_policy.{$key}");

        if (is_numeric($value)) {
            return (int) $value;
        }

        return self::DEFAULT_ANALYSIS_POLICY[$key];
    }

    private function classifyConfidence(array $findings): ThreatConfidence
    {
        if ($findings === []) {
            return ThreatConfidence::LOW;
        }

        $strongNames = [
            'graph_high_risk',
            'failure_then_success',
            'successful_external_signin',
            'ip_reputation_high',
            'confirmed_compromise_signal',
        ];

        $strongCount = collect($findings)
            ->filter(fn (array $finding): bool => in_array($finding['name'], $strongNames, true))
            ->count();

        return match (true) {
            $strongCount >= 2 || count($findings) >= 4 => ThreatConfidence::HIGH,
            $strongCount >= 1 || count($findings) >= 2 => ThreatConfidence::MEDIUM,
            default => ThreatConfidence::LOW,
        };
    }

    private function previousFailures(ThreatEvent $event)
    {
        if ($event->status !== 'success' || !$event->occurred_at || blank($event->principal)) {
            return ThreatEvent::query()->whereRaw('1 = 0')->get();
        }

        return ThreatEvent::query()
            ->where('integration_id', $event->integration_id)
            ->where('event_type', 'sign_in')
            ->where('principal', $event->principal)
            ->where('status', 'failure')
            ->whereBetween('occurred_at', [
                Carbon::parse($event->occurred_at)->subMinutes(self::FAILURE_LOOKBACK_MINUTES),
                $event->occurred_at,
            ])
            ->orderBy('occurred_at')
            ->get([
                'occurred_at',
                'ip_address',
                'location_label',
            ]);
    }

    private function isTrustedIp(ThreatEvent $event): bool
    {
        if (!$event->ip_address) {
            return false;
        }

        foreach ($this->trustedNetworks($event) as $network) {
            [$base, $prefix] = explode('/', $network);
            $baseLong = ip2long($base);
            $ipLong = ip2long($event->ip_address);

            if ($baseLong === false || $ipLong === false) {
                continue;
            }

            $mask = -1 << (32 - (int) $prefix);
            if (($ipLong & $mask) === ($baseLong & $mask)) {
                return true;
            }
        }

        return false;
    }

    private function trustedCountries(ThreatEvent $event): array
    {
        $countries = data_get($event->integration?->settings, 'trusted_countries', []);

        return $countries !== [] ? $countries : self::TRUSTED_COUNTRIES;
    }

    private function trustedNetworks(ThreatEvent $event): array
    {
        $networks = data_get($event->integration?->settings, 'trusted_networks', []);

        return $networks !== [] ? $networks : self::TRUSTED_NETWORKS;
    }

    private function detectExternalSignins(ThreatEvent $event): bool
    {
        return data_get($event->integration?->settings, 'detect_external_signins', true) !== false;
    }

    private function detectUnusualCountries(ThreatEvent $event): bool
    {
        return data_get($event->integration?->settings, 'detect_unusual_countries', true) !== false;
    }

    private function graphRiskDescription(string $level, ThreatEvent $event): string
    {
        $parts = [
            sprintf(
                'Microsoft Graph classified this identity event as %s risk',
                strtoupper($level)
            ),
        ];

        if ($event->risk_detail) {
            $parts[] = $this->graphRiskDetailExplanation($event->risk_detail);
        }

        if ($event->risk_state) {
            $parts[] = 'and the current risk state is ' . $this->humanizeGraphRiskState($event->risk_state);
        }

        if ($event->ip_address) {
            $parts[] = 'from IP ' . $event->ip_address;
        }

        if ($event->location_label) {
            $parts[] = 'at location ' . $event->location_label;
        }

        return implode(' ', $parts) . '.';
    }

    private function graphMitigatedDescription(ThreatEvent $event): string
    {
        $parts = [
            'Microsoft Graph originally marked this identity event as risky, but it is no longer being treated as active risk',
        ];

        if ($event->risk_detail) {
            $parts[] = $this->graphRiskDetailExplanation($event->risk_detail, mitigated: true);
        }

        if ($event->risk_state) {
            $parts[] = 'and the current risk state is ' . $this->humanizeGraphRiskState($event->risk_state);
        }

        if ($event->ip_address) {
            $parts[] = 'from IP ' . $event->ip_address;
        }

        if ($event->location_label) {
            $parts[] = 'at location ' . $event->location_label;
        }

        return implode(' ', $parts) . '.';
    }

    private function graphRiskDetailExplanation(string $riskDetail, bool $mitigated = false): string
    {
        if (strtolower($riskDetail) === 'none') {
            return $mitigated
                ? 'and Graph did not provide a more specific risk detail for why it was flagged earlier'
                : 'but Graph did not provide a more specific risk detail for why it was flagged';
        }

        return 'because the risk detail is ' . $this->humanizeGraphRiskDetail($riskDetail);
    }

    private function humanizeGraphRiskDetail(string $riskDetail): string
    {
        return match (strtolower($riskDetail)) {
            'userreportedcompromised' => 'userReportedCompromised (the user reported the account as compromised)',
            'adminconfirmedsignincompromised' => 'adminConfirmedSigninCompromised (an administrator confirmed a compromised sign-in)',
            'admindismissedallriskforuser' => 'adminDismissedAllRiskForUser (an administrator dismissed the risk for this user)',
            'none' => 'none',
            default => $riskDetail,
        };
    }

    private function humanizeGraphRiskState(string $riskState): string
    {
        return match (strtolower($riskState)) {
            'atrisk' => 'atRisk',
            'dismissed' => 'dismissed',
            'remediated' => 'remediated',
            'confirmedcompromised' => 'confirmedCompromised',
            default => $riskState,
        };
    }

    private function addFinding(
        array &$findings,
        int &$score,
        string $name,
        string $description,
        int $points,
        array $details = []
    ): void
    {
        $findings[] = [
            'name' => $name,
            'description' => $description,
            'points' => $points,
            'details' => $details,
        ];

        $score += $points;
    }
}
