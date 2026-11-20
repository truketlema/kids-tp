<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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

    public function categories(): HasMany
    {
        return $this->hasMany(Category::class);
    }
}
