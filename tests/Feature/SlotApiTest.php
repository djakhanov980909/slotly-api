<?php

use App\Models\Service;
use App\Models\User;
use App\Models\WorkingHour;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function specialistWith(Service $service): User
{
    $specialist = User::factory()->specialist()->create();
    $specialist->services()->attach($service);

    // 2030-01-07 — понедельник
    WorkingHour::create([
        'specialist_id' => $specialist->id,
        'weekday' => 1,
        'start_time' => '09:00',
        'end_time' => '11:00',
    ]);

    return $specialist;
}

it('returns available slots', function () {
    $service = Service::factory()->create(['duration_minutes' => 60]);
    $specialist = specialistWith($service);

    $this->getJson("/api/specialists/{$specialist->id}/slots?service_id={$service->id}&date=2030-01-07")
        ->assertOk()
        ->assertJsonPath('data.slots', ['09:00', '09:30', '10:00']);
});

it('returns 404 when the specialist does not provide the service', function () {
    $specialist = specialistWith(Service::factory()->create());
    $other = Service::factory()->create();

    $this->getJson("/api/specialists/{$specialist->id}/slots?service_id={$other->id}&date=2030-01-07")
        ->assertNotFound();
});

it('returns 404 for a user who is not a specialist', function () {
    $service = Service::factory()->create();
    $client = User::factory()->create();

    $this->getJson("/api/specialists/{$client->id}/slots?service_id={$service->id}&date=2030-01-07")
        ->assertNotFound();
});

it('validates the query', function () {
    $specialist = specialistWith(Service::factory()->create());

    $this->getJson("/api/specialists/{$specialist->id}/slots")
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['service_id', 'date']);
});
