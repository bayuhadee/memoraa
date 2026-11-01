<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('photos', function (Blueprint $table) {
            $table->id();
            // ID publik yang tidak bisa ditebak, dipakai di semua URL.
            $table->uuid('uuid')->unique();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->foreignId('guest_id')->constrained()->cascadeOnDelete();
            $table->string('path')->nullable();
            $table->string('thumb_path')->nullable();
            // File sementara yang menunggu diproses queue (upload baru / ganti foto).
            $table->string('pending_path')->nullable();
            $table->enum('status', ['processing', 'ready', 'failed'])->default('processing')->index();
            $table->text('caption')->nullable();
            $table->enum('orientation', ['landscape', 'portrait'])->nullable();
            $table->unsignedInteger('width')->nullable();
            $table->unsignedInteger('height')->nullable();
            $table->unsignedBigInteger('file_size')->nullable();
            $table->string('mime', 50)->nullable();
            $table->string('original_name')->nullable();
            $table->timestamps();

            $table->index(['event_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('photos');
    }
};
