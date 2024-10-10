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
        // Fetch fund sources
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

        // Particulars for each fund source
        $regional_regular_particulars = ['Regular'];
        $central_regular_particulars = ['Regular', 'SDF', 'With Identified TVI', 'RO Programming', 'Vetted', 'Star Rated', 'APACC', 'AIFO', 'EO79', 'EO70', 'KIA/WIA'];
        $central_legislator_funds_particulars = ['District', 'Party-list', 'Senator', 'House Speaker', 'House Speaker (LAKAS)'];

        // Insert or update particulars for RO - Regular
        foreach ($regional_regular_particulars as $particular) {
            DB::table('sub_particulars')->updateOrInsert(
                ['name' => $particular, 'fund_source_id' => $region_regular->id],
                ['name' => $particular, 'fund_source_id' => $region_regular->id]
            );
        }

        // Insert or update particulars for CO - Regular
        foreach ($central_regular_particulars as $particular) {
            DB::table('sub_particulars')->updateOrInsert(
                ['name' => $particular, 'fund_source_id' => $central_regular->id],
                ['name' => $particular, 'fund_source_id' => $central_regular->id]
            );
        }

        // Insert or update particulars for CO - Legislator Funds
        foreach ($central_legislator_funds_particulars as $particular) {
            DB::table('sub_particulars')->updateOrInsert(
                ['name' => $particular, 'fund_source_id' => $central_legislator_funds->id],
                ['name' => $particular, 'fund_source_id' => $central_legislator_funds->id]
            );
        }
    }
}
