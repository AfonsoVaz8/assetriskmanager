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
        // Forward-only reporting refresh.
    }

    /**
     * @return array<string, string>
     */
    private function views(): array
    {
        return [
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
                        WHEN LOWER(TRIM(COALESCE(te.risk_level, ''))) NOT IN ('', 'none', 'unknown', 'not_set', 'not set', 'n/a')
                            OR LOWER(TRIM(COALESCE(te.risk_state, ''))) NOT IN ('', 'none', 'unknown', 'not_set', 'not set', 'n/a')
                            OR LOWER(TRIM(COALESCE(te.risk_detail, ''))) NOT IN ('', 'none', 'unknown', 'not_set', 'not set', 'n/a')
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
        ];
    }
};
