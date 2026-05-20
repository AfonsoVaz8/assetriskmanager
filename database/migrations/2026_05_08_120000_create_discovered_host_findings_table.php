<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discovered_host_findings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('discovered_host_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('last_enrichment_run_id')->nullable()->constrained('discovered_host_enrichment_runs')->nullOnDelete();
            $table->string('kind', 100);
            $table->string('source', 100)->nullable();
            $table->string('source_key', 255);
            $table->string('title', 255);
            $table->text('description')->nullable();
            $table->string('severity', 50)->nullable();
            $table->json('context')->nullable();
            $table->boolean('active')->default(true);
            $table->timestamp('first_detected_at')->nullable();
            $table->timestamp('last_detected_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['discovered_host_id', 'source', 'source_key'],
                'discovered_host_findings_host_source_key_unique'
            );
            $table->index(['discovered_host_id', 'active'], 'discovered_host_findings_host_active_idx');
            $table->index(['kind', 'active'], 'discovered_host_findings_kind_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discovered_host_findings');
    }
};
