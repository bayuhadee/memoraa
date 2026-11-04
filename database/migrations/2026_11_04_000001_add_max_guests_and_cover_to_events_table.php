<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('events', function (Blueprint $table) {
            // null = tanpa batas jumlah tamu.
            $table->unsignedInteger('max_guests')->nullable()->after('address');
            // Path relatif di disk private "local": covers/{event_id}/{uuid}.jpg
            $table->string('cover_path')->nullable()->after('max_guests');
        });
    }

    public function down(): void
    {
        Schema::table('events', function (Blueprint $table) {
            $table->dropColumn(['max_guests', 'cover_path']);
        });
    }
};
