<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;

class FlexibleDeliveryModeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $deliveryMode = [
            ['acronym' => 'IBT', 'name' => 'Institution Based Training'],
            ['acronym' => 'EBT', 'name' => 'Enterprised Based Training'],
            ['acronym' => 'CBT', 'name' => 'Community Based Training'],
        ];

        foreach ($deliveryMode as $mode) {
            DB::table('delivery_modes')->updateOrInsert($mode);
        }
    }
}
