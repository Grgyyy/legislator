<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define users with their respective roles
        $users = [
            ['name' => 'Super Admin', 'email' => 'superadmin@gmail.com', 'role' => 'Super Admin'],
            ['name' => 'Cedric James Leala', 'email' => 'cjpleala@tesda.gov.ph', 'role' => 'Super Admin'],
            ['name' => 'Mark Ian Amado', 'email' => 'mimamado@tesda.gov.ph', 'role' => 'Super Admin'],
            ['name' => 'Tj Dhaniella Maurea Ojerio', 'email' => 'tmdlojerio@tesda.gov.ph', 'role' => 'Super Admin'],
            ['name' => 'Admin', 'email' => 'admin@gmail.com', 'role' => 'Admin'],
            ['name' => 'SMD Head', 'email' => 'smdhead@gmail.com', 'role' => 'SMD Head'],
            ['name' => 'Joemar Caballero', 'email' => 'jbcaballero@tesda.gov.ph', 'role' => 'SMD Head'],
            ['name' => 'Glenford M. Prospero ', 'email' => 'gmprospero@tesda.gov.ph', 'role' => 'SMD Head'],
            ['name' => 'SMD Focal', 'email' => 'smdfocal@gmail.com', 'role' => 'SMD Focal'],
            ['name' => 'TESDO', 'email' => 'tesdo@gmail.com', 'role' => 'TESDO'],
            ['name' => 'Region I', 'email' => 'regionI@gmail.com', 'role' => 'RO'],
            ['name' => 'Region II', 'email' => 'regionII@gmail.com', 'role' => 'RO'],
            ['name' => 'Region III', 'email' => 'regionIII@gmail.com', 'role' => 'RO'],
            ['name' => 'Region IV-A', 'email' => 'regionIVA@gmail.com', 'role' => 'RO'],
            ['name' => 'Region IV-B', 'email' => 'regionIVB@gmail.com', 'role' => 'RO'],
            ['name' => 'Region V', 'email' => 'regionV@gmail.com', 'role' => 'RO'],
            ['name' => 'Region VI', 'email' => 'regionVI@gmail.com', 'role' => 'RO'],
            ['name' => 'Region VII', 'email' => 'regionVII@gmail.com', 'role' => 'RO'],
            ['name' => 'Region VIII', 'email' => 'regionVIII@gmail.com', 'role' => 'RO'],
            ['name' => 'Region IX', 'email' => 'regionIX@gmail.com', 'role' => 'RO'],
            ['name' => 'Region X', 'email' => 'regionX@gmail.com', 'role' => 'RO'],
            ['name' => 'Region XI', 'email' => 'regionXI@gmail.com', 'role' => 'RO'],
            ['name' => 'Region XII', 'email' => 'regionXII@gmail.com', 'role' => 'RO'],
            ['name' => 'NCR', 'email' => 'ncr@gmail.com', 'role' => 'RO'],
            ['name' => 'CAR', 'email' => 'car@gmail.com', 'role' => 'RO'],
            ['name' => 'CARAGA', 'email' => 'caraga@gmail.com', 'role' => 'RO'],
            ['name' => 'Negros Island Region', 'email' => 'nir@gmail.com', 'role' => 'RO'],
            ['name' => 'BARMM', 'email' => 'barmm@gmail.com', 'role' => 'RO'],
            ['name' => 'PO/DO', 'email' => 'podo@gmail.com', 'role' => 'PO/DO'],
        ];

        foreach ($users as $userData) {
            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                ['name' => $userData['name'], 'password' => bcrypt('password')]
            );

            // Assign role
            $role = Role::firstOrCreate(['name' => $userData['role']]);
            $user->assignRole($role);
        }
    }
}
