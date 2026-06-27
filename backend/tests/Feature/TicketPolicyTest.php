<?php

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->org = Organization::factory()->create();

    $this->admin = User::factory()->create([
        'organization_id' => $this->org->id,
        'role' => 'admin',
    ]);
    $this->agent = User::factory()->create([
        'organization_id' => $this->org->id,
        'role' => 'agent',
    ]);
    $this->customer = User::factory()->create([
        'organization_id' => $this->org->id,
        'role' => 'customer',
    ]);

    $this->adminToken = $this->admin->createToken('test')->plainTextToken;
    $this->agentToken = $this->agent->createToken('test')->plainTextToken;
    $this->customerToken = $this->customer->createToken('test')->plainTextToken;

    $this->ticket = Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->customer->id,
        'assignee_id' => $this->agent->id,
    ]);
});

function h(string $token): array
{
    return ['Authorization' => "Bearer {$token}"];
}

/* ── Role guard tests ─────────────────────────────────── */

test('customer cannot update a ticket', function () {
    $response = $this->withHeaders(h($this->customerToken))
        ->putJson("/api/tickets/{$this->ticket->id}", ['status' => 'closed']);

    $response->assertStatus(403);
});

test('customer cannot assign a ticket', function () {
    $response = $this->withHeaders(h($this->customerToken))
        ->postJson("/api/tickets/{$this->ticket->id}/assign", [
            'assignee_id' => $this->agent->id,
        ]);

    $response->assertStatus(403);
});

test('customer cannot delete a ticket', function () {
    $response = $this->withHeaders(h($this->customerToken))
        ->deleteJson("/api/tickets/{$this->ticket->id}");

    $response->assertStatus(403);
});

test('agent cannot delete a ticket', function () {
    $response = $this->withHeaders(h($this->agentToken))
        ->deleteJson("/api/tickets/{$this->ticket->id}");

    $response->assertStatus(403);
});

test('admin can delete a ticket', function () {
    $response = $this->withHeaders(h($this->adminToken))
        ->deleteJson("/api/tickets/{$this->ticket->id}");

    $response->assertStatus(204);
    $this->assertDatabaseMissing('tickets', ['id' => $this->ticket->id]);
});

/* ── Tags tests ───────────────────────────────────────── */

test('store accepts tags array', function () {
    $response = $this->withHeaders(h($this->agentToken))
        ->postJson('/api/tickets', [
            'subject' => 'Tagged ticket',
            'description' => 'Has labels',
            'tags' => ['bug', 'search'],
        ]);

    $response->assertStatus(201);
    $ticket = \App\Models\Ticket::find($response->json('id'));
    expect($ticket->tags)->toBe(['bug', 'search']);
});

test('update accepts tags array', function () {
    $response = $this->withHeaders(h($this->agentToken))
        ->putJson("/api/tickets/{$this->ticket->id}", [
            'tags' => ['updated', 'labels'],
        ]);

    $response->assertStatus(200);
    $ticket = \App\Models\Ticket::find($this->ticket->id);
    expect($ticket->tags)->toBe(['updated', 'labels']);
});

/* ── Assignee filter test ─────────────────────────────── */

test('index filters by assignee_id', function () {
    Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->customer->id,
        'assignee_id' => $this->agent->id,
    ]);
    Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->customer->id,
        'assignee_id' => null,
    ]);

    $response = $this->withHeaders(h($this->agentToken))
        ->getJson("/api/tickets?assignee_id={$this->agent->id}");

    $response->assertStatus(200);
    expect($response->json('data'))->toHaveCount(2);
});
