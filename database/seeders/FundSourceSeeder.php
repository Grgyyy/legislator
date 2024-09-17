<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class FundSourceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $sources = [
            ['name' => 'RO - Regular'],
            ['name' => 'CO - Regular'],
            ['name' => 'CO - Legislator Funds'],
        ];

        foreach ($sources as $source) {
            DB::table('fund_sources')->updateOrInsert(
                ['name' => $source['name']],
                $source
            );
        }
    }
}
