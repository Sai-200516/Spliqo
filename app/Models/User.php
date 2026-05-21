<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Auth\Authenticatable;
use Illuminate\Auth\MustVerifyEmail as MustVerifyEmailTrait;
use Illuminate\Auth\Passwords\CanResetPassword;
use Illuminate\Contracts\Auth\Access\Authorizable as AuthorizableContract;
use Illuminate\Contracts\Auth\Authenticatable as AuthenticatableContract;
use Illuminate\Contracts\Auth\CanResetPassword as CanResetPasswordContract;
use Illuminate\Foundation\Auth\Access\Authorizable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use MongoDB\Laravel\Eloquent\Model;

class User extends Model implements
    AuthenticatableContract,
    AuthorizableContract,
    CanResetPasswordContract,
    MustVerifyEmail
{
    use Authenticatable,
        Authorizable,
        CanResetPassword,
        MustVerifyEmailTrait,
        Notifiable,
        HasApiTokens;

    protected $connection = 'mongodb';
    protected $collection = 'users';

    protected $fillable = [
        'name', 'email', 'password', 'google_id', 'avatar', 'bio',
        'timezone', 'preferred_currency', 'theme_preference',
        'notification_preferences', 'is_admin', 'is_active',
        'last_login_at', 'email_verified_at',
    ];

    protected $hidden = [
        'password', 'remember_token', 'google_id',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_login_at'     => 'datetime',
            'password'          => 'hashed',
            'is_admin'          => 'boolean',
            'is_active'         => 'boolean',
        ];
    }

    protected $attributes = [
        'timezone'           => 'Asia/Kolkata',
        'preferred_currency' => 'INR',
        'theme_preference'   => 'system',
        'is_admin'           => false,
        'is_active'          => true,
    ];

    public function getUnreadNotificationsCountAttribute(): int
    {
        return \App\Models\AppNotification::where('user_id', (string) $this->_id)
            ->where('is_read', false)
            ->count();
    }

    /** Query builder helper so the layout can call ->notifications()->where() */
    public function notifications(): \MongoDB\Laravel\Eloquent\Builder
    {
        return \App\Models\AppNotification::where('user_id', (string) $this->_id);
    }
}

