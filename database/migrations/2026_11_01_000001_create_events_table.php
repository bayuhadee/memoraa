<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('events', function (Blueprint $table) {
            $table->id();
            $table->string('event_code', 64)->unique();
            $table->string('bride_groom_names');
            $table->date('event_date')->index();
            $table->text('address');
            // Terenkripsi (cast "encrypted") agar owner bisa melihat & memberikannya ke host.
            $table->text('password_plain');
            $table->string('password_hash');
            $table->enum('payment_status', ['baru', 'belum_bayar', 'lunas'])->default('baru')->index();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('events');
    }
};
