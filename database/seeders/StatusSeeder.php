<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class StatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $statuses = [
            ['desc' => 'Active'],
            ['desc' => 'Inactive'],
        ];

        foreach ($statuses as $status) {
            DB::table('statuses')->updateOrInsert($status);
        }
    }
}
