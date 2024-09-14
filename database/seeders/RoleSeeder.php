<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoleSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $roles = [
            ['desc' => 'Admin'],
            ['desc' => 'SMD Head'],
            ['desc' => 'SMD Focal'],
            ['desc' => 'Regional Office'],
            ['desc' => 'Provincial Office'],
        ];

        foreach ($roles as $role) {
            DB::table('roles')->updateOrInsert($roles);
        }
    }
}
