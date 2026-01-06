<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\ConferenceRoom>
 */
class ConferenceRoomFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => 'Salle '.$this->faker->word(),
            'room_number' => $this->faker->unique()->numberBetween(1, 100),
            'capacity' => $this->faker->numberBetween(10, 100),
            'hourly_rate' => $this->faker->numberBetween(50, 200),
            'daily_rate' => $this->faker->numberBetween(300, 1500),
            'equipment' => 'Projecteur, Tableau blanc, Wi-Fi',
            'is_air_conditioned' => $this->faker->boolean(),
            'status' => 'available',
        ];
    }
}
