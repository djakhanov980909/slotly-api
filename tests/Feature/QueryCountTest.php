<?php

use App\Models\Booking;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Laravel\Sanctum\Sanctum;

uses(RefreshDatabase::class);

function countListingQueries(int $bookings): int
{
    $client = User::factory()->create();
    Booking::factory()->count($bookings)->create(['client_id' => $client->id]);
    Sanctum::actingAs($client);

    DB::flushQueryLog();
    DB::enableQueryLog();
    test()->getJson('/api/bookings')->assertOk();
    $count = count(DB::getQueryLog());
    DB::disableQueryLog();

    return $count;
}

it('uses the same number of queries regardless of how many bookings are listed', function () {
    expect(countListingQueries(5))->toBe(countListingQueries(1));
});
