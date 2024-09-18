<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Database\Seeders\ScholarshipProgramSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the RegionTableSeeder
        $this->call(StatusSeeder::class);
        $this->call(RoleSeeder::class);
        $this->call(PartylistSeeder::class);
        $this->call(RegionTableSeeder::class);
        $this->call(ProvinceSeeder::class);
        $this->call(TviTypeSeeder::class);
        $this->call(TviClassSeeder::class);
        $this->call(InstitutionClassSeeder::class);
        $this->call(ScholarshipProgramSeeder::class);
        $this->call(ProvinceSeeder::class);
        $this->call(MunicipalitySeeder::class);
        $this->call(DistrictSeeder::class);
        $this->call(FundSourceSeeder::class);
        $this->call(SubParticularSeeder::class);
        // $this->call(CentralParticularSeeder::class);
        // $this->call(RegionalParticularSeeder::class);
        $this->call(TargetStatusSeeder::class);

        // You can call other seeders here as well
        // $this->call(OtherSeeder::class);
    }
}
