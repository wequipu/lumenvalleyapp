<?php

namespace Database\Seeders;

use App\Models\Accommodation;
use App\Models\Client;
use App\Models\ConferenceRoom;
use App\Models\Reservation;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed roles first
        $this->call([
            RoleSeeder::class,
            PrivilegeSeeder::class,
            PrivilegeRoleSeeder::class,
        ]);

        // Create or update the superadmin user
        $superAdminUser = User::updateOrCreate(
            ['email' => 'superadmin@example.com'],
            [
                'name' => 'Super Admin',
                'password' => Hash::make('password'),
            ]
        );
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        $superAdminUser->roles()->attach($superAdminRole);

        // Create other users
        User::factory(10)->create();

        // Create clients, accommodations, and conference rooms
        Client::factory(50)->create();
        Accommodation::factory(20)->create();
        ConferenceRoom::factory(5)->create();

        // Create reservations using existing data
        Reservation::factory(100)->create();
    }
}
