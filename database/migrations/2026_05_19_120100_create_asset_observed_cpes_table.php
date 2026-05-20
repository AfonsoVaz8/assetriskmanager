<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_observed_cpes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('discovered_host_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('discovered_host_enrichment_run_id')->nullable()->constrained('discovered_host_enrichment_runs')->nullOnDelete();
            $table->string('cpe');
            $table->string('part', 10)->nullable();
            $table->string('vendor')->nullable();
            $table->string('product')->nullable();
            $table->string('version')->nullable();
            $table->string('source', 100);
            $table->string('confidence', 50)->default('low');
            $table->integer('score')->default(0);
            $table->boolean('is_primary')->default(false);
            $table->json('context')->nullable();
            $table->timestamp('first_observed_at')->nullable();
            $table->timestamp('last_observed_at')->nullable();
            $table->timestamps();

            $table->unique(['asset_id', 'cpe']);
            $table->index(['asset_id', 'is_primary']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_observed_cpes');
    }
};
