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
            ['name' => 'Cedric James Leala', 'email' => 'cjpleala@tesda.gov.ph', 'role' => 'Super Admin'],
            ['name' => 'Mark Ian Amado', 'email' => 'mimamado@tesda.gov.ph', 'role' => 'Super Admin'],
            ['name' => 'Tj Dhaniella Maurea Ojerio', 'email' => 'tmdlojerio@tesda.gov.ph', 'role' => 'Super Admin'],
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
