<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('asset_threats', function (Blueprint $table) {
            $table->dropUnique('asset_threats_asset_id_threat_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('asset_threats', function (Blueprint $table) {
            $table->unique(['asset_id', 'threat_id']);
        });
    }
};
