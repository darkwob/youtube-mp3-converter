<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstagramAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'instagram_business_account_id',
        'instagram_id',
        'username',
        'name',
        'profile_picture_url',
        'website',
        'followers_count',
        'follows_count',
        'media_count',
        'biography',
    ];

    // Relationship to User
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    // Relationship to FacebookPage
    public function facebookPage()
    {
        return $this->belongsTo(FacebookPage::class, 'instagram_business_account_id', 'instagram_business_account_id');
    }
}
