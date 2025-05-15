<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;


class MeasurementUnit extends Model
{
    protected $fillable = [
        'code',
        'lt_name',
        'en_name',
        'is_default',
        'user_id',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    // patogus label formoms:
    public function getLabelAttribute(): string
    {
        return "{$this->code} – {$this->lt_name}";
    }

    public function scopeForCurrentUser($query)
    {
        return $query
            ->where('is_default', true)
            ->orWhere('user_id', Auth::id());
    }
}
