<?php

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class RewardsTableSeeder extends Seeder
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
                'talent_id' => 1,
                'name' => 'Jetcoin Tee Shirt',
                'quantity' => 50,
                'price' => 1000,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'talent_id' => 1,
                'name' => 'Tweet',
                'quantity' => 10,
                'price' => 50,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'talent_id' => 1,
                'name' => 'Faceook post',
                'quantity' => 50,
                'price' => 20,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
            [
                'talent_id' => 2,
                'name' => 'Reward of Second talent',
                'quantity' => 150,
                'price' => 40,
                'created_at' => Carbon::now(),
                'updated_at' => Carbon::now(),
            ],
        ];
        DB::table('rewards')->insert($data);
    }
}
