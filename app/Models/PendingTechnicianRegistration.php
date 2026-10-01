<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property string $id
 * @property string $email
 * @property array<string, mixed> $payload
 * @property string $code_hash
 * @property int $attempts
 * @property Carbon $expires_at
 * @property Carbon $registration_expires_at
 */
#[Fillable(['email', 'payload', 'code_hash', 'attempts', 'expires_at', 'registration_expires_at'])]
class PendingTechnicianRegistration extends Model
{
    use HasUuids;

    protected function casts(): array
    {
        return [
            'payload' => 'encrypted:array',
            'expires_at' => 'datetime',
            'registration_expires_at' => 'datetime',
        ];
    }
}
