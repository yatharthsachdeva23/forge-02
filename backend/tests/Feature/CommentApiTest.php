<?php

use App\Models\Comment;
use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;

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

    $this->ticket = Ticket::factory()->create([
        'organization_id' => $this->org->id,
        'requester_id' => $this->customer->id,
        'assignee_id' => $this->agent->id,
    ]);
});

function hdr(string $token): array
{
    return ['Authorization' => "Bearer {$token}"];
}

test('agent can see all comments including internal', function () {
    Comment::factory()->create([
        'organization_id' => $this->org->id,
        'ticket_id' => $this->ticket->id,
        'user_id' => $this->agent->id,
        'body' => 'Public update',
        'is_internal' => false,
    ]);
    Comment::factory()->create([
        'organization_id' => $this->org->id,
        'ticket_id' => $this->ticket->id,
        'user_id' => $this->agent->id,
        'body' => 'Secret internal note',
        'is_internal' => true,
    ]);

    $response = $this->withHeaders(hdr($this->agentToken))
        ->getJson("/api/tickets/{$this->ticket->id}/comments");

    $response->assertStatus(200);
    expect($response->json())->toHaveCount(2);
});

test('customer cannot see internal comments', function () {
    Comment::factory()->create([
        'organization_id' => $this->org->id,
        'ticket_id' => $this->ticket->id,
        'user_id' => $this->agent->id,
        'body' => 'Public reply',
        'is_internal' => false,
    ]);
    Comment::factory()->create([
        'organization_id' => $this->org->id,
        'ticket_id' => $this->ticket->id,
        'user_id' => $this->agent->id,
        'body' => 'Internal agent discussion',
        'is_internal' => true,
    ]);

    $response = $this->withHeaders(hdr($this->customerToken))
        ->getJson("/api/tickets/{$this->ticket->id}/comments");

    $response->assertStatus(200);
    expect($response->json())->toHaveCount(1);
    expect($response->json()[0]['is_internal'])->toBeFalse();
    expect($response->json()[0]['body'])->toBe('Public reply');
});

test('customer can post a comment', function () {
    $response = $this->withHeaders(hdr($this->customerToken))
        ->postJson("/api/tickets/{$this->ticket->id}/comments", [
            'body' => 'My issue is still happening',
        ]);

    $response->assertStatus(201);
    $response->assertJsonPath('body', 'My issue is still happening');
    $response->assertJsonPath('user_id', $this->customer->id);
    $response->assertJsonPath('is_internal', false);
});

test('customer cannot set is_internal to true', function () {
    $response = $this->withHeaders(hdr($this->customerToken))
        ->postJson("/api/tickets/{$this->ticket->id}/comments", [
            'body' => 'Trying to sneak internal',
            'is_internal' => true,
        ]);

    $response->assertStatus(201);
    // is_internal should be forced to false despite the request
    $response->assertJsonPath('is_internal', false);
});

test('agent can post internal comment', function () {
    $response = $this->withHeaders(hdr($this->agentToken))
        ->postJson("/api/tickets/{$this->ticket->id}/comments", [
            'body' => 'Customer seems confused, escalate?',
            'is_internal' => true,
        ]);

    $response->assertStatus(201);
    $response->assertJsonPath('is_internal', true);
    $response->assertJsonPath('user_id', $this->agent->id);
});

test('comment on other org ticket returns 404', function () {
    $otherOrg = Organization::factory()->create();
    $otherUser = User::factory()->create(['organization_id' => $otherOrg->id]);
    $otherTicket = Ticket::factory()->create([
        'organization_id' => $otherOrg->id,
        'requester_id' => $otherUser->id,
    ]);

    $response = $this->withHeaders(hdr($this->agentToken))
        ->getJson("/api/tickets/{$otherTicket->id}/comments");

    $response->assertStatus(404);
});
