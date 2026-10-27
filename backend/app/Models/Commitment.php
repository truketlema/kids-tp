<?php

namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

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
}
