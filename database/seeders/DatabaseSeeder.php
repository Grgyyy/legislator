<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;

use App\Models\TviClass;
use App\Models\TviType;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Call the RegionTableSeeder
        $this->call(RegionTableSeeder::class);
        $this->call(ProvinceSeeder::class);
        $this->call(TviTypeSeeder::class);
        $this->call(TviClassSeeder::class);
        $this->call(InstitutionClassSeeder::class);
        $this->call(ScholarshipProgramSeeder::class);

        // You can call other seeders here as well
        // $this->call(OtherSeeder::class);
    }
}
