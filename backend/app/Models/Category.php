<?php

namespace App\Models;

use App\Models\Commitment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Category extends Model
{
    public function commitments(): HasMany
    {
        return $this->hasMany(Commitment::class);
    }
    public function family(): BelongsTo
{
    return $this->belongsTo(Family::class);
}
}
