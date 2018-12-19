<?php

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
         $this->call(TalentsTableSeeder::class);
         $this->call(RewardsTableSeeder::class);
         $this->call(GeneralSettingsTableSeeder::class);
        // $this->call(UsersTableSeeder::class);
    }
}
