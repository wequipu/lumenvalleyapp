<?php

namespace Database\Seeders;

use App\Models\Privilege;
use Illuminate\Database\Seeder;

class PrivilegeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $privileges = [
            ['name' => 'view-dashboard', 'description' => 'View the dashboard'],
            ['name' => 'manage-roles', 'description' => 'Manage roles and permissions'],
            ['name' => 'manage-privileges', 'description' => 'Manage privileges'],
            ['name' => 'manage-users', 'description' => 'Manage users'],
            ['name' => 'manage-accommodations', 'description' => 'Manage accommodations'],
            ['name' => 'manage-reservations', 'description' => 'Manage reservations'],
            ['name' => 'manage-clients', 'description' => 'Manage clients'],
            ['name' => 'manage-services', 'description' => 'Manage services'],
            ['name' => 'manage-conference-rooms', 'description' => 'Manage conference rooms'],
            ['name' => 'view-reports', 'description' => 'View reports'],
            ['name' => 'edit-reservation-after-checkout', 'description' => 'Edit a reservation after checkout'],
            ['name' => 'grant-time-limited-privileges', 'description' => 'Grant time-limited privileges to other users'],
            ['name' => 'manage-time-limited-privileges', 'description' => 'Manage time-limited privileges'],
        ];

        foreach ($privileges as $privilege) {
            Privilege::firstOrCreate(['name' => $privilege['name']], ['description' => $privilege['description']]);
        }
    }
}
