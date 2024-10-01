<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class TargetStatusSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $target_statuses = [
            ['desc' => 'Pending'],
            ['desc' => 'Compliant'],
            ['desc' => 'Non-Compliant'],
            ['desc' => 'Assigned'],
        ];

        foreach ($target_statuses as $status) {
            DB::table('target_statuses')->updateOrInsert($status);
        }
    }
}
