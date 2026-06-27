<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use App\Models\ActivityLog;
use App\Models\SlaPolicy;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * GET /api/tickets — list tickets for the authed user's org.
     * Supports filtering by status, priority, assignee_id, and text search.
     */
    public function index(Request $request)
    {
        $query = Ticket::query();

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->string('status'));
        }

        // Filter by priority
        if ($request->filled('priority')) {
            $query->where('priority', $request->string('priority'));
        }

        // Filter by assignee_id
        if ($request->filled('assignee_id')) {
            $query->where('assignee_id', $request->integer('assignee_id'));
        }

        // Text search on subject + description
        if ($request->filled('search')) {
            $search = $request->string('search');
            $query->where(function ($q) use ($search) {
                $q->where('subject', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        return response()->json(
            $query->orderByDesc('id')->paginate(15)
        );
    }

    /**
     * GET /api/tickets/metrics — returns ticket counts grouped by status.
     */
    public function metrics(Request $request)
    {
        $counts = Ticket::query()
            ->selectRaw('status, count(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        $statuses = ['open', 'pending', 'resolved', 'closed'];
        $response = [];
        $total = 0;

        foreach ($statuses as $status) {
            $count = $counts[$status] ?? 0;
            $response[$status] = $count;
            $total += $count;
        }

        $response['total'] = $total;

        return response()->json($response);
    }

    /**
     * POST /api/tickets — create a ticket.
     * requester_id is forced to the authed user; org_id auto-stamped by TenantOwned.
     */
    public function store(Request $request)
    {
        $data = $request->validate([
            'subject' => ['required', 'string', 'max:255'],
            'description' => ['required', 'string'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,urgent'],
            'assignee_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:50'],
        ]);

        $ticket = Ticket::create([
            'subject' => $data['subject'],
            'description' => $data['description'],
            'status' => 'open',
            'priority' => $data['priority'] ?? 'medium',
            'tags' => $data['tags'] ?? null,
            'requester_id' => $request->user()->id,
            'assignee_id' => $data['assignee_id'] ?? null,
        ]);

        // Write Activity Log
        ActivityLog::create([
            'ticket_id' => $ticket->id,
            'user_id' => $request->user()->id,
            'action_description' => 'Ticket created',
        ]);

        return response()->json($ticket, 201);
    }

    /**
     * GET /api/tickets/{id} — show a single ticket with SLA eager loaded.
     */
    public function show(int $id)
    {
        $ticket = Ticket::with('requester', 'assignee')->findOrFail($id);

        $sla = SlaPolicy::where('organization_id', $ticket->organization_id)
            ->where('priority', $ticket->priority)
            ->first();

        $response = $ticket->toArray();
        $response['sla'] = $sla;

        return response()->json($response);
    }

    /**
     * PUT /api/tickets/{id} — update status, priority, assignee_id, tags.
     * Agent or admin only.
     */
    public function update(Request $request, int $id)
    {
        $this->authorizeAgentOrAdmin($request);

        $ticket = Ticket::findOrFail($id);

        $data = $request->validate([
            'status' => ['sometimes', 'string', 'in:open,pending,resolved,closed'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,urgent'],
            'assignee_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
            'tags' => ['sometimes', 'array'],
            'tags.*' => ['string', 'max:50'],
        ]);

        $oldStatus = $ticket->status;
        $oldAssignee = $ticket->assignee_id;

        $ticket->update($data);

        // Activity log for status change
        if (isset($data['status']) && $ticket->status !== $oldStatus) {
            ActivityLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'action_description' => "Status changed from {$oldStatus} to {$ticket->status}",
            ]);
        }

        // Activity log for assignee change
        if (array_key_exists('assignee_id', $data) && $ticket->assignee_id !== $oldAssignee) {
            $ticket->load('assignee');
            $name = $ticket->assignee ? $ticket->assignee->name : 'Unassigned';
            ActivityLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'action_description' => "Assignee changed to {$name}",
            ]);
        }

        return response()->json($ticket);
    }

    /**
     * POST /api/tickets/{id}/assign — assign or claim a ticket.
     * Agent or admin only.
     */
    public function assign(Request $request, int $id)
    {
        $this->authorizeAgentOrAdmin($request);

        $ticket = Ticket::findOrFail($id);

        $data = $request->validate([
            'assignee_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $oldAssignee = $ticket->assignee_id;

        $ticket->update(['assignee_id' => $data['assignee_id']]);

        if ($ticket->assignee_id !== $oldAssignee) {
            $ticket->load('assignee');
            $name = $ticket->assignee ? $ticket->assignee->name : 'Unassigned';
            ActivityLog::create([
                'ticket_id' => $ticket->id,
                'user_id' => $request->user()->id,
                'action_description' => "Assignee changed to {$name}",
            ]);
        }

        return response()->json($ticket);
    }

    /**
     * GET /api/tickets/{id}/activity — get activity logs for the ticket.
     */
    public function activity(int $id)
    {
        // TenantScope handles safety boundary implicitly via Ticket resolution
        $ticket = Ticket::findOrFail($id);

        $logs = ActivityLog::where('ticket_id', $id)
            ->with('user:id,name')
            ->orderByDesc('created_at')
            ->get();

        return response()->json($logs);
    }

    /**
     * DELETE /api/tickets/{id} — hard delete a ticket.
     * Admin only.
     */
    public function destroy(Request $request, int $id)
    {
        if ($request->user()->role !== 'admin') {
            abort(403, 'This action is restricted to administrators.');
        }

        $ticket = Ticket::findOrFail($id);
        $ticket->delete();

        return response()->noContent();
    }

    /**
     * Block customers from agent/admin-only actions.
     */
    private function authorizeAgentOrAdmin(Request $request): void
    {
        if ($request->user()->role === 'customer') {
            abort(403, 'This action is restricted to agents and administrators.');
        }
    }
}
