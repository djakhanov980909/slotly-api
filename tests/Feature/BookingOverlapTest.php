<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function bookingAt(User $specialist, $start, int $minutes = 60, array $extra = []): Booking
{
    return Booking::factory()->create(array_merge([
        'specialist_id' => $specialist->id,
        'starts_at' => $start->copy(),
        'ends_at' => $start->copy()->addMinutes($minutes),
    ], $extra));
}

it('rejects overlapping bookings for the same specialist', function () {
    $specialist = User::factory()->specialist()->create();
    $start = now()->addDay()->setTime(10, 0);

    bookingAt($specialist, $start);

    expect(fn () => bookingAt($specialist, $start->copy()->addMinutes(30)))
        ->toThrow(QueryException::class);
});

it('allows back-to-back bookings', function () {
    $specialist = User::factory()->specialist()->create();
    $start = now()->addDay()->setTime(10, 0);

    bookingAt($specialist, $start);
    bookingAt($specialist, $start->copy()->addHour());

    expect(Booking::count())->toBe(2);
});

it('ignores cancelled bookings', function () {
    $specialist = User::factory()->specialist()->create();
    $start = now()->addDay()->setTime(10, 0);

    bookingAt($specialist, $start, extra: ['status' => BookingStatus::Cancelled]);
    bookingAt($specialist, $start);

    expect(Booking::count())->toBe(2);
});

it('allows the same slot for different specialists', function () {
    $start = now()->addDay()->setTime(10, 0);

    bookingAt(User::factory()->specialist()->create(), $start);
    bookingAt(User::factory()->specialist()->create(), $start);

    expect(Booking::count())->toBe(2);
});
