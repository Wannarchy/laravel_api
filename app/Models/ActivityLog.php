<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public const ACTOR_ADMIN = 'admin';

    public const ACTOR_USER = 'user';

    public const ACTOR_GUEST = 'guest';

    public $timestamps = false;

    protected $table = 'logs';

    protected $fillable = [
        'actor_type',
        'user_id',
        'action',
        'target_type',
        'target_id',
        'ip',
        'details',
        'created_at',
    ];

    protected function casts(): array
    {
        return [
            'created_at' => 'datetime',
        ];
    }

    public static function normalizeDetails(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if (is_array($value)) {
            if (isset($value['method'])) {
                return strtoupper((string) $value['method']);
            }

            return null;
        }

        if (! is_string($value)) {
            return is_scalar($value) ? strtoupper((string) $value) : null;
        }

        $trimmed = trim($value);

        if ($trimmed === '') {
            return null;
        }

        if ($trimmed[0] === '{' || $trimmed[0] === '[') {
            $decoded = json_decode($trimmed, true);

            if (is_array($decoded) && isset($decoded['method'])) {
                return strtoupper((string) $decoded['method']);
            }

            if (is_string($decoded)) {
                return strtoupper($decoded);
            }
        }

        return strtoupper($trimmed);
    }

    protected function details(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn (mixed $value) => self::normalizeDetails($value),
            set: fn (mixed $value) => self::normalizeDetails($value),
        );
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id');
    }
}
