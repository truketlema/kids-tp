<?php

namespace App\Models;

use App\Models\Family;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Child extends Model
{

    public function family(): BelongsTo
    {
        return $this->belongsTo(Family::class);
    }
}
