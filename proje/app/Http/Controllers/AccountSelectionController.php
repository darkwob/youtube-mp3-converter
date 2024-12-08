<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Services\UserService;
use App\Models\InstagramAccount;

class AccountSelectionController extends Controller
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    // Kullanıcının Facebook sayfalarını ve Instagram hesaplarını göster
    public function showAccounts()
    {
        $userId = Auth::id();

        if (!$userId) {
            return redirect()->route('login')->with('error', 'Kullanıcı bulunamadı. Lütfen giriş yapın.');
        }

        try {
            // Kullanıcının token'ını veritabanından alıyoruz (session'daki token'i siliyor)
            $accessToken = $this->userService->getAndClearSessionAccessToken($userId);

            // Kullanıcının Facebook sayfalarını veritabanından alıyoruz
            $facebookPages = $this->userService->getUserFacebookPages($userId);
            $businessAccounts = [];

            // Her bir Facebook sayfası için ilgili Instagram hesap bilgilerini çek
            foreach ($facebookPages as $page) {
                // `instagram_business_account_id` kullanarak Instagram `username` bilgisi alınıyor
                $instagramInfo = $this->userService->getFacebookPageForInstagramInfo($page->instagram_business_account_id, $accessToken);

                $businessAccounts[] = [
                    'page_name' => $page->name,
                    'profile_picture_url' => $page->profile_picture_url,
                    'instagram' => [
                        'instagram_bussines_account_id' =>  $page->instagram_business_account_id,
                        'username' => $instagramInfo['username'] ?? null,
                    ],
                ];
            }

            // Verileri view’e gönderiyoruz
            return view('app.account_selection', compact('businessAccounts'));

        } catch (\Exception $e) {
            return redirect()->route('login')->with('error', $e->getMessage());
        }
    }




    // Seçilen hesabın bilgilerini kaydet
    public function selectAccount(Request $request)
    {
        $userId = Auth::id();
        $pageId = $request->input('page_id');

        if (!$userId) {
            return back()->with('error', 'Kullanıcı bulunamadı. Lütfen giriş yapın.');
        }

        try {
            // Kullanıcının token'ını alıyoruz
            $accessToken = $this->userService->getUserAccessToken($userId);

            // Seçilen sayfanın bilgilerini alıyoruz
            $pageInfo = $this->userService->getPageInfo($pageId, $accessToken);

            if (isset($pageInfo['instagram_business_account']['id'])) {
                $instagramBusinessAccountId = $pageInfo['instagram_business_account']['id'];

                // Kullanıcıyı güncellemek için UserService'teki metodu kullanın
                $this->userService->updateUserAccountInfo($userId, $pageId, $instagramBusinessAccountId);

                return redirect()->route('dashboard')->with('success', 'Instagram hesabı başarıyla seçildi.');
            } else {
                return back()->with('error', 'Bu sayfaya bağlı bir Instagram hesabı bulunamadı.');
            }
        } catch (\Exception $e) {
            return back()->with('error', $e->getMessage());
        }
    }
}
