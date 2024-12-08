<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class InstagramPost extends Model
{
    use HasFactory;

    protected $fillable = [
        'instagram_account_id',
        'post_id',
        'caption',
        'feed_link',
        'thumbnail_url',
        'media_url',
        'media_type',
        'comments_count',
        'like_count',
    ];

    public function instagramAccount()
    {
        return $this->belongsTo(InstagramAccount::class);
    }
}
