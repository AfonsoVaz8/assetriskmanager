<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ($this->views() as $name => $sql) {
            DB::statement("DROP VIEW IF EXISTS {$name}");
            DB::statement("CREATE VIEW {$name} AS {$sql}");
        }
    }

    public function down(): void
    {
        foreach (array_keys($this->views()) as $name) {
            DB::statement("DROP VIEW IF EXISTS {$name}");
        }
    }

    /**
     * @return array<string, string>
     */
    private function views(): array
    {
        return [
            'vw_grafana_incident_kpis' => <<<'SQL'
SELECT
    COUNT(*) AS total_incidents,
    SUM(CASE WHEN status IN ('open', 'in_progress') THEN 1 ELSE 0 END) AS active_incidents,
    SUM(CASE WHEN status IN ('open', 'in_progress') AND LOWER(severity) = 'critical' THEN 1 ELSE 0 END) AS active_critical_incidents,
    SUM(CASE WHEN first_seen_at >= DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) AS new_incidents_24h,
    SUM(CASE WHEN first_seen_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS new_incidents_7d,
    ROUND(AVG(CASE WHEN resolved_at IS NOT NULL THEN TIMESTAMPDIFF(MINUTE, first_seen_at, resolved_at) END), 2) AS avg_mttr_minutes,
    ROUND(AVG(event_count), 2) AS avg_events_per_incident
FROM incidents
SQL,
            'vw_grafana_m365_account_risk' => <<<'SQL'
SELECT
    COALESCE(NULLIF(TRIM(te.principal_display), ''), NULLIF(TRIM(te.principal), ''), 'Unknown') AS principal,
    COUNT(*) AS threat_event_count,
    COUNT(DISTINCT ie.incident_id) AS incident_count,
    SUM(
        CASE
            WHEN (
                te.status IS NOT NULL
                AND LOWER(te.status) NOT IN ('success', 'succeeded', 'ok')
            ) OR (
                (te.status IS NULL OR LOWER(te.status) NOT IN ('success', 'succeeded', 'ok'))
                AND te.failure_reason IS NOT NULL
                AND TRIM(te.failure_reason) <> ''
                AND LOWER(TRIM(te.failure_reason)) NOT IN ('other', 'other.', 'none', 'n/a', 'not applicable')
            )
            THEN 1
            ELSE 0
        END
    ) AS failure_event_count,
    SUM(
        CASE
            WHEN LOWER(TRIM(COALESCE(te.risk_level, ''))) NOT IN ('', 'none', 'unknown', 'not_set', 'not set', 'n/a')
                OR LOWER(TRIM(COALESCE(te.risk_state, ''))) NOT IN ('', 'none', 'unknown', 'not_set', 'not set', 'n/a')
                OR LOWER(TRIM(COALESCE(te.risk_detail, ''))) NOT IN ('', 'none', 'unknown', 'not_set', 'not set', 'n/a')
            THEN 1
            ELSE 0
        END
    ) AS risky_event_count,
    ROUND(AVG(te.score), 2) AS avg_score,
    MAX(te.score) AS max_score,
    ROUND(
        (COUNT(DISTINCT ie.incident_id) * 8)
        + (
            SUM(
                CASE
                    WHEN LOWER(TRIM(COALESCE(te.risk_level, ''))) NOT IN ('', 'none', 'unknown', 'not_set', 'not set', 'n/a')
                        OR LOWER(TRIM(COALESCE(te.risk_state, ''))) NOT IN ('', 'none', 'unknown', 'not_set', 'not set', 'n/a')
                        OR LOWER(TRIM(COALESCE(te.risk_detail, ''))) NOT IN ('', 'none', 'unknown', 'not_set', 'not set', 'n/a')
                    THEN 1
                    ELSE 0
                END
            ) * 3
        )
        + (
            SUM(
                CASE
                    WHEN (
                        te.status IS NOT NULL
                        AND LOWER(te.status) NOT IN ('success', 'succeeded', 'ok')
                    ) OR (
                        (te.status IS NULL OR LOWER(te.status) NOT IN ('success', 'succeeded', 'ok'))
                        AND te.failure_reason IS NOT NULL
                        AND TRIM(te.failure_reason) <> ''
                        AND LOWER(TRIM(te.failure_reason)) NOT IN ('other', 'other.', 'none', 'n/a', 'not applicable')
                    )
                    THEN 1
                    ELSE 0
                END
            ) * 2
        )
        + COALESCE(MAX(te.score), 0),
        2
    ) AS priority_score,
    CONCAT_WS(
        ' | ',
        CASE
            WHEN COUNT(DISTINCT ie.incident_id) > 0
            THEN CONCAT(COUNT(DISTINCT ie.incident_id), ' incidents')
        END,
        CASE
            WHEN SUM(
                CASE
                    WHEN LOWER(TRIM(COALESCE(te.risk_level, ''))) NOT IN ('', 'none', 'unknown', 'not_set', 'not set', 'n/a')
                        OR LOWER(TRIM(COALESCE(te.risk_state, ''))) NOT IN ('', 'none', 'unknown', 'not_set', 'not set', 'n/a')
                        OR LOWER(TRIM(COALESCE(te.risk_detail, ''))) NOT IN ('', 'none', 'unknown', 'not_set', 'not set', 'n/a')
                    THEN 1
                    ELSE 0
                END
            ) > 0
            THEN CONCAT(
                SUM(
                    CASE
                        WHEN COALESCE(te.risk_level, '') <> ''
                            OR COALESCE(te.risk_state, '') <> ''
                            OR COALESCE(te.risk_detail, '') <> ''
                        THEN 1
                        ELSE 0
                    END
                ),
                ' risk-signaled events'
            )
        END,
        CASE
            WHEN SUM(
                CASE
                    WHEN (
                        te.status IS NOT NULL
                        AND LOWER(te.status) NOT IN ('success', 'succeeded', 'ok')
                    ) OR (
                        (te.status IS NULL OR LOWER(te.status) NOT IN ('success', 'succeeded', 'ok'))
                        AND te.failure_reason IS NOT NULL
                        AND TRIM(te.failure_reason) <> ''
                        AND LOWER(TRIM(te.failure_reason)) NOT IN ('other', 'other.', 'none', 'n/a', 'not applicable')
                    )
                    THEN 1
                    ELSE 0
                END
            ) > 0
            THEN CONCAT(
                SUM(
                    CASE
                        WHEN te.failure_reason IS NOT NULL
                            OR (te.status IS NOT NULL AND LOWER(te.status) NOT IN ('success', 'succeeded', 'ok'))
                        THEN 1
                        ELSE 0
                    END
                ),
                ' failures'
            )
        END,
        CASE
            WHEN COALESCE(MAX(te.score), 0) >= 70
            THEN CONCAT('max score ', ROUND(MAX(te.score), 0))
        END
    ) AS priority_reason,
    MAX(te.occurred_at) AS last_seen_at
FROM threat_events te
JOIN integrations i
    ON i.id = te.integration_id
    AND i.provider = 'microsoft_graph'
LEFT JOIN incident_events ie
    ON ie.threat_event_id = te.id
GROUP BY COALESCE(NULLIF(TRIM(te.principal_display), ''), NULLIF(TRIM(te.principal), ''), 'Unknown')
SQL,
            'vw_grafana_source_ip_risk' => <<<'SQL'
SELECT
    te.ip_address AS ip_address,
    COUNT(*) AS threat_event_count,
    COUNT(DISTINCT COALESCE(NULLIF(TRIM(te.principal_display), ''), NULLIF(TRIM(te.principal), ''), 'Unknown')) AS principal_count,
    COUNT(DISTINCT ie.incident_id) AS incident_count,
    SUM(CASE WHEN LOWER(te.severity) IN ('critical', 'high') THEN 1 ELSE 0 END) AS high_severity_events,
    ROUND(AVG(te.score), 2) AS avg_score,
    MAX(te.score) AS max_score,
    ROUND(
        (COUNT(DISTINCT ie.incident_id) * 8)
        + (SUM(CASE WHEN LOWER(te.severity) IN ('critical', 'high') THEN 1 ELSE 0 END) * 4)
        + (COUNT(DISTINCT COALESCE(NULLIF(TRIM(te.principal_display), ''), NULLIF(TRIM(te.principal), ''), 'Unknown')) * 2)
        + COALESCE(MAX(te.score), 0),
        2
    ) AS priority_score,
    CONCAT_WS(
        ' | ',
        CASE
            WHEN COUNT(DISTINCT ie.incident_id) > 0
            THEN CONCAT(COUNT(DISTINCT ie.incident_id), ' incidents')
        END,
        CASE
            WHEN SUM(CASE WHEN LOWER(te.severity) IN ('critical', 'high') THEN 1 ELSE 0 END) > 0
            THEN CONCAT(SUM(CASE WHEN LOWER(te.severity) IN ('critical', 'high') THEN 1 ELSE 0 END), ' high severity events')
        END,
        CASE
            WHEN COUNT(DISTINCT COALESCE(NULLIF(TRIM(te.principal_display), ''), NULLIF(TRIM(te.principal), ''), 'Unknown')) > 1
            THEN CONCAT(COUNT(DISTINCT COALESCE(NULLIF(TRIM(te.principal_display), ''), NULLIF(TRIM(te.principal), ''), 'Unknown')), ' principals affected')
        END,
        CASE
            WHEN COALESCE(MAX(te.score), 0) >= 70
            THEN CONCAT('max score ', ROUND(MAX(te.score), 0))
        END
    ) AS priority_reason,
    MAX(te.occurred_at) AS last_seen_at
FROM threat_events te
JOIN integrations i
    ON i.id = te.integration_id
    AND i.provider = 'microsoft_graph'
LEFT JOIN incident_events ie
    ON ie.threat_event_id = te.id
WHERE te.ip_address IS NOT NULL
  AND TRIM(te.ip_address) <> ''
GROUP BY te.ip_address
SQL,
            'vw_grafana_attack_surface_kpis' => <<<'SQL'
SELECT
    COUNT(*) AS total_hosts,
    SUM(CASE WHEN status = 'active' THEN 1 ELSE 0 END) AS active_hosts,
    SUM(CASE WHEN status = 'inactive' THEN 1 ELSE 0 END) AS inactive_hosts,
    SUM(CASE WHEN status = 'filtered' THEN 1 ELSE 0 END) AS filtered_hosts,
    SUM(CASE WHEN status = 'unknown' THEN 1 ELSE 0 END) AS unknown_hosts,
    SUM(CASE WHEN status = 'active' AND asset_id IS NULL THEN 1 ELSE 0 END) AS unlinked_active_hosts,
    SUM(CASE WHEN was_auto_created = 1 THEN 1 ELSE 0 END) AS auto_created_hosts,
    SUM(CASE WHEN first_seen_at >= DATE_SUB(NOW(), INTERVAL 7 DAY) THEN 1 ELSE 0 END) AS new_hosts_7d,
    COUNT(DISTINCT attack_surface_scope_id) AS scope_count,
    COUNT(DISTINCT CASE WHEN status = 'active' THEN attack_surface_scope_id END) AS scopes_with_active_hosts
FROM discovered_hosts
SQL,
            'vw_grafana_attack_surface_top_hosts' => <<<'SQL'
SELECT
    dh.id AS discovered_host_id,
    dh.ip_address,
    COALESCE(NULLIF(dh.fqdn, ''), 'Not observed') AS fqdn,
    dh.status,
    COALESCE(a.name, 'Not linked') AS asset_name,
    COALESCE(fs.active_finding_count, 0) AS active_finding_count,
    COALESCE(fs.cve_count, 0) AS cve_count,
    COALESCE(fs.kev_cve_count, 0) AS kev_cve_count,
    COALESCE(fs.open_port_count, 0) AS open_port_count,
    COALESCE(fs.priority_finding_count, 0) AS priority_finding_count,
    (
        (COALESCE(fs.priority_finding_count, 0) * 6)
        + (COALESCE(fs.kev_cve_count, 0) * 10)
        + (COALESCE(fs.cve_count, 0) * 3)
        + COALESCE(fs.open_port_count, 0)
        + CASE WHEN dh.asset_id IS NULL AND dh.status = 'active' THEN 5 ELSE 0 END
    ) AS priority_score,
    CONCAT_WS(
        ' | ',
        CASE
            WHEN COALESCE(fs.priority_finding_count, 0) > 0
            THEN CONCAT(COALESCE(fs.priority_finding_count, 0), ' high/critical findings')
        END,
        CASE
            WHEN COALESCE(fs.kev_cve_count, 0) > 0
            THEN CONCAT(COALESCE(fs.kev_cve_count, 0), ' KEV CVEs')
        END,
        CASE
            WHEN COALESCE(fs.cve_count, 0) > 0
            THEN CONCAT(COALESCE(fs.cve_count, 0), ' CVEs observed')
        END,
        CASE
            WHEN COALESCE(fs.open_port_count, 0) > 0
            THEN CONCAT(COALESCE(fs.open_port_count, 0), ' open ports')
        END,
        CASE
            WHEN dh.asset_id IS NULL AND dh.status = 'active'
            THEN 'active host not linked to an asset'
        END
    ) AS priority_reason,
    MAX(dh.last_seen_at) AS last_seen_at
FROM discovered_hosts dh
LEFT JOIN assets a
    ON a.id = dh.asset_id
LEFT JOIN (
    SELECT
        f.discovered_host_id,
        COUNT(DISTINCT CASE WHEN f.active = 1 THEN f.id END) AS active_finding_count,
        SUM(CASE WHEN f.active = 1 AND f.kind = 'cve_detected' THEN 1 ELSE 0 END) AS cve_count,
        SUM(CASE WHEN f.active = 1 AND f.kind = 'cve_detected' AND COALESCE(vi.cisa_kev, 0) = 1 THEN 1 ELSE 0 END) AS kev_cve_count,
        SUM(CASE WHEN f.active = 1 AND f.kind = 'open_port' THEN 1 ELSE 0 END) AS open_port_count,
        SUM(CASE WHEN f.active = 1 AND LOWER(COALESCE(vi.severity, f.severity, '')) IN ('critical', 'high') THEN 1 ELSE 0 END) AS priority_finding_count
    FROM discovered_host_findings f
    LEFT JOIN vulnerability_intelligence vi
        ON vi.cve = SUBSTRING_INDEX(f.source_key, ':', -1)
    GROUP BY f.discovered_host_id
) fs
    ON fs.discovered_host_id = dh.id
GROUP BY dh.id, dh.ip_address, dh.fqdn, dh.status, a.name, fs.active_finding_count, fs.cve_count, fs.kev_cve_count, fs.open_port_count, fs.priority_finding_count
SQL,
            'vw_grafana_asset_risk' => <<<'SQL'
SELECT
    a.id AS asset_id,
    a.name AS asset_name,
    COALESCE(atp.name, 'Unknown') AS asset_type,
    COALESCE(NULLIF(a.fqdn, ''), NULLIF(a.ip_address, ''), 'Not observed') AS primary_identifier,
    COALESCE(ts.threat_count, 0) AS threat_count,
    COALESCE(ts.auto_generated_threat_count, 0) AS auto_generated_threat_count,
    COALESCE(ts.high_risk_threat_count, 0) AS high_risk_threat_count,
    COALESCE(ts.max_absolute_risk, 0) AS max_absolute_risk,
    COALESCE(ts.max_residual_risk, 0) AS max_residual_risk,
    COALESCE(es.active_external_hosts, 0) AS active_external_hosts,
    COALESCE(es.active_cve_findings, 0) AS active_cve_findings,
    COALESCE(es.kev_cve_findings, 0) AS kev_cve_findings,
    ROUND(
        (COALESCE(ts.high_risk_threat_count, 0) * 10)
        + (COALESCE(es.kev_cve_findings, 0) * 12)
        + (COALESCE(es.active_cve_findings, 0) * 3)
        + (COALESCE(es.active_external_hosts, 0) * 4)
        + (COALESCE(ts.max_absolute_risk, 0) * 1.5)
        + COALESCE(ts.max_residual_risk, 0),
        2
    ) AS priority_score,
    CONCAT_WS(
        ' | ',
        CASE
            WHEN COALESCE(ts.high_risk_threat_count, 0) > 0
            THEN CONCAT(COALESCE(ts.high_risk_threat_count, 0), ' high-risk threats')
        END,
        CASE
            WHEN COALESCE(es.kev_cve_findings, 0) > 0
            THEN CONCAT(COALESCE(es.kev_cve_findings, 0), ' KEV CVE findings')
        END,
        CASE
            WHEN COALESCE(es.active_cve_findings, 0) > 0
            THEN CONCAT(COALESCE(es.active_cve_findings, 0), ' active CVE findings')
        END,
        CASE
            WHEN COALESCE(es.active_external_hosts, 0) > 0
            THEN CONCAT(COALESCE(es.active_external_hosts, 0), ' active external hosts')
        END,
        CASE
            WHEN COALESCE(ts.max_residual_risk, 0) > 0
            THEN CONCAT('residual risk ', COALESCE(ts.max_residual_risk, 0))
        END
    ) AS priority_reason,
    MAX(a.updated_at) AS last_updated_at
FROM assets a
LEFT JOIN asset_types atp
    ON atp.id = a.asset_type_id
LEFT JOIN (
    SELECT
        ath.asset_id,
        COUNT(*) AS threat_count,
        SUM(CASE WHEN ath.auto_generated = 1 THEN 1 ELSE 0 END) AS auto_generated_threat_count,
        SUM(
            CASE
                WHEN (
                    GREATEST(
                        COALESCE(ath.confidentiality_impact, 0),
                        COALESCE(ath.availability_impact, 0),
                        COALESCE(ath.integrity_impact, 0)
                    ) * COALESCE(ath.probability, 0)
                ) >= 15 THEN 1 ELSE 0
            END
        ) AS high_risk_threat_count,
        MAX(
            GREATEST(
                COALESCE(ath.confidentiality_impact, 0),
                COALESCE(ath.availability_impact, 0),
                COALESCE(ath.integrity_impact, 0)
            ) * COALESCE(ath.probability, 0)
        ) AS max_absolute_risk,
        MAX(COALESCE(ath.residual_risk, 0)) AS max_residual_risk
    FROM asset_threats ath
    GROUP BY ath.asset_id
) ts
    ON ts.asset_id = a.id
LEFT JOIN (
    SELECT
        dh.asset_id,
        COUNT(DISTINCT CASE WHEN dh.status = 'active' THEN dh.id END) AS active_external_hosts,
        SUM(CASE WHEN f.active = 1 AND f.kind = 'cve_detected' THEN 1 ELSE 0 END) AS active_cve_findings,
        SUM(CASE WHEN f.active = 1 AND f.kind = 'cve_detected' AND COALESCE(vi.cisa_kev, 0) = 1 THEN 1 ELSE 0 END) AS kev_cve_findings
    FROM discovered_hosts dh
    LEFT JOIN discovered_host_findings f
        ON f.discovered_host_id = dh.id
    LEFT JOIN vulnerability_intelligence vi
        ON vi.cve = SUBSTRING_INDEX(f.source_key, ':', -1)
    WHERE dh.asset_id IS NOT NULL
    GROUP BY dh.asset_id
) es
    ON es.asset_id = a.id
GROUP BY a.id, a.name, atp.name, a.fqdn, a.ip_address, ts.threat_count, ts.auto_generated_threat_count, ts.high_risk_threat_count, ts.max_absolute_risk, ts.max_residual_risk, es.active_external_hosts, es.active_cve_findings, es.kev_cve_findings
SQL,
            'vw_grafana_cve_overview' => <<<'SQL'
SELECT
    COUNT(*) AS total_active_cve_findings,
    SUM(CASE WHEN LOWER(COALESCE(vi.severity, f.severity, '')) = 'critical' THEN 1 ELSE 0 END) AS critical_cve_findings,
    SUM(CASE WHEN LOWER(COALESCE(vi.severity, f.severity, '')) = 'high' THEN 1 ELSE 0 END) AS high_cve_findings,
    SUM(CASE WHEN COALESCE(vi.cisa_kev, 0) = 1 THEN 1 ELSE 0 END) AS kev_cve_findings,
    ROUND(AVG(vi.cvss_score), 2) AS avg_cvss_score,
    MAX(vi.cvss_score) AS max_cvss_score,
    MAX(vi.epss_score) AS max_epss_score
FROM discovered_host_findings f
LEFT JOIN vulnerability_intelligence vi
    ON vi.cve = SUBSTRING_INDEX(f.source_key, ':', -1)
WHERE f.active = 1
  AND f.kind = 'cve_detected'
SQL,
            'vw_grafana_top_cves' => <<<'SQL'
SELECT
    SUBSTRING_INDEX(f.source_key, ':', -1) AS cve,
    COUNT(*) AS affected_host_count,
    SUM(CASE WHEN COALESCE(vi.cisa_kev, 0) = 1 THEN 1 ELSE 0 END) AS kev_hits,
    MAX(vi.cvss_score) AS cvss_score,
    COALESCE(MAX(vi.severity), MAX(f.severity), 'unknown') AS severity,
    MAX(vi.epss_score) AS epss_score,
    ROUND(
        (SUM(CASE WHEN COALESCE(vi.cisa_kev, 0) = 1 THEN 1 ELSE 0 END) * 20)
        + (COUNT(*) * 4)
        + (COALESCE(MAX(vi.cvss_score), 0) * 2)
        + (COALESCE(MAX(vi.epss_score), 0) * 100),
        2
    ) AS priority_score,
    CONCAT_WS(
        ' | ',
        CASE
            WHEN SUM(CASE WHEN COALESCE(vi.cisa_kev, 0) = 1 THEN 1 ELSE 0 END) > 0
            THEN CONCAT(SUM(CASE WHEN COALESCE(vi.cisa_kev, 0) = 1 THEN 1 ELSE 0 END), ' KEV matches')
        END,
        CASE
            WHEN COUNT(*) > 0
            THEN CONCAT(COUNT(*), ' affected hosts')
        END,
        CASE
            WHEN COALESCE(MAX(vi.cvss_score), 0) >= 9
            THEN CONCAT('CVSS ', ROUND(MAX(vi.cvss_score), 1))
        END,
        CASE
            WHEN COALESCE(MAX(vi.epss_score), 0) >= 0.5
            THEN CONCAT('EPSS ', ROUND(MAX(vi.epss_score), 3))
        END
    ) AS priority_reason,
    MAX(f.last_detected_at) AS last_detected_at
FROM discovered_host_findings f
LEFT JOIN vulnerability_intelligence vi
    ON vi.cve = SUBSTRING_INDEX(f.source_key, ':', -1)
WHERE f.active = 1
  AND f.kind = 'cve_detected'
GROUP BY SUBSTRING_INDEX(f.source_key, ':', -1)
SQL,
        ];
    }
};
