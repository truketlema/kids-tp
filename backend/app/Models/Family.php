<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Attributes\Fillable;
#[Fillable([
    'family_name',
    'conversion_rate',
    'daily_deduction_cap',
])]
class Family extends Model
{
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
    public function children(): HasMany
    {
        return $this->hasMany(Child::class);
    }
}
