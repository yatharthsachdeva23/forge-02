<?php

namespace App\Models;

use App\Models\Traits\TenantOwned;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Comment extends Model
{
    use HasFactory;
    use TenantOwned;

    protected $fillable = ['organization_id', 'ticket_id', 'user_id', 'body', 'is_internal'];

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
