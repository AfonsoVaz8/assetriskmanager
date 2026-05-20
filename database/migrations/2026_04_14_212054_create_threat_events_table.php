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
        Schema::create('threat_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained()->cascadeOnDelete();
            $table->string('provider');
            $table->string('provider_event_key');
            $table->string('event_type');
            $table->string('source_stream');
            $table->timestamp('occurred_at')->nullable();
            $table->string('principal')->nullable();
            $table->string('principal_display')->nullable();
            $table->string('application_name')->nullable();
            $table->string('resource_name')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->string('location_label')->nullable();
            $table->string('country_code', 2)->nullable();
            $table->string('status')->nullable();
            $table->text('failure_reason')->nullable();
            $table->string('risk_level')->nullable();
            $table->string('risk_state')->nullable();
            $table->string('risk_detail')->nullable();
            $table->string('severity')->default('informational');
            $table->string('confidence')->default('low');
            $table->unsignedInteger('score')->default(0);
            $table->json('findings')->nullable();
            $table->json('normalized_payload')->nullable();
            $table->json('raw_payload')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamp('notified_at')->nullable();
            $table->timestamps();

            $table->unique(['integration_id', 'provider_event_key']);
            $table->index(['integration_id', 'occurred_at']);
            $table->index(['integration_id', 'severity']);
            $table->index(['integration_id', 'source_stream']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('threat_events');
    }
};
