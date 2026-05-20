# Microsoft Graph threat integration

## Goal

Replace the manual Excel-based risky sign-in workflow with a provider-based ingestion pipeline that:

- syncs Microsoft Entra sign-ins and risk detections on a schedule
- stores provider credentials per tenant in encrypted form
- normalizes all upstream records into a shared threat event schema
- runs a rule-based analysis engine
- opens incidents and sends notifications for high severity events

## Architecture

### Core models

- `integrations`
  - one row per tenant/provider connection
  - stores encrypted credentials, provider settings, sync cursors, sync status, and last error state
  - uses a polymorphic `tenant_type` and `tenant_id` so it can attach to a future `Organization` model or to Jetstream teams if you enable them later
- `threat_events`
  - stores normalized provider data, raw payloads, analysis output, and notification state
  - de-duplicates by `integration_id + provider_event_key`
- `incidents`
  - created from high severity threat events
  - keeps incident lifecycle separate from low-level event ingestion

### Services

- `MicrosoftGraphClient`
  - authenticates with client credentials flow against `https://login.microsoftonline.com/{tenant}/oauth2/v2.0/token`
  - requests `https://graph.microsoft.com/.default`
  - follows `@odata.nextLink` until the stream is exhausted
- `MicrosoftGraphProvider`
  - fetches from `auditLogs/signIns` and `identityProtection/riskDetections`
  - tracks per-stream cursors in `integrations.sync_state`
  - upserts normalized rows into `threat_events`
- normalizers
  - `GraphSignInNormalizer`
  - `GraphRiskDetectionNormalizer`
  - convert Graph JSON into the internal schema before any scoring logic runs
- `ThreatAnalysisEngine`
  - ports the rule-based logic from the Python script into Laravel
  - keeps severity logic isolated from provider-specific code
- `ThreatIncidentService`
  - opens or updates incidents for high severity events
- `ThreatNotificationService`
  - sends notifications after analysis, not during ingestion

### Jobs and schedule

- `threat-integrations:sync`
  - dispatches one `SyncThreatIntegration` job per active integration
- `SyncThreatIntegration`
  - selects the correct provider via `ThreatProviderManager`
- `AnalyzeThreatEvent`
  - calculates findings, score, severity, and confidence
  - opens an incident when needed
  - triggers notification when severity is `high`

The scheduler entry is in [app/Console/Kernel.php](/c:/Users/diogo/.vscode/assetriskmanager/app/Console/Kernel.php:19).

## Database notes

### `integrations`

Recommended fields:

- `tenant_type`, `tenant_id`
- `name`
- `provider`
- `status`
- `credentials` as `encrypted:array`
- `settings` as provider-specific non-secret JSON
- `sync_state` as JSON cursors, for example:

```json
{
  "sign_ins_last_seen_at": "2026-04-14T10:15:00Z",
  "risk_detections_last_seen_at": "2026-04-14T10:12:00Z"
}
```

### `threat_events`

Recommended normalized fields:

- stream identity: `provider`, `provider_event_key`, `event_type`, `source_stream`
- timeline: `occurred_at`, `processed_at`, `notified_at`
- principal: `principal`, `principal_display`
- context: `application_name`, `resource_name`, `ip_address`, `location_label`, `country_code`
- provider risk state: `risk_level`, `risk_state`, `risk_detail`
- analysis state: `severity`, `confidence`, `score`, `findings`
- payload retention: `normalized_payload`, `raw_payload`

### `incidents`

Minimal fields:

- `integration_id`
- `threat_event_id`
- `title`
- `status`
- `severity`
- `first_seen_at`
- `last_seen_at`
- `context`

If you later want event grouping, add an incident-event pivot and group by a fingerprint such as `tenant + principal + ip + day + event_type`.

## Microsoft Graph implementation details

### Required application permissions

- `AuditLog.Read.All` for `GET /auditLogs/signIns`
- `IdentityRiskEvent.Read.All` for `GET /identityProtection/riskDetections`

Microsoft also notes:

- sign-ins support `$top`, `$skiptoken`, and `$filter`, and recommends time-range filtering to avoid timeouts
- risk detections support `$filter` and `$select`, with default page size `20` and max `$top` of `500`
- risk detections require Microsoft Entra ID P1 or P2

Sources:

- https://learn.microsoft.com/en-us/graph/api/signin-list?view=graph-rest-1.0
- https://learn.microsoft.com/en-us/graph/api/riskdetection-list?view=graph-rest-1.0
- https://learn.microsoft.com/en-us/entra/identity-platform/v2-oauth2-client-creds-grant-flow

### Pagination

The client follows `@odata.nextLink` exactly as returned by Graph. This is the correct place to handle paging, because providers should not need to know how Graph encodes skip tokens.

### Duplicate avoidance

Use both:

- Graph object IDs converted into `provider_event_key`
- stream cursors in `integrations.sync_state`

This gives you safe replays without duplicate inserts.

## Mapping from the Python script

The current Python logic does three useful things:

1. extracts sign-in rows into a normalized event structure
2. scores events with deterministic rules
3. escalates medium/high events for reporting

Those map directly into the Laravel implementation:

- Excel loader becomes the Graph provider
- `SignInEvent` becomes `threat_events` normalized rows
- `assess_event()` becomes `ThreatAnalysisEngine`
- report/email generation becomes incidents plus Laravel notifications

## Should the Python logic be ported or called as a service?

### Short answer

Port the existing rules to PHP now. Keep Python only if you have advanced analytics that would be painful to reproduce.

### Why

- your current Python script is mostly deterministic business logic, not heavy data science
- the rules depend on application context already living in Laravel
- keeping scoring in PHP simplifies deployment, observability, retries, and debugging
- queue jobs, incidents, and notifications stay inside one runtime

### When Python should remain separate

Keep Python as a sidecar service only if you later add:

- anomaly detection models
- heavier pandas or notebook-based analytics
- geolocation or ML pipelines already maintained by a security data team

If that happens, pass normalized threat events from Laravel to Python over a queue or HTTP boundary. Do not let Python own tenant credentials or Graph synchronization.

## Production recommendations

- prefer certificates over client secrets for Graph auth when possible
- encrypt credentials at rest and restrict who can view integration settings
- store provider rate limit and error telemetry in logs
- keep raw payloads for forensic traceability, but add retention rules
- move trusted countries, trusted networks, and sensitive apps into provider settings or tenant policy tables instead of hard-coding them
- add a webhook notification channel so external SOAR or ticketing systems can subscribe to `high` incidents
- use a dedicated queue for external integrations so slow Graph syncs do not block user-facing jobs

## Suggested next steps

1. add an integration management UI for tenant admins
2. decide what model represents a tenant in this app, then bind `integrations.tenant_*` to it
3. replace hard-coded trusted networks and countries with tenant-managed policy settings
4. add integration tests with `Http::fake()` for token acquisition and `@odata.nextLink` paging
