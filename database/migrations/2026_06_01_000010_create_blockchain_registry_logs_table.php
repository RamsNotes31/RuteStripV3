<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('blockchain_registry_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gpx_version_id')->constrained()->cascadeOnDelete();
            $table->string('operation')->default('register');
            $table->string('status');
            $table->string('network')->nullable();
            $table->string('contract_address')->nullable();
            $table->string('tx_hash')->nullable();
            $table->string('registered_by')->nullable();
            $table->unsignedBigInteger('block_number')->nullable();
            $table->unsignedBigInteger('gas_used')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('logged_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('blockchain_registry_logs');
    }
};
