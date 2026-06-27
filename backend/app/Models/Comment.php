<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Comments do NOT have an organization_id column.
 * Tenant isolation is enforced by always querying comments through
 * a TenantScoped Ticket (e.g. $ticket->comments()).
 * Never query Comment directly without a scoped ticket constraint,
 * as that would risk cross-tenant data leaks.
 */
class Comment extends Model
{
    protected $fillable = ['ticket_id', 'user_id', 'body', 'is_internal'];

    protected $casts = ['is_internal' => 'boolean'];

    public function ticket(): BelongsTo
    {
        return $this->belongsTo(Ticket::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
