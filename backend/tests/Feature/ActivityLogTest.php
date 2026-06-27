<?php

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use App\Models\SlaPolicy;
use App\Models\ActivityLog;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    $this->org = Organization::factory()->create();
    $this->agent = User::factory()->create([
        'organization_id' => $this->org->id,
        'role' => 'agent',
    ]);
    $this->customer = User::factory()->create([
        'organization_id' => $this->org->id,
        'role' => 'customer',
    ]);

    $this->agentToken = $this->agent->createToken('test')->plainTextToken;
    $this->customerToken = $this->customer->createToken('test')->plainTextToken;
});

function activityLogAuthHeader(string $token): array
{
    return ['Authorization' => "Bearer {$token}"];
}

test('creating ticket writes activity log', function () {
    $response = $this->withHeaders(activityLogAuthHeader($this->agentToken))
        ->postJson('/api/tickets', [
            'subject' => 'New Ticket',
            'description' => 'Help needed',
        ]);

    $response->assertStatus(201);
    $ticketId = $response->json('id');

    $this->assertDatabaseHas('activity_logs', [
        'ticket_id' => $ticketId,
        'action_description' => 'Ticket created',
    ]);
});

test('updating status writes activity log', function () {
    $ticket = Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->customer->id,
        'status' => 'open',
    ]);

    $response = $this->withHeaders(activityLogAuthHeader($this->agentToken))
        ->putJson("/api/tickets/{$ticket->id}", [
            'status' => 'pending',
        ]);

    $response->assertStatus(200);

    $this->assertDatabaseHas('activity_logs', [
        'ticket_id' => $ticket->id,
        'action_description' => 'Status changed from open to pending',
    ]);
});

test('adding comment writes activity log', function () {
    $ticket = Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->customer->id,
    ]);

    $response = $this->withHeaders(activityLogAuthHeader($this->agentToken))
        ->postJson("/api/tickets/{$ticket->id}/comments", [
            'body' => 'I am looking into this.',
        ]);

    $response->assertStatus(201);

    $this->assertDatabaseHas('activity_logs', [
        'ticket_id' => $ticket->id,
        'action_description' => 'Comment added',
    ]);
});

test('metrics endpoint aggregates counts correctly', function () {
    Ticket::factory()->create(['organization_id' => $this->org->id, 'requester_id' => $this->customer->id, 'status' => 'open']);
    Ticket::factory()->create(['organization_id' => $this->org->id, 'requester_id' => $this->customer->id, 'status' => 'open']);
    Ticket::factory()->create(['organization_id' => $this->org->id, 'requester_id' => $this->customer->id, 'status' => 'pending']);

    $response = $this->withHeaders(activityLogAuthHeader($this->agentToken))
        ->getJson('/api/tickets/metrics');

    $response->assertStatus(200);
    expect($response->json('open'))->toBe(2);
    expect($response->json('pending'))->toBe(1);
    expect($response->json('total'))->toBe(3);
});

test('ticket details load SLA correctly', function () {
    $policy = SlaPolicy::create([
        'organization_id' => $this->org->id,
        'priority' => 'high',
        'response_time_hours' => 4,
        'resolution_time_hours' => 24,
    ]);

    $ticket = Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->customer->id,
        'priority' => 'high',
    ]);

    $response = $this->withHeaders(activityLogAuthHeader($this->agentToken))
        ->getJson("/api/tickets/{$ticket->id}");

    $response->assertStatus(200);
    expect($response->json('sla.resolution_time_hours'))->toBe(24);
});
