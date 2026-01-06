<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('conference_rooms', function (Blueprint $table) {
            if (! Schema::hasColumn('conference_rooms', 'daily_rate')) {
                $table->decimal('daily_rate', 10, 2)->default(0);
            }
            if (! Schema::hasColumn('conference_rooms', 'hourly_rate')) {
                $table->decimal('hourly_rate', 10, 2)->default(0);
            }
            if (Schema::hasColumn('conference_rooms', 'price')) {
                $table->dropColumn('price');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conference_rooms', function (Blueprint $table) {
            $table->dropColumn(['hourly_rate', 'daily_rate']);
            $table->decimal('price', 10, 2)->default(0);
        });
    }
};
