<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('verification_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gpx_version_id')->constrained()->cascadeOnDelete();
            $table->string('source')->default('local');
            $table->string('expected_hash', 64);
            $table->string('actual_hash', 64)->nullable();
            $table->string('status');
            $table->text('message')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at');
            $table->timestamps();

            $table->index(['gpx_version_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('verification_logs');
    }
};
