<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Ticket;
use Illuminate\Http\Request;

class TicketController extends Controller
{
    /**
     * GET /api/tickets — list tickets for the authed user's org.
     * Supports filtering by status, priority, and text search (subject + description).
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
        ]);

        $ticket = Ticket::create([
            'subject' => $data['subject'],
            'description' => $data['description'],
            'status' => 'open',
            'priority' => $data['priority'] ?? 'medium',
            'requester_id' => $request->user()->id,
            'assignee_id' => $data['assignee_id'] ?? null,
        ]);

        return response()->json($ticket, 201);
    }

    /**
     * GET /api/tickets/{id} — show a single ticket (TenantScope handles ownership).
     */
    public function show(int $id)
    {
        $ticket = Ticket::findOrFail($id);

        return response()->json($ticket);
    }

    /**
     * PUT /api/tickets/{id} — update status, priority, assignee_id.
     */
    public function update(Request $request, int $id)
    {
        $ticket = Ticket::findOrFail($id);

        $data = $request->validate([
            'status' => ['sometimes', 'string', 'in:open,pending,resolved,closed'],
            'priority' => ['sometimes', 'string', 'in:low,medium,high,urgent'],
            'assignee_id' => ['sometimes', 'nullable', 'integer', 'exists:users,id'],
        ]);

        $ticket->update($data);

        return response()->json($ticket);
    }

    /**
     * POST /api/tickets/{id}/assign — assign or claim a ticket.
     */
    public function assign(Request $request, int $id)
    {
        $ticket = Ticket::findOrFail($id);

        $data = $request->validate([
            'assignee_id' => ['required', 'integer', 'exists:users,id'],
        ]);

        $ticket->update(['assignee_id' => $data['assignee_id']]);

        return response()->json($ticket);
    }
}
