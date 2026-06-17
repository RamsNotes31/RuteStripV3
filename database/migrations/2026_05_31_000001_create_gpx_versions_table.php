<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gpx_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('hiking_route_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('version_number');
            $table->string('gpx_file_path');
            $table->string('file_hash', 64);
            $table->string('verification_status')->default('verified');
            $table->text('change_log')->nullable();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->boolean('is_active')->default(false);
            $table->string('ipfs_cid')->nullable();
            $table->string('ipfs_url')->nullable();
            $table->string('blockchain_tx_hash')->nullable();
            $table->unsignedInteger('ipfs_upload_time_ms')->nullable();
            $table->unsignedInteger('ipfs_retrieval_time_ms')->nullable();
            $table->timestamp('verified_at')->nullable();
            $table->timestamps();

            $table->unique(['hiking_route_id', 'version_number']);
            $table->index(['hiking_route_id', 'is_active']);
            $table->index('verification_status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gpx_versions');
    }
};
