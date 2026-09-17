<?php

namespace App\Models;

use Database\Factories\WalkInStatusHistoryFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WalkInStatusHistory extends Model
{
    /** @use HasFactory<WalkInStatusHistoryFactory> */
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'walk_in_entry_id',
        'from_status',
        'to_status',
        'actor_id',
        'reason',
        'metadata',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
            'created_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<WalkInEntry, $this> */
    public function walkInEntry(): BelongsTo
    {
        return $this->belongsTo(WalkInEntry::class);
    }

    /** @return BelongsTo<User, $this> */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
