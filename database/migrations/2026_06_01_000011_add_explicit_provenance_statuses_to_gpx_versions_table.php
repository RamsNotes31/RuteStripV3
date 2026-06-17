<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gpx_versions', function (Blueprint $table) {
            $table->string('ipfs_status')->default('pending_ipfs')->after('ipfs_url');
            $table->string('blockchain_status')->default('pending_blockchain')->after('blockchain_tx_hash');
        });

        DB::table('gpx_versions')
            ->whereNotNull('ipfs_cid')
            ->update(['ipfs_status' => 'uploaded_ipfs']);

        DB::table('gpx_versions')
            ->whereNotNull('blockchain_tx_hash')
            ->update(['blockchain_status' => 'registered_blockchain']);
    }

    public function down(): void
    {
        Schema::table('gpx_versions', function (Blueprint $table) {
            $table->dropColumn(['ipfs_status', 'blockchain_status']);
        });
    }
};
