<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Accommodation>
 */
class AccommodationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $roomNumber = $this->faker->unique()->numberBetween(101, 599);

        return [
            'name' => 'Chambre '.$roomNumber,
            'accommodation_number' => $roomNumber,
            'type' => $this->faker->randomElement(['Standard', 'Deluxe', 'Suite']),
            'nightly_rate' => $this->faker->numberBetween(80, 300),
            'status' => 'available',
        ];
    }
}
