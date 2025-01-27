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


        $passwordRegex = '/^(?=.*[A-Z])(?=.*[a-z])(?=.*\d)(?=.*[@$!%*?&])[A-Za-z\d@$!%*?&]{8,}$/';

        $users = [
            ['name' => 'Super Admin', 'email' => 'superadmin@tesda.gov.ph', 'role' => 'Super Admin'],
            ['name' => 'Cedric James Leala', 'email' => 'cjpleala@tesda.gov.ph', 'role' => 'Super Admin'],
            ['name' => 'Mark Ian Amado', 'email' => 'mimamado@tesda.gov.ph', 'role' => 'Super Admin'],
            ['name' => 'Tj Dhaniella Maurea Ojerio', 'email' => 'tmdlojerio@tesda.gov.ph', 'role' => 'Super Admin'],
            ['name' => 'Admin', 'email' => 'admin@tesda.gov.ph', 'role' => 'Admin'],
            ['name' => 'Rosalina S. Constantino', 'email' => 'rsconstantino@tesda.gov.ph', 'role' => 'Director'],
            ['name' => 'SMD Head', 'email' => 'smdhead@tesda.gov.ph', 'role' => 'SMD Head'],
            ['name' => 'Joemar Caballero', 'email' => 'jbcaballero@tesda.gov.ph', 'role' => 'SMD Head'],
            ['name' => 'Glenford M. Prospero ', 'email' => 'gmprospero@tesda.gov.ph', 'role' => 'SMD Head'],
            ['name' => 'SMD Focal', 'email' => 'smdfocal@tesda.gov.ph', 'role' => 'SMD Focal'],
            ['name' => 'Planning Office', 'email' => 'planning@tesda.gov.ph', 'role' => 'Planning Office'],
            ['name' => 'TESDO', 'email' => 'tesdo@tesda.gov.ph', 'role' => 'TESDO'],
            ['name' => 'Region I', 'email' => 'regionI@tesda.gov.ph', 'role' => 'RO'],
            ['name' => 'Region II', 'email' => 'regionII@tesda.gov.ph', 'role' => 'RO'],
            ['name' => 'Region III', 'email' => 'regionIII@tesda.gov.ph', 'role' => 'RO'],
            ['name' => 'Region IV-A', 'email' => 'regionIVA@tesda.gov.ph', 'role' => 'RO'],
            ['name' => 'Region IV-B', 'email' => 'regionIVB@tesda.gov.ph', 'role' => 'RO'],
            ['name' => 'Region V', 'email' => 'regionV@tesda.gov.ph', 'role' => 'RO'],
            ['name' => 'Region VI', 'email' => 'regionVI@tesda.gov.ph', 'role' => 'RO'],
            ['name' => 'Region VII', 'email' => 'regionVII@tesda.gov.ph', 'role' => 'RO'],
            ['name' => 'Region VIII', 'email' => 'regionVIII@tesda.gov.ph', 'role' => 'RO'],
            ['name' => 'Region IX', 'email' => 'regionIX@tesda.gov.ph', 'role' => 'RO'],
            ['name' => 'Region X', 'email' => 'regionX@tesda.gov.ph', 'role' => 'RO'],
            ['name' => 'Region XI', 'email' => 'regionXI@tesda.gov.ph', 'role' => 'RO'],
            ['name' => 'Region XII', 'email' => 'regionXII@tesda.gov.ph', 'role' => 'RO'],
            ['name' => 'NCR', 'email' => 'ncr@tesda.gov.ph', 'role' => 'RO'],
            ['name' => 'CAR', 'email' => 'car@tesda.gov.ph', 'role' => 'RO'],
            ['name' => 'CARAGA', 'email' => 'caraga@tesda.gov.ph', 'role' => 'RO'],
            ['name' => 'Negros Island Region', 'email' => 'nir@tesda.gov.ph', 'role' => 'RO'],
            ['name' => 'BARMM', 'email' => 'barmm@tesda.gov.ph', 'role' => 'RO'],
            ['name' => 'PO/DO', 'email' => 'podo@tesda.gov.ph', 'role' => 'PO/DO'],
        ];

        foreach ($users as $userData) {

            $defaultPassword = 'Password123!';

            if (!preg_match($passwordRegex, $defaultPassword)) {
                throw new \Exception('Default password does not meet security requirements.');
            }

            $user = User::firstOrCreate(
                ['email' => $userData['email']],
                [
                    'name' => $userData['name'],
                    'password' => bcrypt($defaultPassword),
                ]
            );

            // Assign role
            $role = Role::firstOrCreate(['name' => $userData['role']]);
            $user->assignRole($role);
        }
    }
}
