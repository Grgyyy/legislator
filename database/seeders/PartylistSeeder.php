<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PartylistSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run()
    {
        $partylists = [
            ['name' => 'Not Applicable'],
        ];

        foreach ($partylists as $partylist) {
            DB::table('partylists')->updateOrInsert(
                ['name' => $partylist['name']],
                $partylist
            );
        }
    }
}
