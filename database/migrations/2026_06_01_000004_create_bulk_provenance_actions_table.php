<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('bulk_provenance_actions', function (Blueprint $table) {
            $table->id();
            $table->string('action');
            $table->string('status')->default('queued');
            $table->unsignedInteger('processed_count')->default(0);
            $table->unsignedInteger('failed_count')->default(0);
            $table->text('last_error')->nullable();
            $table->foreignId('started_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bulk_provenance_actions');
    }
};
