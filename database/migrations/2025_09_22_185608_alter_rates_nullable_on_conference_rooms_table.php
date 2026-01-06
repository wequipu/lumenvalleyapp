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
            $table->decimal('hourly_rate', 8, 2)->nullable()->change();
            $table->decimal('daily_rate', 8, 2)->nullable()->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('conference_rooms', function (Blueprint $table) {
            $table->decimal('hourly_rate', 8, 2)->nullable(false)->change();
            $table->decimal('daily_rate', 8, 2)->nullable(false)->change();
        });
    }
};
