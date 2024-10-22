<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SubParticularSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $region_regular = DB::table('fund_sources')
            ->where('name', 'RO Regular')
            ->first();

        $central_regular = DB::table('fund_sources')
            ->where('name', 'CO Regular')
            ->first();

        $central_legislator_funds = DB::table('fund_sources')
            ->where('name', 'CO Legislator Funds')
            ->first();

        if (!$region_regular || !$central_regular || !$central_legislator_funds) {
            echo "One or more fund sources not found.";
            return;
        }

        $regional_regular_particulars = ['RO Regular'];
        $central_regular_particulars = ['CO Regular', 'SDF', 'With Identified TVI', 'RO Programming', 'Vetted', 'Star Rated', 'APACC', 'AIFO', 'EO79', 'EO70', 'KIA/WIA'];
        $central_legislator_funds_particulars = ['District', 'Party-list', 'Senator', 'House Speaker', 'House Speaker (LAKAS)'];

        foreach ($regional_regular_particulars as $particular) {
            DB::table('sub_particulars')->updateOrInsert(
                ['name' => $particular, 'fund_source_id' => $region_regular->id],
                ['name' => $particular, 'fund_source_id' => $region_regular->id]
            );
        }

        foreach ($central_regular_particulars as $particular) {
            DB::table('sub_particulars')->updateOrInsert(
                ['name' => $particular, 'fund_source_id' => $central_regular->id],
                ['name' => $particular, 'fund_source_id' => $central_regular->id]
            );
        }

        foreach ($central_legislator_funds_particulars as $particular) {
            DB::table('sub_particulars')->updateOrInsert(
                ['name' => $particular, 'fund_source_id' => $central_legislator_funds->id],
                ['name' => $particular, 'fund_source_id' => $central_legislator_funds->id]
            );
        }
    }
}
