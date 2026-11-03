<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Commitment extends Model
{
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }
    Public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }
    public function ledgers(): HasMany
{
    return $this->hasMany(Ledger::class);
}
}
