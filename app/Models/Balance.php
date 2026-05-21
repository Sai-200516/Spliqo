<?php

namespace App\Models;

use MongoDB\Laravel\Eloquent\Model;

class Balance extends Model
{
    protected $connection = 'mongodb';
    protected $collection = 'balances';

    protected $fillable = [
        'group_id',
        'from_user_id',
        'to_user_id',
        'amount',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }

    public function getAmountFormattedAttribute(): string
    {
        return '₹' . number_format(abs($this->amount) / 100, 2);
    }

    public function fromUser(): ?User
    {
        return User::find($this->from_user_id);
    }

    public function toUser(): ?User
    {
        return User::find($this->to_user_id);
    }
}
