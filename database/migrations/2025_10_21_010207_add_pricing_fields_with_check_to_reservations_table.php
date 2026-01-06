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
        // Récupérer la liste des colonnes existantes dans la table reservations
        $columns = Schema::getColumnListing('reservations');

        // Ajouter les champs pour les remises s'ils n'existent pas déjà
        if (! in_array('accommodation_discount_percent', $columns)) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->decimal('accommodation_discount_percent', 5, 2)->default(0);
            });
        }

        if (! in_array('conference_room_discount_percent', $columns)) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->decimal('conference_room_discount_percent', 5, 2)->default(0);
            });
        }

        if (! in_array('services_discount_percent', $columns)) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->decimal('services_discount_percent', 5, 2)->default(0);
            });
        }

        // Ajouter les champs pour les taxes s'ils n'existent pas déjà
        if (! in_array('accommodation_tax_rate', $columns)) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->decimal('accommodation_tax_rate', 5, 2)->default(0);
            });
        }

        if (! in_array('conference_room_tax_rate', $columns)) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->decimal('conference_room_tax_rate', 5, 2)->default(0);
            });
        }

        if (! in_array('services_tax_rate', $columns)) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->decimal('services_tax_rate', 5, 2)->default(0);
            });
        }

        // Ajouter les champs pour les sous-totaux HT s'ils n'existent pas déjà
        if (! in_array('accommodation_subtotal_ht', $columns)) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->decimal('accommodation_subtotal_ht', 10, 2)->default(0);
            });
        }

        if (! in_array('conference_room_subtotal_ht', $columns)) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->decimal('conference_room_subtotal_ht', 10, 2)->default(0);
            });
        }

        if (! in_array('services_subtotal_ht', $columns)) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->decimal('services_subtotal_ht', 10, 2)->default(0);
            });
        }

        // Ajouter les champs pour les sous-totaux TTC s'ils n'existent pas déjà
        if (! in_array('accommodation_subtotal_ttc', $columns)) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->decimal('accommodation_subtotal_ttc', 10, 2)->default(0);
            });
        }

        if (! in_array('conference_room_subtotal_ttc', $columns)) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->decimal('conference_room_subtotal_ttc', 10, 2)->default(0);
            });
        }

        if (! in_array('services_subtotal_ttc', $columns)) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->decimal('services_subtotal_ttc', 10, 2)->default(0);
            });
        }

        // Ajouter le total TTC final s'il n'existe pas déjà
        if (! in_array('total_ttc', $columns)) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->decimal('total_ttc', 10, 2)->default(0);
            });
        }

        // Ajouter le champ pour indiquer si la réservation utilise le système de taxation s'il n'existe pas déjà
        if (! in_array('uses_tax_system', $columns)) {
            Schema::table('reservations', function (Blueprint $table) {
                $table->boolean('uses_tax_system')->default(false);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn([
                'accommodation_discount_percent',
                'conference_room_discount_percent',
                'services_discount_percent',
                'accommodation_tax_rate',
                'conference_room_tax_rate',
                'services_tax_rate',
                'accommodation_subtotal_ht',
                'conference_room_subtotal_ht',
                'services_subtotal_ht',
                'accommodation_subtotal_ttc',
                'conference_room_subtotal_ttc',
                'services_subtotal_ttc',
                'total_ttc',
                'uses_tax_system',
            ]);
        });
    }
};
