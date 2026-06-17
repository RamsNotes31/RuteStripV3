<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ipfs_upload_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('gpx_version_id')->constrained()->cascadeOnDelete();
            $table->string('operation')->default('upload');
            $table->string('status');
            $table->string('cid')->nullable();
            $table->string('url')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->text('message')->nullable();
            $table->timestamp('logged_at');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ipfs_upload_logs');
    }
};
