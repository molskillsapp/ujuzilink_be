<?php
/**
 * File name: FavoriteFactory.php
 * Last modified: 2024.04.11 at 15:19:20
 * Author: SmarterVision - https://codecanyon.net/user/smartervision
 * Copyright (c) 2024
 */

namespace Database\Factories;

use App\Models\Favorite;
use Faker\Generator as Faker;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Class FavoriteFactory
 * @package Database\Factories
 */
class FavoriteFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = Favorite::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'e_service_id' => $this->faker->numberBetween(1, 30),
            'user_id' => $this->faker->numberBetween(1, 6)
        ];
    }
}
