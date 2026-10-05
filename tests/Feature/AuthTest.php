<?php

use App\Enums\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('registers a client and returns a token', function () {
    $response = $this->postJson('/api/register', [
        'name' => 'Aibek',
        'email' => 'aibek@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
    ]);

    $response->assertCreated()
        ->assertJsonPath('user.email', 'aibek@example.com')
        ->assertJsonPath('user.role', 'client')
        ->assertJsonStructure(['user' => ['id', 'name', 'email', 'phone', 'role'], 'token']);

    expect(User::where('email', 'aibek@example.com')->first()->role)->toBe(Role::Client);
});

it('cannot register as admin by sending a role', function () {
    $this->postJson('/api/register', [
        'name' => 'Hacker',
        'email' => 'hacker@example.com',
        'password' => 'password123',
        'password_confirmation' => 'password123',
        'role' => 'admin',
    ])->assertCreated()->assertJsonPath('user.role', 'client');
});

it('validates registration data', function () {
    User::factory()->create(['email' => 'taken@example.com']);

    $this->postJson('/api/register', [
        'name' => '',
        'email' => 'taken@example.com',
        'password' => 'short',
    ])->assertUnprocessable()->assertJsonValidationErrors(['name', 'email', 'password']);
});

it('logs in with correct credentials', function () {
    $user = User::factory()->create(['password' => 'password123']);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'password123'])
        ->assertOk()
        ->assertJsonStructure(['user', 'token']);
});

it('rejects wrong credentials', function () {
    $user = User::factory()->create(['password' => 'password123']);

    $this->postJson('/api/login', ['email' => $user->email, 'password' => 'wrong-password'])
        ->assertUnprocessable()->assertJsonValidationErrors(['email']);

    $this->postJson('/api/login', ['email' => 'nobody@example.com', 'password' => 'password123'])
        ->assertUnprocessable()->assertJsonValidationErrors(['email']);
});

it('requires a token for the me endpoint', function () {
    $this->getJson('/api/me')->assertUnauthorized();
});

it('returns the current user for a valid token', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->getJson('/api/me')
        ->assertOk()
        ->assertJsonPath('data.email', $user->email);
});

it('revokes the token on logout', function () {
    $user = User::factory()->create();
    $token = $user->createToken('test')->plainTextToken;

    $this->withToken($token)->postJson('/api/logout')->assertNoContent();

    expect($user->tokens()->count())->toBe(0);
});
