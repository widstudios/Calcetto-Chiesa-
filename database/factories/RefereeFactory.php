<?php

namespace Database\Factories;

use App\Models\Referee;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Referee>
 */
class RefereeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'phone' => '333 000 '.fake()->numerify('####'),
        ];
    }
}
