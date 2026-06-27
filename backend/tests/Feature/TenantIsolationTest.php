<?php

use App\Models\Organization;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Support\Facades\Hash;

beforeEach(function () {
    // ── Org-A ──────────────────────────────────────────
    $this->orgA = Organization::factory()->create(['name' => 'Org Alpha']);
    $this->userA = User::factory()->create([
        'organization_id' => $this->orgA->id,
        'email' => 'user-a@example.com',
        'password' => Hash::make('password'),
        'role' => 'agent',
    ]);

    // ── Org-B ──────────────────────────────────────────
    $this->orgB = Organization::factory()->create(['name' => 'Org Beta']);
    $this->userB = User::factory()->create([
        'organization_id' => $this->orgB->id,
        'email' => 'user-b@example.com',
        'password' => Hash::make('password'),
        'role' => 'agent',
    ]);

    // Tickets for Org-A
    $this->ticketA1 = Ticket::factory()->create([
        'organization_id' => $this->orgA->id,
        'subject' => 'Alpha Ticket 1',
        'description' => 'Issue in Org A',
        'status' => 'open',
        'priority' => 'high',
        'requester_id' => $this->userA->id,
        'assignee_id' => $this->userA->id,
    ]);

    $this->ticketA2 = Ticket::factory()->create([
        'organization_id' => $this->orgA->id,
        'subject' => 'Alpha Ticket 2',
        'description' => 'Another issue in Org A',
        'status' => 'pending',
        'priority' => 'medium',
        'requester_id' => $this->userA->id,
        'assignee_id' => null,
    ]);

    // Tickets for Org-B
    $this->ticketB1 = Ticket::factory()->create([
        'organization_id' => $this->orgB->id,
        'subject' => 'Beta Ticket 1',
        'description' => 'Issue in Org B',
        'status' => 'open',
        'priority' => 'low',
        'requester_id' => $this->userB->id,
        'assignee_id' => $this->userB->id,
    ]);

    $this->ticketB2 = Ticket::factory()->create([
        'organization_id' => $this->orgB->id,
        'subject' => 'Beta Ticket 2',
        'description' => 'Another issue in Org B',
        'status' => 'resolved',
        'priority' => 'urgent',
        'requester_id' => $this->userB->id,
        'assignee_id' => null,
    ]);
});

test('org-A user only sees org-A tickets', function () {
    $this->actingAs($this->userA);

    $tickets = Ticket::all();

    expect($tickets)->toHaveCount(2);
    expect($tickets->pluck('id')->toArray())
        ->toContain($this->ticketA1->id)
        ->toContain($this->ticketA2->id)
        ->not->toContain($this->ticketB1->id)
        ->not->toContain($this->ticketB2->id);
});

test('org-A user cannot fetch org-B ticket viafindOrFail', function () {
    $this->actingAs($this->userA);

    // TenantScope applies on findOrFail — should throw ModelNotFoundException
    Ticket::findOrFail($this->ticketB1->id);
})->throws(\Illuminate\Database\Eloquent\ModelNotFoundException::class);

test('creating a ticket auto-stamps organization_id', function () {
    $this->actingAs($this->userA);

    $ticket = Ticket::create([
        'subject' => 'Auto-stamped ticket',
        'description' => 'Should get org A',
        'status' => 'open',
        'priority' => 'medium',
        'requester_id' => $this->userA->id,
    ]);

    expect($ticket->organization_id)->toBe($this->orgA->id);
});

test('org-B user only sees org-B tickets', function () {
    $this->actingAs($this->userB);

    $tickets = Ticket::all();

    expect($tickets)->toHaveCount(2);
    expect($tickets->pluck('id')->toArray())
        ->toContain($this->ticketB1->id)
        ->toContain($this->ticketB2->id)
        ->not->toContain($this->ticketA1->id)
        ->not->toContain($this->ticketA2->id);
});
