<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

class Admin extends Authenticatable
{
    use HasApiTokens, Notifiable;

    protected $fillable = [
        'name',
        'phone_number',
        'phone_verified_at',
        'is_active',
        'role',
    ];

    protected $hidden = [
        'remember_token',
    ];

    protected $casts = [
        'phone_verified_at' => 'datetime',
        'is_active' => 'boolean',
    ];

    /**
     * Check if admin is active
     */
    public function isActive(): bool
    {
        return $this->is_active;
    }
}
