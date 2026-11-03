<?php

namespace App\Models;

use App\Models\Family;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;
#[Fillable([
    'name',
    'date_of_birth',])]
class Child extends Model
{

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function ledgers(): HasMany
{
    return $this->hasMany(Ledger::class);
}
}
