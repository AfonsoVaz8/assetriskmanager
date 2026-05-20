<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->string('detected_cpe')->nullable()->after('version');
            $table->string('detected_cpe_confidence', 50)->nullable()->after('detected_cpe');
            $table->string('detected_cpe_source', 100)->nullable()->after('detected_cpe_confidence');
            $table->json('detected_cpe_reasons')->nullable()->after('detected_cpe_source');
        });
    }

    public function down(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn([
                'detected_cpe',
                'detected_cpe_confidence',
                'detected_cpe_source',
                'detected_cpe_reasons',
            ]);
        });
    }
};
