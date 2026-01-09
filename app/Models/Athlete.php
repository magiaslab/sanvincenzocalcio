<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Athlete extends Model
{
    use HasFactory;

    protected $guarded = [];

    protected $casts = [
        'dob' => 'date',
        'medical_cert_expiry' => 'date',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'parent_id');
    }

    public function teams(): BelongsToMany
    {
        return $this->belongsToMany(Team::class)->withTimestamps();
    }

    // Metodo helper per retrocompatibilità (restituisce la prima squadra)
    public function team(): ?Team
    {
        return $this->teams()->first();
    }

    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class);
    }

    public function kitItems(): BelongsToMany
    {
        return $this->belongsToMany(KitItem::class)
            ->withPivot(['size', 'is_delivered', 'is_paid', 'delivered_at', 'paid_at'])
            ->withTimestamps();
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    public function convocations(): HasMany
    {
        return $this->hasMany(Convocation::class);
    }
}
