<?php

use App\Enums\BookingStatus;
use App\Models\Booking;
use App\Models\Service;
use App\Models\TimeOff;
use App\Models\User;
use App\Models\WorkingHour;
use App\Services\SlotService;
use Carbon\CarbonImmutable;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

// 2030-01-07 — понедельник (weekday = 1)

function slotTime(string $time, string $date = '2030-01-07'): CarbonImmutable
{
    return CarbonImmutable::parse("$date $time", config('slotly.timezone'));
}

function workingSpecialist(array $windows = [['09:00', '18:00']]): User
{
    $specialist = User::factory()->specialist()->create();

    foreach ($windows as [$start, $end]) {
        WorkingHour::create([
            'specialist_id' => $specialist->id,
            'weekday' => 1,
            'start_time' => $start,
            'end_time' => $end,
        ]);
    }

    return $specialist;
}

function availableFor(User $specialist, Service $service, string $date = '2030-01-07'): array
{
    return app(SlotService::class)
        ->availableSlots($specialist, $service, CarbonImmutable::parse($date))
        ->map(fn ($slot) => $slot->format('H:i'))
        ->all();
}

function bookSlot(User $specialist, string $from, string $to, array $extra = []): Booking
{
    return Booking::factory()->create(array_merge([
        'specialist_id' => $specialist->id,
        'starts_at' => slotTime($from)->utc(),
        'ends_at' => slotTime($to)->utc(),
    ], $extra));
}

it('returns slots every 30 minutes inside working hours', function () {
    $slots = availableFor(workingSpecialist(), Service::factory()->create(['duration_minutes' => 60]));

    expect($slots)->toHaveCount(17);
    expect($slots[0])->toBe('09:00');
    expect(end($slots))->toBe('17:00');
});

it('returns nothing on a day without working hours', function () {
    $slots = availableFor(workingSpecialist(), Service::factory()->create(), '2030-01-08');

    expect($slots)->toBe([]);
});

it('respects several working windows such as a lunch break', function () {
    $specialist = workingSpecialist([['09:00', '13:00'], ['14:00', '18:00']]);
    $slots = availableFor($specialist, Service::factory()->create(['duration_minutes' => 60]));

    expect($slots)->toContain('12:00', '14:00');
    expect($slots)->not->toContain('12:30', '13:00', '13:30');
});

it('does not offer slots that end after closing time', function () {
    $slots = availableFor(workingSpecialist(), Service::factory()->create(['duration_minutes' => 90]));

    expect($slots)->toContain('16:30');
    expect($slots)->not->toContain('17:00');
});

it('excludes slots that overlap an existing booking', function () {
    $specialist = workingSpecialist();
    bookSlot($specialist, '10:00', '11:00');

    $slots = availableFor($specialist, Service::factory()->create(['duration_minutes' => 60]));

    expect($slots)->toContain('09:00', '11:00');
    expect($slots)->not->toContain('09:30', '10:00', '10:30');
});

it('ignores cancelled bookings', function () {
    $specialist = workingSpecialist();
    bookSlot($specialist, '10:00', '11:00', ['status' => BookingStatus::Cancelled]);

    $slots = availableFor($specialist, Service::factory()->create(['duration_minutes' => 60]));

    expect($slots)->toContain('10:00');
});

it('excludes slots that overlap a time off', function () {
    $specialist = workingSpecialist();
    TimeOff::create([
        'specialist_id' => $specialist->id,
        'starts_at' => slotTime('12:00')->utc(),
        'ends_at' => slotTime('14:00')->utc(),
    ]);

    $slots = availableFor($specialist, Service::factory()->create(['duration_minutes' => 60]));

    expect($slots)->toContain('11:00', '14:00');
    expect($slots)->not->toContain('11:30', '12:00', '13:30');
});

it('does not offer slots in the past', function () {
    $this->travelTo(slotTime('12:00'));

    $slots = availableFor(workingSpecialist(), Service::factory()->create(['duration_minutes' => 60]));

    expect($slots[0])->toBe('12:30');
});
