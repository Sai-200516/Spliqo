<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Invitation extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'invitations';

    protected $fillable = [
        'group_id',
        'email',
        'invited_by',
        'token',
        'expires_at',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    protected $attributes = [
        'status' => 'pending',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isPending(): bool
    {
        return $this->status === 'pending' && !$this->isExpired();
    }

    public function group(): ?Group
    {
        return Group::find($this->group_id);
    }

    public function inviter(): ?User
    {
        return User::find($this->invited_by);
    }
}
