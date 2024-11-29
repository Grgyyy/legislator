<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Recognition;

class RecognitionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $recognitions = [
            ['name' => 'Star Rated'],
            ['name' => 'APACC'],
        ];

        foreach ($recognitions as $recognition) {
            Recognition::updateOrCreate(['name' => $recognition['name']], $recognition);
        }
    }
}
