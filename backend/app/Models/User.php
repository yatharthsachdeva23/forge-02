<?php

namespace App\Models;

use App\Models\Traits\TenantOwned;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use Notifiable;
    use TenantOwned;

    protected $fillable = ['organization_id', 'name', 'email', 'password', 'role'];

    protected $hidden = ['password', 'remember_token'];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }

    public function requesterTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'requester_id');
    }

    public function assigneeTickets(): HasMany
    {
        return $this->hasMany(Ticket::class, 'assignee_id');
    }

    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }
}
