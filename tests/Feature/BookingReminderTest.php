<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Notifications\BookingReminder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function bookingIn(int $hours, array $extra = []): Booking
{
    return Booking::factory()->create(array_merge([
        'starts_at' => now()->addHours($hours),
        'ends_at' => now()->addHours($hours)->addHour(),
    ], $extra));
}

it('reminds about bookings starting within 24 hours', function () {
    Notification::fake();
    $booking = bookingIn(5);

    $this->artisan('app:send-booking-reminders')->assertSuccessful();

    Notification::assertSentTo($booking->client, BookingReminder::class);
    expect($booking->fresh()->reminder_sent_at)->not->toBeNull();
});

it('ignores bookings further than 24 hours away', function () {
    Notification::fake();
    $booking = bookingIn(30);

    $this->artisan('app:send-booking-reminders');

    Notification::assertNothingSent();
    expect($booking->fresh()->reminder_sent_at)->toBeNull();
});

it('never reminds twice', function () {
    Notification::fake();
    $booking = bookingIn(5);

    $this->artisan('app:send-booking-reminders');
    $this->artisan('app:send-booking-reminders');

    Notification::assertSentToTimes($booking->client, BookingReminder::class, 1);
});

it('skips cancelled and past bookings', function () {
    Notification::fake();
    bookingIn(5, ['status' => BookingStatus::Cancelled]);
    bookingIn(-3);

    $this->artisan('app:send-booking-reminders');

    Notification::assertNothingSent();
});
