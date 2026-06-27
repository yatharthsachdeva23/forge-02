<?php

namespace App\Models\Traits;

use App\Models\Scopes\TenantScope;
use Illuminate\Database\Eloquent\Model;

trait TenantOwned
{
    public static function bootTenantOwned(): void
    {
        static::addGlobalScope(new TenantScope());

        static::creating(function (Model $model): void {
            if (auth()->check() && ! $model->isDirty('organization_id')) {
                $model->organization_id = auth()->user()->organization_id;
            }
        });
    }
}
