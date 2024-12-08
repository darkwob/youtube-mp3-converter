<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'facebook_id',
        'email',
        'name',
        'password',
        'access_token',
        'token_expires_at',
    ];

    public function instagramAccounts()
    {
        return $this->hasMany(InstagramAccount::class, 'user_id');
    }

}
