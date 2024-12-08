<?php

namespace App\Http\Controllers;

use App\Services\UserService;
use Illuminate\Http\Request;
use Exception;

class UserController extends Controller
{
    protected $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function loginWithFacebook(Request $request)
    {
        $code = $request->query('code');

        if ($code) {
            try {
                // Token alma işlemi
                $newToken = $this->userService->exchangeCodeForToken($code);

                $accessTokenValue = $newToken['access_token'];

                // Token'i veritabanına kaydetme veya kullanma (örneğin repository ile)
                // $this->userService->saveTokenToDatabase($accessTokenValue);

                // Kullanıcı bilgilerini almak için örnek bir veri
                // Kullanıcı verisini session yerine veritabanına kaydedebilirsiniz
                session(['access_token' => $accessTokenValue]);

                return view('igtest', ['token' => $newToken]);

            } catch (Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }

        return response()->json(['error' => 'Code not found'], 400);
    }

    public function getUserInfo($instagramId, $username)
    {
        $accessTokenValue = session('access_token');

        if (!$accessTokenValue) {
            return response()->json(['error' => 'Access token bulunamadı.'], 400);
        }

        try {
            $userInfo = $this->userService->getUserDataFromFacebook($accessTokenValue, $instagramId, $username);
            return view('getUserProfile', ['userInfo' => $userInfo]);

        } catch (Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
