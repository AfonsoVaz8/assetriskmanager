<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discovered_host_enrichment_runs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('discovered_host_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('provider')->default('shodan');
            $table->string('status')->default('queued');
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->json('open_ports')->nullable();
            $table->json('vulnerabilities')->nullable();
            $table->text('error')->nullable();
            $table->json('raw_payload')->nullable();
            $table->json('normalized_payload')->nullable();

            $table->index(['discovered_host_id', 'provider', 'status'], 'dh_enrichment_host_provider_status_idx');
            $table->index('synced_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discovered_host_enrichment_runs');
    }
};
