<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Role;
use App\Models\Privilege;
use Illuminate\Support\Facades\DB;

class PrivilegeRoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Clear the pivot table to avoid duplicates on re-seeding
        DB::table('privilege_role')->delete();

        // Assign privileges to Receptionist
        $receptionistRole = Role::where('name', 'Receptionist')->first();
        if ($receptionistRole) {
            $receptionistPrivileges = Privilege::whereIn('name', [
                'manage-accommodations',
                'manage-reservations',
                'manage-clients',
                'manage-services',
                'manage-conference-rooms',
                'view-reports'
            ])->get();
            $receptionistRole->privileges()->attach($receptionistPrivileges);
        }

        // Assign all privileges to Super Admin
        $superAdminRole = Role::where('name', 'Super Admin')->first();
        if ($superAdminRole) {
            $allPrivileges = Privilege::all();
            $superAdminRole->privileges()->attach($allPrivileges);
        }

        // Assign some privileges to Admin
        $adminRole = Role::where('name', 'Admin')->first();
        if ($adminRole) {
            $adminPrivileges = Privilege::whereIn('name', [
                'manage-accommodations',
                'manage-reservations',
                'manage-clients',
                'manage-services',
                'manage-conference-rooms',
                'view-reports'
            ])->get();
            $adminRole->privileges()->attach($adminPrivileges);
        }
    }
}
