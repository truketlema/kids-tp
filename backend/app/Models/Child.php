<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'name',
    'date_of_birth',
    'avatar',
    'budget_allocation',
    'monthly_savings_goal',
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

    public function commitments(): BelongsToMany
    {
        return $this->belongsToMany(Commitment::class, 'child_commitment')
            ->withPivot('tp_override');
    }
}
