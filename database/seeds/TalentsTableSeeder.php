<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class TalentsTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        $data = [
            [
                'name' => 'JETCOIN Pool',
                'val' => 10000000,
                'percentage' => 20,
                'jet_amount' => 2000000,
                'available' => 2000000,
                'sold' => 0,
                'description' => 'JETCOIN pool represents: 3% IP rights of all champions',
                'is_active' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'name' => 'Second Talent',
                'val' => 16000000,
                'percentage' => 25,
                'jet_amount' => 4000000,
                'available' => 4000000,
                'sold' => 0,
                'description' => 'Description of second talent',
                'is_active' => 1,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];
        DB::table('talents')->insert($data);


    }
}
