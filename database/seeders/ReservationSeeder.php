<?php

namespace Database\Seeders;

use App\Models\Accommodation;
use App\Models\Client;
use App\Models\ConferenceRoom;
use App\Models\Reservation;
use Illuminate\Database\Seeder;

class ReservationSeeder extends Seeder
{
    public function run(): void
    {
        // Create 10 clients if none exist
        if (Client::count() == 0) {
            Client::factory()->count(10)->create();
        }

        // Create 5 accommodations if none exist
        if (Accommodation::count() == 0) {
            Accommodation::factory()->count(5)->create();
        }

        // Create 2 conference rooms if none exist
        if (ConferenceRoom::count() == 0) {
            ConferenceRoom::factory()->count(2)->create();
        }

        // Create 20 reservations
        Reservation::factory()->count(20)->create();
    }
}
