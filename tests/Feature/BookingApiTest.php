<?php

use App\Models\Booking;
use App\Models\Service;
use App\Models\User;
use App\Models\WorkingHour;
use App\Notifications\BookingCancelledNotice;
use App\Notifications\BookingConfirmed;
use App\Services\SlotService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function bookable(): array
{
    $service = Service::factory()->create(['duration_minutes' => 60, 'price' => 7000]);
    $specialist = User::factory()->specialist()->create();
    $specialist->services()->attach($service);

    WorkingHour::create([
        'specialist_id' => $specialist->id,
        'weekday' => 1, // 2030-01-07 — понедельник
        'start_time' => '09:00',
        'end_time' => '18:00',
    ]);

    return [$specialist, $service];
}

function bookingPayload(User $specialist, Service $service, string $time = '10:00'): array
{
    return [
        'specialist_id' => $specialist->id,
        'service_id' => $service->id,
        'date' => '2030-01-07',
        'time' => $time,
    ];
}

it('creates a booking and stores time in UTC', function () {
    [$specialist, $service] = bookable();
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/bookings', bookingPayload($specialist, $service))
        ->assertCreated()
        ->assertJsonPath('data.status', 'confirmed')
        ->assertJsonPath('data.price', 7000);

    $expected = CarbonImmutable::parse('2030-01-07 10:00', config('slotly.timezone'));
    expect(Booking::first()->starts_at->equalTo($expected))->toBeTrue();
});

it('requires authentication', function () {
    $this->postJson('/api/bookings', [])->assertUnauthorized();
});

it('keeps the price from the moment of booking', function () {
    [$specialist, $service] = bookable();
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/bookings', bookingPayload($specialist, $service));
    $service->update(['price' => 9000]);

    expect(Booking::first()->price)->toBe(7000);
});

it('returns 409 for an already taken slot', function () {
    [$specialist, $service] = bookable();

    Sanctum::actingAs(User::factory()->create());
    $this->postJson('/api/bookings', bookingPayload($specialist, $service))->assertCreated();

    Sanctum::actingAs(User::factory()->create());
    $this->postJson('/api/bookings', bookingPayload($specialist, $service))->assertStatus(409);

    expect(Booking::count())->toBe(1);
});

it('returns 409 outside working hours', function () {
    [$specialist, $service] = bookable();
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/bookings', bookingPayload($specialist, $service, '19:00'))->assertStatus(409);
});

it('rejects a service the specialist does not provide', function () {
    [$specialist] = bookable();
    $other = Service::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/bookings', bookingPayload($specialist, $other))
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['service_id']);
});

it('returns 409 instead of 500 when the database catches a race', function () {
    [$specialist, $service] = bookable();
    $start = CarbonImmutable::parse('2030-01-07 10:00', config('slotly.timezone'));

    Booking::factory()->create([
        'specialist_id' => $specialist->id,
        'service_id' => $service->id,
        'starts_at' => $start->utc(),
        'ends_at' => $start->addHour()->utc(),
    ]);

    // Имитируем «устаревшую» проверку: слот успели занять, пока мы его проверяли
    $this->mock(SlotService::class, fn ($mock) => $mock
        ->shouldReceive('availableSlots')->andReturn(collect([$start])));

    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/bookings', bookingPayload($specialist, $service))->assertStatus(409);

    expect(Booking::count())->toBe(1);
});

it('validates the booking data', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/bookings', [])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['specialist_id', 'service_id', 'date', 'time']);
});

it('lists only own bookings for a client', function () {
    $mine = Booking::factory()->create();
    Booking::factory()->create();

    Sanctum::actingAs($mine->client);

    $this->getJson('/api/bookings')->assertOk()->assertJsonCount(1, 'data');
});

it('lets the client cancel and frees the slot', function () {
    [$specialist, $service] = bookable();
    Sanctum::actingAs(User::factory()->create());

    $id = $this->postJson('/api/bookings', bookingPayload($specialist, $service))->json('data.id');

    $this->postJson("/api/bookings/{$id}/cancel")
        ->assertOk()
        ->assertJsonPath('data.status', 'cancelled');

    $this->postJson('/api/bookings', bookingPayload($specialist, $service))->assertCreated();
});

it('forbids cancelling someone elses booking', function () {
    $booking = Booking::factory()->create();
    Sanctum::actingAs(User::factory()->create());

    $this->postJson("/api/bookings/{$booking->id}/cancel")->assertForbidden();
});

it('notifies the client after booking', function () {
    Notification::fake();
    [$specialist, $service] = bookable();
    $client = User::factory()->create();
    Sanctum::actingAs($client);

    $this->postJson('/api/bookings', bookingPayload($specialist, $service))->assertCreated();

    Notification::assertSentTo($client, BookingConfirmed::class);
});

it('sends no confirmation when the slot is taken', function () {
    Notification::fake();
    [$specialist, $service] = bookable();

    Sanctum::actingAs(User::factory()->create());
    $this->postJson('/api/bookings', bookingPayload($specialist, $service))->assertCreated();

    $second = User::factory()->create();
    Sanctum::actingAs($second);
    $this->postJson('/api/bookings', bookingPayload($specialist, $service))->assertStatus(409);

    Notification::assertNotSentTo($second, BookingConfirmed::class);
});

it('notifies the other party when a booking is cancelled', function () {
    Notification::fake();
    $booking = Booking::factory()->create();
    Sanctum::actingAs($booking->client);

    $this->postJson("/api/bookings/{$booking->id}/cancel")->assertOk();

    Notification::assertSentTo($booking->specialist, BookingCancelledNotice::class);
    Notification::assertNotSentTo($booking->client, BookingCancelledNotice::class);
});
