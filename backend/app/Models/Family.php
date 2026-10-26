<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

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
