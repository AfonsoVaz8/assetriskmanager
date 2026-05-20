<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attack_surface_runs', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->foreignId('attack_surface_scope_id')->constrained()->cascadeOnDelete();
            $table->string('status')->default('queued');
            $table->string('strategy')->default('safe_tcp_discovery');
            $table->unsignedInteger('target_count')->default(0);
            $table->unsignedInteger('active_host_count')->default(0);
            $table->unsignedInteger('created_asset_count')->default(0);
            $table->unsignedInteger('error_count')->default(0);
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->text('error')->nullable();
            $table->json('config_snapshot')->nullable();

            $table->index(['attack_surface_scope_id', 'status']);
            $table->index('started_at');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attack_surface_runs');
    }
};
