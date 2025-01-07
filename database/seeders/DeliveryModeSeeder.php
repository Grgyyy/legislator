<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class DeliveryModeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $deliveryModes = [
            ['acronym' => 'IBT', 'name' => 'Institution Based Training'],
            ['acronym' => 'EBT', 'name' => 'Enterprised Based Training'],
            ['acronym' => 'CBT', 'name' => 'Community Based Training'],

        ];

        foreach ($deliveryModes as $mode) {
            DB::table('delivery_modes')->updateOrInsert($mode);
        }
    }
}
