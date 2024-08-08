<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
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
       $this->call(DistrictTableSeeder::class);
       // You can call other seeders here as well
       // $this->call(OtherSeeder::class);
    }
}
