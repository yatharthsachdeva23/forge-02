<?php

namespace Database\Seeders;

use App\Models\Organization;
use App\Models\SlaPolicy;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── Organization ──────────────────────────────────
        $org = Organization::create([
            'name' => 'PulseDesk Support',
        ]);

        // ── Users ─────────────────────────────────────────
        $admin = User::create([
            'organization_id' => $org->id,
            'name' => 'Admin User',
            'email' => 'admin@pulsedesk.com',
            'password' => Hash::make('password'),
            'role' => 'admin',
        ]);

        $agent1 = User::create([
            'organization_id' => $org->id,
            'name' => 'Agent One',
            'email' => 'agent1@pulsedesk.com',
            'password' => Hash::make('password'),
            'role' => 'agent',
        ]);

        $agent2 = User::create([
            'organization_id' => $org->id,
            'name' => 'Agent Two',
            'email' => 'agent2@pulsedesk.com',
            'password' => Hash::make('password'),
            'role' => 'agent',
        ]);

        $customer1 = User::create([
            'organization_id' => $org->id,
            'name' => 'Customer One',
            'email' => 'customer1@pulsedesk.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        $customer2 = User::create([
            'organization_id' => $org->id,
            'name' => 'Customer Two',
            'email' => 'customer2@pulsedesk.com',
            'password' => Hash::make('password'),
            'role' => 'customer',
        ]);

        // ── SLA Policies (one per priority) ───────────────
        $slaData = [
            ['priority' => 'low',    'response_time_hours' => 24, 'resolution_time_hours' => 168],
            ['priority' => 'medium', 'response_time_hours' => 8,  'resolution_time_hours' => 72],
            ['priority' => 'high',   'response_time_hours' => 4,  'resolution_time_hours' => 24],
            ['priority' => 'urgent', 'response_time_hours' => 1,  'resolution_time_hours' => 8],
        ];

        foreach ($slaData as $sla) {
            SlaPolicy::create(array_merge($sla, ['organization_id' => $org->id]));
        }

        // ── Tickets (12 spread across status/priority) ────
        $tickets = [
            ['subject' => 'Login page not loading',       'description' => 'Users see a blank page when navigating to /login.',         'status' => 'open',     'priority' => 'high',   'requester_id' => $customer1->id, 'assignee_id' => $agent1->id],
            ['subject' => 'Password reset email delayed', 'description' => 'Reset email takes 30+ minutes to arrive.',                    'status' => 'open',     'priority' => 'medium', 'requester_id' => $customer2->id, 'assignee_id' => $agent2->id],
            ['subject' => 'API returns 500 on /tickets',   'description' => 'GET /api/tickets intermittently throws 500.',                'status' => 'pending',  'priority' => 'urgent', 'requester_id' => $customer1->id, 'assignee_id' => $agent1->id],
            ['subject' => 'Cannot upload attachments',     'description' => 'File upload fails with a 413 error.',                        'status' => 'pending',  'priority' => 'high',   'requester_id' => $customer2->id, 'assignee_id' => null],
            ['subject' => 'Dashboard charts not rendering','description' => 'Charts are blank after the latest deploy.',                   'status' => 'open',     'priority' => 'low',    'requester_id' => $customer1->id, 'assignee_id' => $agent2->id],
            ['subject' => 'SSO integration request',       'description' => 'Need SSO via Google Workspace for the team.',                'status' => 'closed',   'priority' => 'medium', 'requester_id' => $customer2->id, 'assignee_id' => $agent1->id],
            ['subject' => 'Slack notifications stopped',   'description' => 'No Slack alerts for new tickets since yesterday.',            'status' => 'resolved', 'priority' => 'medium', 'requester_id' => $customer1->id, 'assignee_id' => $agent2->id],
            ['subject' => 'Duplicate ticket IDs',          'description' => 'Two tickets created with the same ID.',                       'status' => 'resolved', 'priority' => 'high',   'requester_id' => $customer2->id, 'assignee_id' => $agent1->id],
            ['subject' => 'Export to CSV failing',         'description' => 'CSV export downloads an empty file.',                         'status' => 'open',     'priority' => 'low',    'requester_id' => $customer1->id, 'assignee_id' => null],
            ['subject' => 'Mobile app push notifications', 'description' => 'Push notifications not arriving on iOS devices.',              'status' => 'closed',   'priority' => 'low',    'requester_id' => $customer2->id, 'assignee_id' => $agent2->id],
            ['subject' => 'Rate limiting too aggressive',  'description' => 'API returns 429 after just 5 requests per minute.',           'status' => 'pending',  'priority' => 'urgent', 'requester_id' => $customer1->id, 'assignee_id' => $agent1->id],
            ['subject' => 'Dark mode flicker',             'description' => 'UI flickers between light and dark mode on refresh.',         'status' => 'resolved', 'priority' => 'low',    'requester_id' => $customer2->id, 'assignee_id' => null],
        ];

        // Add tags to two tickets for demonstration
        $tickets[0]['tags'] = ['bug', 'login', 'P1'];
        $tickets[4]['tags'] = ['feature-request', 'UI'];

        foreach ($tickets as $ticket) {
            $createdTicket = Ticket::create(array_merge($ticket, ['organization_id' => $org->id]));

            // Add a public comment from the customer
            \App\Models\Comment::create([
                'organization_id' => $org->id,
                'ticket_id' => $createdTicket->id,
                'user_id' => $createdTicket->requester_id,
                'body' => "This is a follow-up comment for ticket: {$createdTicket->subject}.",
                'is_internal' => false,
            ]);

            // Add an internal comment from the assignee (if assigned)
            if ($createdTicket->assignee_id) {
                \App\Models\Comment::create([
                    'organization_id' => $org->id,
                    'ticket_id' => $createdTicket->id,
                    'user_id' => $createdTicket->assignee_id,
                    'body' => "Internal note: Reviewing logs for this request.",
                    'is_internal' => true,
                ]);
            }
        }

        // Seed 2 tickets with tags
        Ticket::create([
            'organization_id' => $org->id,
            'subject' => 'Bug: Search results empty',
            'description' => 'Search returns no results for valid queries.',
            'status' => 'open',
            'priority' => 'high',
            'tags' => json_encode(['bug', 'search', 'frontend']),
            'requester_id' => $customer1->id,
            'assignee_id' => $agent1->id,
        ]);

        Ticket::create([
            'organization_id' => $org->id,
            'subject' => 'Feature: Dark mode toggle',
            'description' => 'Add a dark mode toggle in settings.',
            'status' => 'open',
            'priority' => 'low',
            'tags' => json_encode(['feature', 'ui']),
            'requester_id' => $customer2->id,
            'assignee_id' => null,
        ]);
    }
}
