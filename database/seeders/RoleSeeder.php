<?php

namespace Database\Seeders;

use App\Models\Role;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        DB::table('roles')->delete();

        Role::create(['name' => 'Super Admin', 'description' => 'Has all permissions']);
        Role::create(['name' => 'Admin', 'description' => 'Manages hotel basics and stats']);
        Role::create(['name' => 'Receptionist', 'description' => 'Manages clients and reservations']);
        Role::create(['name' => 'Salesperson', 'description' => 'Prospects and manages clients']);
    }
}
