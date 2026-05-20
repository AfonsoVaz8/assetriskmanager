<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incident_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('incident_id')->constrained()->cascadeOnDelete();
            $table->foreignId('threat_event_id')->constrained()->cascadeOnDelete();
            $table->timestamp('linked_at')->useCurrent();
            $table->timestamps();

            $table->unique(['incident_id', 'threat_event_id']);
            $table->index('threat_event_id');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->nullableMorphs('tenant');
            $table->string('fingerprint', 64)->nullable()->after('integration_id');
            $table->string('confidence')->default('medium')->after('severity');
            $table->unsignedInteger('event_count')->default(1)->after('confidence');
            $table->string('affected_principal')->nullable()->after('event_count');
            $table->string('affected_principal_display')->nullable()->after('affected_principal');
            $table->string('event_type')->nullable()->after('affected_principal_display');
            $table->foreignId('assigned_to')->nullable()->after('event_type')->constrained('users')->nullOnDelete();
            $table->foreignId('resolved_by')->nullable()->after('assigned_to')->constrained('users')->nullOnDelete();
            $table->timestamp('resolved_at')->nullable()->after('resolved_by');
            $table->text('resolution_note')->nullable()->after('resolved_at');
            $table->foreignId('dismissed_by')->nullable()->after('resolution_note')->constrained('users')->nullOnDelete();
            $table->timestamp('dismissed_at')->nullable()->after('dismissed_by');
        });

        Schema::table('threat_events', function (Blueprint $table) {
            $table->string('incident_fingerprint', 64)->nullable()->after('score');
            $table->index(['integration_id', 'principal', 'event_type', 'occurred_at'], 'threat_events_incident_lookup_idx');
            $table->index(['integration_id', 'ip_address', 'event_type', 'occurred_at'], 'threat_events_ip_lookup_idx');
            $table->index('incident_fingerprint');
        });

        if (Schema::hasColumn('incidents', 'threat_event_id')) {
            DB::table('incidents')
                ->select(['id', 'threat_event_id'])
                ->whereNotNull('threat_event_id')
                ->orderBy('id')
                ->chunkById(200, function ($rows): void {
                    $payload = [];
                    $now = now();

                    foreach ($rows as $row) {
                        $payload[] = [
                            'incident_id' => $row->id,
                            'threat_event_id' => $row->threat_event_id,
                            'linked_at' => $now,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ];
                    }

                    if ($payload !== []) {
                        DB::table('incident_events')->insertOrIgnore($payload);
                    }
                });
        }

        DB::table('incidents')
            ->orderBy('id')
            ->chunkById(200, function ($rows): void {
                foreach ($rows as $row) {
                    $integration = DB::table('integrations')->where('id', $row->integration_id)->first();
                    $event = isset($row->threat_event_id)
                        ? DB::table('threat_events')->where('id', $row->threat_event_id)->first()
                        : null;

                    DB::table('incidents')
                        ->where('id', $row->id)
                        ->update([
                            'tenant_type' => $row->tenant_type ?? $integration?->tenant_type,
                            'tenant_id' => $row->tenant_id ?? $integration?->tenant_id,
                            'affected_principal' => $row->affected_principal ?? $event?->principal,
                            'affected_principal_display' => $row->affected_principal_display ?? $event?->principal_display,
                            'event_type' => $row->event_type ?? $event?->event_type,
                            'confidence' => !empty($row->confidence) ? $row->confidence : ($event?->confidence ?? 'medium'),
                            'event_count' => DB::table('incident_events')->where('incident_id', $row->id)->count(),
                            'fingerprint' => $row->fingerprint ?? ($event ? sha1(implode('|', [
                                $integration?->tenant_type ?? 'integration',
                                $integration?->tenant_id ?? $row->integration_id,
                                $row->integration_id,
                                $event->event_type ?? 'unknown',
                                Str::lower(trim((string) ($event->principal ?? 'unknown'))),
                                'legacy-event',
                                $event?->occurred_at ? substr((string) $event->occurred_at, 0, 13) : 'no-time',
                            ])) : null),
                        ]);
                }
            });

        Schema::table('incidents', function (Blueprint $table) {
            $table->index(['tenant_type', 'tenant_id', 'status'], 'incidents_tenant_status_idx');
            $table->index(['tenant_type', 'tenant_id', 'last_seen_at'], 'incidents_tenant_last_seen_idx');
            $table->index(['tenant_type', 'tenant_id', 'severity'], 'incidents_tenant_severity_idx');
            $table->index(['fingerprint', 'status'], 'incidents_fingerprint_status_idx');
        });
    }

    public function down(): void
    {
        Schema::table('threat_events', function (Blueprint $table) {
            $table->dropIndex('threat_events_incident_lookup_idx');
            $table->dropIndex('threat_events_ip_lookup_idx');
            $table->dropIndex(['incident_fingerprint']);
            $table->dropColumn('incident_fingerprint');
        });

        Schema::table('incidents', function (Blueprint $table) {
            $table->dropIndex('incidents_tenant_status_idx');
            $table->dropIndex('incidents_tenant_last_seen_idx');
            $table->dropIndex('incidents_tenant_severity_idx');
            $table->dropIndex('incidents_fingerprint_status_idx');
            $table->dropConstrainedForeignId('assigned_to');
            $table->dropConstrainedForeignId('resolved_by');
            $table->dropConstrainedForeignId('dismissed_by');
            $table->dropMorphs('tenant');
            $table->dropColumn([
                'fingerprint',
                'confidence',
                'event_count',
                'affected_principal',
                'affected_principal_display',
                'event_type',
                'resolved_at',
                'resolution_note',
                'dismissed_at',
            ]);
        });

        Schema::dropIfExists('incident_events');
    }
};
