<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class ActivityLog extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'activity_logs';

    protected $fillable = [
        'user_id',
        'action',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
    ];

    // NOTE: Do NOT cast 'properties' as 'array' — MongoDB returns native PHP arrays
    // and the 'array' cast calls json_decode() on an array, causing a TypeError.

    public static function log(string $action, string $subjectType, string $subjectId, array $properties = []): void
    {
        try {
            static::create([
                'user_id'      => (string) auth()->id(),
                'action'       => $action,
                'subject_type' => $subjectType,
                'subject_id'   => $subjectId,
                'properties'   => $properties,
                'ip_address'   => request()->ip(),
                'user_agent'   => request()->userAgent(),
            ]);
        } catch (\Throwable) {
            // Never let logging crash the request
        }
    }

    public function user(): ?User
    {
        return User::find($this->user_id);
    }
}
