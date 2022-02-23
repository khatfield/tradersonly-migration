<?php

namespace Database\Factories;

use App\Models\MigrationDelta;
use Illuminate\Database\Eloquent\Factories\Factory;

class MigrationDeltaFactory extends Factory
{
    /**
     * The name of the factory's corresponding model.
     *
     * @var string
     */
    protected $model = MigrationDelta::class;

    /**
     * Define the model's default state.
     *
     * @return array
     */
    public function definition()
    {
        return [
            "to_subscription_id" => $this->faker->numberBetween(1,999999)
        ];
    }
}
