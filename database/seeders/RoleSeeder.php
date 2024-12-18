<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Define fixed roles
        $roles = [
            'Super Admin',
            'Admin',
            'SMD Head',
            'SMD Focal',
            'TESDO',
            'RO', // Consolidated role for all regions
            'PO/DO',
        ];

        // Create roles if not already present
        foreach ($roles as $roleName) {
            Role::firstOrCreate(['name' => $roleName]);
        }
    }
}
