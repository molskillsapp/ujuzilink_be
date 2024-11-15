<?php
/*
 * File name: AvailabilityHoursTableSeeder.php
 * Last modified: 2021.02.01 at 22:22:23
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2021
 */


namespace Database\Seeders;
use App\Models\AvailabilityHour;
use DB;
use Illuminate\Database\Seeder;

class AvailabilityHoursTableSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * @return void
     */
    public function run()
    {
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('availability_hours')->truncate();
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');


        AvailabilityHour::factory()->count(50)->create();
    }
}
