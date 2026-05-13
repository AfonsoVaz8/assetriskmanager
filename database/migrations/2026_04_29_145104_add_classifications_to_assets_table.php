<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->foreignId('information_classification_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('risk_classification_id')->nullable()->constrained()->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropForeign(['information_classification_id']);
            $table->dropColumn('information_classification_id');
            
            $table->dropForeign(['risk_classification_id']);
            $table->dropColumn('risk_classification_id');
        });
    }
};