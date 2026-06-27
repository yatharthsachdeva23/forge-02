<?php

use App\Models\Ticket;
use App\Models\User;

beforeEach(function () {
    $this->org = \App\Models\Organization::factory()->create();

    $this->agent = User::factory()->create([
        'organization_id' => $this->org->id,
        'role' => 'agent',
    ]);
    $this->agentToken = $this->agent->createToken('test')->plainTextToken;

    $this->admin = User::factory()->create([
        'organization_id' => $this->org->id,
        'role' => 'admin',
    ]);
    $this->adminToken = $this->admin->createToken('test')->plainTextToken;

    $this->customer = User::factory()->create([
        'organization_id' => $this->org->id,
        'role' => 'customer',
    ]);
    $this->customerToken = $this->customer->createToken('test')->plainTextToken;
});

function authHeader(string $token): array
{
    return ['Authorization' => "Bearer {$token}"];
}

// ── Existing tests (preserved) ──────────────────────────

test('index returns tickets for authed org only', function () {
    Ticket::factory()->count(3)->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->agent->id,
    ]);

    $otherOrg = \App\Models\Organization::factory()->create();
    $otherUser = User::factory()->create(['organization_id' => $otherOrg->id]);
    Ticket::factory()->create([
        'organization_id' => $otherOrg->id,
        'requester_id' => $otherUser->id,
    ]);

    $response = $this->withHeaders(authHeader($this->agentToken))
        ->getJson('/api/tickets');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(3);
});

test('index filters by status', function () {
    Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->agent->id,
        'status' => 'open',
    ]);
    Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->agent->id,
        'status' => 'closed',
    ]);

    $response = $this->withHeaders(authHeader($this->agentToken))
        ->getJson('/api/tickets?status=open');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.status'))->toBe('open');
});

test('index filters by priority', function () {
    Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->agent->id,
        'priority' => 'high',
    ]);
    Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->agent->id,
        'priority' => 'low',
    ]);

    $response = $this->withHeaders(authHeader($this->agentToken))
        ->getJson('/api/tickets?priority=high');

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.priority'))->toBe('high');
});

test('index text search matches subject and description', function () {
    Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->agent->id,
        'subject' => 'Login page broken',
        'description' => 'Cannot sign in',
    ]);
    Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->agent->id,
        'subject' => 'Feature request',
        'description' => 'Add dark mode',
    ]);

    $r1 = $this->withHeaders(authHeader($this->agentToken))
        ->getJson('/api/tickets?search=Login');
    $r1->assertStatus(200);
    expect($r1->json('data'))->toHaveCount(1);

    $r2 = $this->withHeaders(authHeader($this->agentToken))
        ->getJson('/api/tickets?search=dark');
    $r2->assertStatus(200);
    expect($r2->json('data'))->toHaveCount(1);
});

test('store creates ticket with authed user as requester', function () {
    $response = $this->withHeaders(authHeader($this->agentToken))
        ->postJson('/api/tickets', [
            'subject' => 'New ticket from API',
            'description' => 'Something is wrong',
            'priority' => 'urgent',
        ]);

    $response->assertStatus(201);
    $response->assertJsonPath('requester_id', $this->agent->id);
    $response->assertJsonPath('organization_id', $this->org->id);
    $response->assertJsonPath('priority', 'urgent');
    $response->assertJsonPath('status', 'open');
});

test('store defaults priority to medium', function () {
    $response = $this->withHeaders(authHeader($this->agentToken))
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
        'requester_id' => $this->agent->id,
    ]);

    $response = $this->withHeaders(authHeader($this->agentToken))
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

    $response = $this->withHeaders(authHeader($this->agentToken))
        ->getJson("/api/tickets/{$ticket->id}");

    $response->assertStatus(404);
});

test('update modifies status and priority', function () {
    $ticket = Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->agent->id,
        'status' => 'open',
        'priority' => 'low',
    ]);

    $response = $this->withHeaders(authHeader($this->agentToken))
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
        'requester_id' => $this->agent->id,
        'assignee_id' => null,
    ]);

    $response = $this->withHeaders(authHeader($this->agentToken))
        ->postJson("/api/tickets/{$ticket->id}/assign", [
            'assignee_id' => $this->agent->id,
        ]);

    $response->assertStatus(200);
    $response->assertJsonPath('assignee_id', $this->agent->id);
});

// ── Sprint 5: Tags tests ────────────────────────────────

test('store accepts tags', function () {
    $response = $this->withHeaders(authHeader($this->agentToken))
        ->postJson('/api/tickets', [
            'subject' => 'Tagged ticket',
            'description' => 'Has tags',
            'tags' => ['bug', 'urgent', 'frontend'],
        ]);

    $response->assertStatus(201);
    $response->assertJsonPath('tags', ['bug', 'urgent', 'frontend']);
});

test('update accepts tags', function () {
    $ticket = Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->agent->id,
    ]);

    $response = $this->withHeaders(authHeader($this->agentToken))
        ->putJson("/api/tickets/{$ticket->id}", [
            'tags' => ['resolved', 'backend'],
        ]);

    $response->assertStatus(200);
    $response->assertJsonPath('tags', ['resolved', 'backend']);
});

// ── Sprint 5: Role guard tests (negative) ───────────────

test('customer cannot update ticket', function () {
    $ticket = Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->customer->id,
    ]);

    $response = $this->withHeaders(authHeader($this->customerToken))
        ->putJson("/api/tickets/{$ticket->id}", [
            'status' => 'resolved',
        ]);

    $response->assertStatus(403);
});

test('customer cannot assign ticket', function () {
    $ticket = Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->customer->id,
    ]);

    $response = $this->withHeaders(authHeader($this->customerToken))
        ->postJson("/api/tickets/{$ticket->id}/assign", [
            'assignee_id' => $this->agent->id,
        ]);

    $response->assertStatus(403);
});

test('customer cannot delete ticket', function () {
    $ticket = Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->customer->id,
    ]);

    $response = $this->withHeaders(authHeader($this->customerToken))
        ->deleteJson("/api/tickets/{$ticket->id}");

    $response->assertStatus(403);
});

// ── Sprint 5: DELETE route tests ────────────────────────

test('admin can delete ticket', function () {
    $ticket = Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->customer->id,
    ]);

    $response = $this->withHeaders(authHeader($this->adminToken))
        ->deleteJson("/api/tickets/{$ticket->id}");

    $response->assertStatus(204);
    expect(Ticket::find($ticket->id))->toBeNull();
});

test('agent cannot delete ticket', function () {
    $ticket = Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->customer->id,
    ]);

    $response = $this->withHeaders(authHeader($this->agentToken))
        ->deleteJson("/api/tickets/{$ticket->id}");

    $response->assertStatus(403);
});

// ── Sprint 5: Assignee filter test ──────────────────────

test('index filters by assignee_id', function () {
    $agent2 = User::factory()->create([
        'organization_id' => $this->org->id,
        'role' => 'agent',
    ]);

    Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->customer->id,
        'assignee_id' => $this->agent->id,
    ]);

    Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->customer->id,
        'assignee_id' => $agent2->id,
    ]);

    Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->customer->id,
        'assignee_id' => null,
    ]);

    $response = $this->withHeaders(authHeader($this->agentToken))
        ->getJson("/api/tickets?assignee_id={$this->agent->id}");

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(1);
    expect($response->json('data.0.assignee_id'))->toBe($this->agent->id);
});
