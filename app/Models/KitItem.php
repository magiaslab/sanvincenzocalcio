<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class KitItem extends Model
{
    use HasFactory;

    protected $guarded = [];

    public function athletes(): BelongsToMany
    {
        return $this->belongsToMany(Athlete::class)
            ->withPivot(['size', 'is_delivered', 'is_paid', 'delivered_at', 'paid_at'])
            ->withTimestamps();
    }
}
