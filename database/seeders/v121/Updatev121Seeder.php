<?php
/*
 * File name: Updatev121Seeder.php
 * Last modified: 2021.08.10 at 18:03:35
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2021
 */
namespace Database\Seeders\v121;
use Database\Seeders\WalletTransactionsTableSeeder;
use Illuminate\Database\Seeder;
use Database\Seeders\WalletsTableSeeder;

class Updatev121Seeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        $this->call(PaymentMethodsTableV121Seeder::class);
        $this->call(PermissionsTableV121Seeder::class);
        $this->call(RoleHasPermissionsTableV121Seeder::class);
        $this->call(WalletsTableSeeder::class);
        $this->call(WalletTransactionsTableSeeder::class);
    }
}
