<?php

namespace App\Services;

use Illuminate\Support\Str;

class TokenService
{
    public static function generateToken()
    {
        return strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4)) . '-' . strtoupper(Str::random(4));
    }
}
