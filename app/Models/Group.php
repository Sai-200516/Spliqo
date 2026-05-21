<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class Group extends Model
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'groups';

    protected $fillable = [
        'name',
        'description',
        'image',
        'currency',
        'created_by',
        'members',
        'is_archived',
        'archived_at',
    ];

    protected function casts(): array
    {
        return [
            'is_archived' => 'boolean',
            'archived_at' => 'datetime',
        ];
    }

    protected $attributes = [
        'currency'    => 'INR',
        'is_archived' => false,
    ];

    public function hasMember(string $userId): bool
    {
        $members = $this->members ?? [];
        foreach ($members as $member) {
            if (($member['user_id'] ?? '') === $userId) {
                return true;
            }
        }
        return false;
    }

    public function getMemberRole(string $userId): ?string
    {
        foreach ($this->members ?? [] as $member) {
            if (($member['user_id'] ?? '') === $userId) {
                return $member['role'] ?? 'member';
            }
        }
        return null;
    }

    public function isAdmin(string $userId): bool
    {
        return $this->getMemberRole($userId) === 'admin';
    }

    public function expenses()
    {
        return Expense::where('group_id', (string) $this->_id)->get();
    }

    public function creator(): ?User
    {
        return User::find($this->created_by);
    }
}
