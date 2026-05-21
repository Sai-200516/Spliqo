<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Transaction extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'transactions';

    protected $fillable = [
        'from_user_id',
        'to_user_id',
        'group_id',
        'expense_ids',
        'amount',
        'status',
        'razorpay_order_id',
        'razorpay_payment_id',
        'razorpay_signature',
        'failure_reason',
        'webhook_payload',
        'settled_at',
    ];

    protected function casts(): array
    {
        return [
            'amount'      => 'integer',
            'expense_ids' => 'array',
            'settled_at'  => 'datetime',
        ];
    }

    protected $attributes = [
        'status'      => 'pending',
        'expense_ids' => [],
    ];

    public const STATUSES = ['pending', 'completed', 'failed'];

    public function getAmountFormattedAttribute(): string
    {
        return '₹' . number_format($this->amount / 100, 2);
    }

    public function fromUser(): ?User
    {
        return User::find($this->from_user_id);
    }

    public function toUser(): ?User
    {
        return User::find($this->to_user_id);
    }

    public function group(): ?Group
    {
        return Group::find($this->group_id);
    }
}
