<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('discovered_hosts', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('attack_surface_scope_id')->constrained()->cascadeOnDelete();
            $table->foreignId('attack_surface_run_id')->constrained()->cascadeOnDelete();
            $table->foreignId('asset_id')->nullable()->constrained()->nullOnDelete();
            $table->string('ip_address', 45);
            $table->string('fqdn')->nullable();
            $table->string('status')->default('inactive');
            $table->string('origin')->default('range_discovery');
            $table->string('discovery_method')->default('safe_tcp_probe');
            $table->json('open_ports')->nullable();
            $table->boolean('was_auto_created')->default(false);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->text('error')->nullable();
            $table->json('raw_payload')->nullable();
            $table->json('normalized_payload')->nullable();

            $table->unique(['attack_surface_run_id', 'ip_address'], 'discovered_hosts_run_ip_unique');
            $table->index(['attack_surface_scope_id', 'status']);
            $table->index(['asset_id', 'last_seen_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('discovered_hosts');
    }
};
