<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{

    public function up(): void
    {
        Schema::create('asset_vulnerability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('asset_id')->constrained()->cascadeOnDelete();
            $table->foreignId('vulnerability_id')->constrained('vulnerabilities')->cascadeOnDelete();
            
            $table->integer('probability')->default(1);
            $table->integer('confidentiality_impact')->default(1);
            $table->integer('integrity_impact')->default(1);
            $table->integer('availability_impact')->default(1);
            $table->boolean('residual_risk_accepted')->default(false);
            $table->text('notes')->nullable();
            
            $table->timestamps();
        });
    }


    public function down(): void
    {
        Schema::dropIfExists('asset_vulnerability');
    }
};