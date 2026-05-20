<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::table('assets', function (Blueprint $table) {
            $table->json('allowed_open_ports')->nullable()->after('ip_address');
        });

        Schema::table('asset_threats', function (Blueprint $table) {
            $table->boolean('auto_generated')->default(false)->after('residual_risk_accepted');
            $table->string('source')->nullable()->after('auto_generated');
            $table->string('source_key')->nullable()->after('source');
            $table->json('source_context')->nullable()->after('source_key');

            $table->index(['asset_id', 'source'], 'asset_threats_asset_source_idx');
            $table->unique(['asset_id', 'source', 'source_key'], 'asset_threats_asset_source_key_unique');
        });
    }

    public function down(): void
    {
        Schema::table('asset_threats', function (Blueprint $table) {
            $table->dropUnique('asset_threats_asset_source_key_unique');
            $table->dropIndex('asset_threats_asset_source_idx');
            $table->dropColumn(['auto_generated', 'source', 'source_key', 'source_context']);
        });

        Schema::table('assets', function (Blueprint $table) {
            $table->dropColumn('allowed_open_ports');
        });
    }
};
