<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('gpx_versions', function (Blueprint $table) {
            $table->unsignedBigInteger('blockchain_block_number')->nullable()->after('blockchain_registered_at');
            $table->unsignedBigInteger('blockchain_gas_used')->nullable()->after('blockchain_block_number');
            $table->string('blockchain_confirmation_status')->nullable()->after('blockchain_gas_used');
            $table->timestamp('blockchain_block_timestamp')->nullable()->after('blockchain_confirmation_status');
            $table->timestamp('blockchain_receipt_checked_at')->nullable()->after('blockchain_block_timestamp');
        });
    }

    public function down(): void
    {
        Schema::table('gpx_versions', function (Blueprint $table) {
            $table->dropColumn([
                'blockchain_block_number',
                'blockchain_gas_used',
                'blockchain_confirmation_status',
                'blockchain_block_timestamp',
                'blockchain_receipt_checked_at',
            ]);
        });
    }
};
