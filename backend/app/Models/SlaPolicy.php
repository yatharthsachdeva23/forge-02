<?php

namespace App\Models;

use App\Models\Traits\TenantOwned;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SlaPolicy extends Model
{
    use TenantOwned;

    protected $fillable = [
        'organization_id',
        'priority',
        'response_time_hours',
        'resolution_time_hours',
    ];

    public function organization(): BelongsTo
    {
        return $this->belongsTo(Organization::class);
    }
}
