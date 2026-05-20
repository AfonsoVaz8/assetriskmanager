<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('asset_shodan_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45);
            $table->json('open_ports')->nullable();
            $table->json('vulnerabilities')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->string('status')->default('pending');
            $table->text('error')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamps();

            $table->index(['asset_id', 'synced_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('asset_shodan_reports');
    }
};
