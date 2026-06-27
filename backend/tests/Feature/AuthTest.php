<?php

use App\Models\Organization;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->org = Organization::factory()->create();
});

test('register creates user and returns token', function () {
    $response = $this->postJson('/api/register', [
        'org_id' => $this->org->id,
        'name' => 'Jane Doe',
        'email' => 'jane@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure(['user' => ['id', 'name', 'email', 'role'], 'token']);

    expect($response->json('user.role'))->toBe('customer');

    $this->assertDatabaseHas('users', [
        'email' => 'jane@example.com',
        'organization_id' => $this->org->id,
    ]);
});

test('register allows role override', function () {
    $response = $this->postJson('/api/register', [
        'org_id' => $this->org->id,
        'name' => 'Admin User',
        'email' => 'admin@example.com',
        'password' => 'password123',
        'role' => 'admin',
    ]);

    $response->assertStatus(201);
    expect($response->json('user.role'))->toBe('admin');
});

test('register rejects duplicate email within same org', function () {
    User::factory()->create([
        'organization_id' => $this->org->id,
        'email' => 'dup@example.com',
    ]);

    $response = $this->postJson('/api/register', [
        'org_id' => $this->org->id,
        'name' => 'Dup User',
        'email' => 'dup@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(422);
});

test('register rejects invalid org_id', function () {
    $response = $this->postJson('/api/register', [
        'org_id' => 99999,
        'name' => 'Ghost',
        'email' => 'ghost@example.com',
        'password' => 'password123',
    ]);

    $response->assertStatus(422);
});

test('login returns token with valid credentials', function () {
    $user = User::factory()->create([
        'organization_id' => $this->org->id,
        'email' => 'login@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'login@example.com',
        'password' => 'secret123',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure(['user', 'token']);
});

test('login fails with wrong password', function () {
    User::factory()->create([
        'organization_id' => $this->org->id,
        'email' => 'wrong@example.com',
        'password' => Hash::make('secret123'),
    ]);

    $response = $this->postJson('/api/login', [
        'email' => 'wrong@example.com',
        'password' => 'wrong-password',
    ]);

    $response->assertStatus(422);
});

test('logout revokes current token', function () {
    $user = User::factory()->create([
        'organization_id' => $this->org->id,
    ]);
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->postJson('/api/logout');

    $response->assertStatus(200);

    // Token should be deleted from the database
    expect(\Laravel\Sanctum\PersonalAccessToken::count())->toBe(0);
});

test('me returns authenticated user', function () {
    $user = User::factory()->create([
        'organization_id' => $this->org->id,
    ]);
    $token = $user->createToken('test')->plainTextToken;

    $response = $this->withHeader('Authorization', "Bearer {$token}")
        ->getJson('/api/me');

    $response->assertStatus(200);
    $response->assertJsonPath('user.id', $user->id);
    $response->assertJsonPath('user.email', $user->email);
});

test('me rejects unauthenticated request', function () {
    $response = $this->getJson('/api/me');

    $response->assertStatus(401);
});
