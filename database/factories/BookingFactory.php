<?php

namespace Database\Factories;

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Booking>
 */
class BookingFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDay()->setTime(10, 0);

        return [
            'client_id' => User::factory(),
            'specialist_id' => User::factory()->specialist(),
            'service_id' => Service::factory(),
            'starts_at' => $start,
            'ends_at' => $start->copy()->addHour(),
            'status' => BookingStatus::Confirmed,
            'price' => 5000,
        ];
    }
}
