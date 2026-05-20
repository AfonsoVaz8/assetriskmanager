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
        Schema::create('incidents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('integration_id')->constrained()->cascadeOnDelete();
            $table->foreignId('threat_event_id')->unique()->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('status')->default('open');
            $table->string('severity');
            $table->timestamp('first_seen_at');
            $table->timestamp('last_seen_at');
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['integration_id', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('incidents');
    }
};
