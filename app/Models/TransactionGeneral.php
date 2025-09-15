<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TransactionGeneral extends Model
{
    use SoftDeletes;

    /**
     * Get the parent transactionable model (academy, membership, booking, and hairpro).
     */
    public function transactionable(): MorphTo
    {
        return $this->morphTo();
    }
}
