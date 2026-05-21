<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;
use MongoDB\Laravel\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $connection = 'mongodb';
    protected $collection = 'expenses';

    protected $fillable = [
        'title',
        'amount',
        'currency',
        'category',
        'tags',
        'paid_by',
        'split_type',
        'splits',
        'group_id',
        'notes',
        'attachments',
        'status',
        'created_by',
        'updated_by',
    ];

    protected function casts(): array
    {
        return [
            'amount'      => 'integer',
            'tags'        => 'array',
            'paid_by'     => 'array',
            'splits'      => 'array',
            'attachments' => 'array',
        ];
    }

    protected $attributes = [
        'currency'    => 'INR',
        'category'    => 'other',
        'split_type'  => 'equal',
        'status'      => 'active',
        'tags'        => [],
        'splits'      => [],
        'attachments' => [],
    ];

    public const CATEGORIES = [
        'food'          => 'Food',
        'travel'        => 'Travel',
        'shopping'      => 'Shopping',
        'entertainment' => 'Entertainment',
        'bills'         => 'Bills',
        'other'         => 'Other',
    ];

    public const SPLIT_TYPES = ['equal', 'percentage', 'exact', 'shares'];

    public function getAmountFormattedAttribute(): string
    {
        return '₹' . number_format($this->amount / 100, 2);
    }

    public function group(): ?Group
    {
        return Group::find($this->group_id);
    }

    public function paidByUser(): ?User
    {
        return User::find($this->paid_by['user_id'] ?? null);
    }

    public function getUserSplit(string $userId): ?array
    {
        foreach ($this->splits ?? [] as $split) {
            if (($split['user_id'] ?? '') === $userId) {
                return $split;
            }
        }
        return null;
    }
}
