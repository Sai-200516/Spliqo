<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class AppNotification extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'notifications';

    protected $fillable = [
        'user_id',
        'type',
        'title',
        'message',
        'data',
        'is_read',
        'read_at',
    ];

    protected function casts(): array
    {
        return [
            'is_read' => 'boolean',
            'read_at' => 'datetime',
        ];
    }

    protected $attributes = [
        'is_read' => false,
        'data'    => [],
    ];

    public function user(): ?User
    {
        return User::find($this->user_id);
    }

    public function markRead(): void
    {
        $this->update(['is_read' => true, 'read_at' => now()]);
    }
}
