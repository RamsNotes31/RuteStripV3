<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gpx_versions', function (Blueprint $table) {
            $table->string('blockchain_network')->nullable()->after('blockchain_tx_hash');
            $table->string('blockchain_contract_address')->nullable()->after('blockchain_network');
            $table->string('blockchain_registered_by')->nullable()->after('blockchain_contract_address');
            $table->timestamp('blockchain_registered_at')->nullable()->after('blockchain_registered_by');
        });
    }

    public function down(): void
    {
        Schema::table('gpx_versions', function (Blueprint $table) {
            $table->dropColumn([
                'blockchain_network',
                'blockchain_contract_address',
                'blockchain_registered_by',
                'blockchain_registered_at',
            ]);
        });
    }
};
