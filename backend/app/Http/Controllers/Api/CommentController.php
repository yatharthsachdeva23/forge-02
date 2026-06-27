<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\Ticket;
use Illuminate\Http\Request;

class CommentController extends Controller
{
    /**
     * GET /api/tickets/{ticket_id}/comments
     * Customers cannot see internal comments.
     */
    public function index(int $ticketId)
    {
        // Verify the ticket belongs to the authed user's org (TenantScope)
        $ticket = Ticket::findOrFail($ticketId);

        $query = Comment::where('ticket_id', $ticketId);

        // Customers: filter out internal comments
        if (auth()->user()->role === 'customer') {
            $query->where('is_internal', false);
        }

        $comments = $query->orderBy('id')->get();

        return response()->json($comments);
    }

    /**
     * POST /api/tickets/{ticket_id}/comments
     * user_id auto-stamped. is_internal only honoured for agents/admins.
     */
    public function store(Request $request, int $ticketId)
    {
        // Verify ticket belongs to org
        $ticket = Ticket::findOrFail($ticketId);

        $data = $request->validate([
            'body' => ['required', 'string'],
            'is_internal' => ['sometimes', 'boolean'],
        ]);

        $isInternal = false;

        // Only agents/admins can set is_internal = true
        if (auth()->user()->role !== 'customer') {
            $isInternal = $data['is_internal'] ?? false;
        }

        $comment = Comment::create([
            'ticket_id' => $ticketId,
            'user_id' => auth()->user()->id,
            'body' => $data['body'],
            'is_internal' => $isInternal,
        ]);

        return response()->json($comment, 201);
    }
}
