<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalkInPayment extends Model
{
    protected $attributes = ['amount' => 0, 'status' => 'pending', 'method' => 'cash'];

    protected $fillable = ['walk_in_entry_id', 'amount', 'status', 'received_by', 'paid_at'];

    protected function casts(): array
    {
        return ['amount' => 'decimal:2', 'paid_at' => 'datetime'];
    }

    /** @return BelongsTo<WalkInEntry, $this> */
    public function walkInEntry(): BelongsTo
    {
        return $this->belongsTo(WalkInEntry::class);
    }

    /** @return BelongsTo<User, $this> */
    public function receiver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}
