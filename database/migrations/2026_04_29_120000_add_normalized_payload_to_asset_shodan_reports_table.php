<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('asset_shodan_reports', function (Blueprint $table) {
            $table->json('normalized_payload')->nullable()->after('raw_payload');
        });
    }

    public function down(): void
    {
        Schema::table('asset_shodan_reports', function (Blueprint $table) {
            $table->dropColumn('normalized_payload');
        });
    }
};
