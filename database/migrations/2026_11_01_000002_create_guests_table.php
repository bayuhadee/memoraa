<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('guests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('event_id')->constrained()->cascadeOnDelete();
            $table->string('device_token', 64)->index();
            $table->string('name', 100);
            $table->string('address', 255);
            // Nullable: host bisa menghapus ucapan tanpa menghapus tamu & fotonya.
            $table->text('message')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->timestamps();

            // 1 perangkat hanya bisa 1 entri buku tamu per event.
            $table->unique(['event_id', 'device_token']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('guests');
    }
};
