<?php

namespace Database\Factories;

use App\Models\Accommodation;
use App\Models\Client;
use App\Models\ConferenceRoom;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Reservation>
 */
class ReservationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $reservable = $this->faker->randomElement([
            Accommodation::class,
            ConferenceRoom::class,
        ]);

        $checkin_date = $this->faker->dateTimeBetween('-1 month', '+1 month');
        $checkout_date = (clone $checkin_date)->modify('+'.$this->faker->numberBetween(1, 10).' days');

        return [
            'client_id' => Client::inRandomOrder()->first()->id,
            'reservable_id' => $reservable::inRandomOrder()->first()->id,
            'reservable_type' => $reservable,
            'checkin_date' => $checkin_date,
            'checkout_date' => $checkout_date,
            'total_price' => $this->faker->numberBetween(100, 5000),
            'status' => $this->faker->randomElement(['pending', 'confirmed', 'checked-in', 'checked-out', 'canceled']),
        ];
    }
}
