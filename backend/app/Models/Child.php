<?php

namespace App\Models;

use App\Models\Family;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'date_of_birth',
    'avatar',
    'budget_allocation',
])]
class Child extends Model
{
    public const AVATARS = ['bear', 'cat', 'dog', 'flower', 'rocket', 'star'];

    protected function casts(): array
    {
        return [
            'budget_allocation' => 'array',
        ];
    }

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }

    public function ledgers(): HasMany
{
    return $this->hasMany(Ledger::class);
}
}
