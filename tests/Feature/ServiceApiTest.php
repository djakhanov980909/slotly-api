<?php

use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

it('lists only active services', function () {
    Service::factory()->create(['name' => 'Visible']);
    Service::factory()->create(['name' => 'Hidden', 'is_active' => false]);

    $this->getJson('/api/services')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.name', 'Visible');
});

it('shows a service with specialists but hides their contacts', function () {
    $service = Service::factory()->create();
    $specialist = User::factory()->specialist()->create();
    $service->specialists()->attach($specialist);

    $this->getJson("/api/services/{$service->id}")
        ->assertOk()
        ->assertJsonPath('data.specialists.0.id', $specialist->id)
        ->assertJsonMissingPath('data.specialists.0.email')
        ->assertJsonMissingPath('data.specialists.0.phone');
});

it('forbids a client from creating a service', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/services', [
        'name' => 'Haircut', 'duration_minutes' => 30, 'price' => 5000,
    ])->assertForbidden();
});

it('requires authentication to create a service', function () {
    $this->postJson('/api/services', [])->assertUnauthorized();
});

it('lets an admin create and update a service', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $id = $this->postJson('/api/services', [
        'name' => 'Haircut', 'duration_minutes' => 30, 'price' => 5000,
    ])->assertCreated()->json('data.id');

    $this->putJson("/api/services/{$id}", ['price' => 6000])
        ->assertOk()
        ->assertJsonPath('data.price', 6000);
});

it('validates service data', function () {
    Sanctum::actingAs(User::factory()->admin()->create());

    $this->postJson('/api/services', ['name' => '', 'duration_minutes' => 1, 'price' => -5])
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['name', 'duration_minutes', 'price']);
});
