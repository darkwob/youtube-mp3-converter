<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class FacebookController extends Controller
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function redirectToFacebook()
    {
        $loginUrl = $this->userService->generateLoginUrl();
        return redirect($loginUrl);
    }

    public function handleFacebookCallback(Request $request)
    {
        $code = $request->query('code');
        if (!$code) {
            return redirect()->route('login')->with('error', 'Facebook login failed: No code provided.');
        }

        try {
            $user = $this->userService->handleUserLogin($code);
            Auth::login($user);

            $accessToken = Session::get('access_token');


            if ($this->userService->findFacebookPages($user->id)) {
                return redirect()->route('select.account');
            }

            $pages = $this->userService->getUserPages($accessToken);

            foreach ($pages['data'] as $page) {
                $pageInfo = $this->userService->getPageInfo($page['id'], $accessToken);

                if (isset($pageInfo['instagram_business_account'])) {
                    $instagramBusinessAccountId = $pageInfo['instagram_business_account']['id'];
                    $profilePictureUrl = $this->userService->getPageProfilePictureUrl($page['id'], $accessToken);

                    $facebookPageData = [
                        'user_id' => $user->id,
                        'instagram_business_account_id' => $instagramBusinessAccountId,
                        'name' => $page['name'],
                        'profile_picture_url' => $profilePictureUrl,
                    ];

                    $this->userService->saveFacebookPages($facebookPageData);
                }
            }

            return redirect()->route('select.account')->with('success', 'Facebook ile başarıyla giriş yapıldı.');
        } catch (\Exception $e) {
            return redirect()->route('home')->with('error', 'Facebook login failed: ' . $e->getMessage());
        }
    }
}
