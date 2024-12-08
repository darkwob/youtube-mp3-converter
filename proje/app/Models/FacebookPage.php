<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FacebookPage extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'instagram_business_account_id',
        'name',
        'profile_picture_url',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function instagramAccounts()
{
    return $this->hasMany(InstagramAccount::class, 'instagram_business_account_id', 'instagram_business_account_id');
}

}

