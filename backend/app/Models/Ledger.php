<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Ledger extends Model
{
    public function child(): BelongsTo
    {
        return $this->belongsTo(Child::class);
    }

    public function recordedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }

    public function commitment(): BelongsTo
    {
        return $this->belongsTo(Commitment::class);
    }
}
