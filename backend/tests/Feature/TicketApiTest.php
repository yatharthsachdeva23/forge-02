<?php

use App\Models\Ticket;
use App\Models\User;

beforeEach(function () {
    $this->org = \App\Models\Organization::factory()->create();
    $this->user = User::factory()->create([
        'organization_id' => $this->org->id,
        'role' => 'agent',
    ]);
    $this->token = $this->user->createToken('test')->plainTextToken;
});

function authHeader(string $token): array
{
    return ['Authorization' => "Bearer {$token}"];
}

test('index returns tickets for authed org only', function () {
    // Own org tickets
    Ticket::factory()->count(3)->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->user->id,
    ]);

    // Other org's tickets
    $otherOrg = \App\Models\Organization::factory()->create();
    $otherUser = User::factory()->create(['organization_id' => $otherOrg->id]);
    Ticket::factory()->create([
        'organization_id' => $otherOrg->id,
        'requester_id' => $otherUser->id,
    ]);

    $response = $this->withHeaders(authHeader($this->token))
        ->getJson('/api/tickets');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(3);
});

test('index filters by status', function () {
    Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->user->id,
        'status' => 'open',
    ]);
    Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->user->id,
        'status' => 'closed',
    ]);

    $response = $this->withHeaders(authHeader($this->token))
        ->getJson('/api/tickets?status=open');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.status'))->toBe('open');
});

test('index filters by priority', function () {
    Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->user->id,
        'priority' => 'high',
    ]);
    Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->user->id,
        'priority' => 'low',
    ]);

    $response = $this->withHeaders(authHeader($this->token))
        ->getJson('/api/tickets?priority=high');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.priority'))->toBe('high');
});

test('index text search matches subject and description', function () {
    Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->user->id,
        'subject' => 'Login page broken',
        'description' => 'Cannot sign in',
    ]);
    Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->user->id,
        'subject' => 'Feature request',
        'description' => 'Add dark mode',
    ]);

    // Search subject
    $r1 = $this->withHeaders(authHeader($this->token))
        ->getJson('/api/tickets?search=Login');
    $r1->assertStatus(200);
    expect($r1->json('data'))->toHaveCount(1);

    // Search description
    $r2 = $this->withHeaders(authHeader($this->token))
        ->getJson('/api/tickets?search=dark');
    $r2->assertStatus(200);
    expect($r2->json('data'))->toHaveCount(1);
});

test('store creates ticket with authed user as requester', function () {
    $response = $this->withHeaders(authHeader($this->token))
        ->postJson('/api/tickets', [
            'subject' => 'New ticket from API',
            'description' => 'Something is wrong',
            'priority' => 'urgent',
        ]);

    $response->assertStatus(201);
    $response->assertJsonPath('requester_id', $this->user->id);
    $response->assertJsonPath('organization_id', $this->org->id);
    $response->assertJsonPath('priority', 'urgent');
    $response->assertJsonPath('status', 'open');
});

test('store defaults priority to medium', function () {
    $response = $this->withHeaders(authHeader($this->token))
        ->postJson('/api/tickets', [
            'subject' => 'Test',
            'description' => 'Desc',
        ]);

    $response->assertStatus(201);
    $response->assertJsonPath('priority', 'medium');
});

test('show returns ticket for own org', function () {
    $ticket = Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->user->id,
    ]);

    $response = $this->withHeaders(authHeader($this->token))
        ->getJson("/api/tickets/{$ticket->id}");

    $response->assertStatus(200);
    $response->assertJsonPath('id', $ticket->id);
});

test('show returns 404 for other org ticket', function () {
    $otherOrg = \App\Models\Organization::factory()->create();
    $otherUser = User::factory()->create(['organization_id' => $otherOrg->id]);
    $ticket = Ticket::factory()->create([
        'organization_id' => $otherOrg->id,
        'requester_id' => $otherUser->id,
    ]);

    $response = $this->withHeaders(authHeader($this->token))
        ->getJson("/api/tickets/{$ticket->id}");

    $response->assertStatus(404);
});

test('update modifies status and priority', function () {
    $ticket = Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->user->id,
        'status' => 'open',
        'priority' => 'low',
    ]);

    $response = $this->withHeaders(authHeader($this->token))
        ->putJson("/api/tickets/{$ticket->id}", [
            'status' => 'resolved',
            'priority' => 'high',
        ]);

    $response->assertStatus(200);
    $response->assertJsonPath('status', 'resolved');
    $response->assertJsonPath('priority', 'high');
});

test('assign sets assignee_id', function () {
    $ticket = Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->user->id,
        'assignee_id' => null,
    ]);

    $response = $this->withHeaders(authHeader($this->token))
        ->postJson("/api/tickets/{$ticket->id}/assign", [
            'assignee_id' => $this->user->id,
        ]);

    $response->assertStatus(200);
    $response->assertJsonPath('assignee_id', $this->user->id);
});
