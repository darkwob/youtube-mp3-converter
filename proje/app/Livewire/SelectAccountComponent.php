<?php

namespace App\Livewire;

use Livewire\Component;
use App\Services\UserService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;

class SelectAccountComponent extends Component
{
    public $businessAccounts = [];
    public $selectedAccount = null;
    protected $userService;
    public $userId = null; // Bu alan başlangıçta null olacak
    public $status = null;

    protected $queryString = [
        'status' => ['except' => null],
    ];

    public function mount()
    {
        $this->userService = app(UserService::class);

        // Auth::id() mount içinde çağrılarak userId atanır
        $this->userId = Auth::id();

        if (!$this->userId) {
            toast("Kullanıcı bulunamadı. Lütfen giriş yapın.", 'error');
            return redirect()->route('login');
        }

        $this->loadAccounts($this->userId);
    }

    public function hydrate()
    {
        if (!$this->userService) {
            $this->userService = app(UserService::class);
        }
    }

    public function loadAccounts($userId)
    {
        $cacheKey = "user_{$userId}_facebook_pages";
        $cachedData = Cache::get($cacheKey);

        if ($cachedData) {
            $this->businessAccounts = $cachedData;
        } else {
            try {
                $accessToken = $this->userService->getUserAccessToken($userId);
                $facebookPages = $this->userService->getUserFacebookPages($userId);

                foreach ($facebookPages as $page) {
                    $instagramInfo = $this->userService->getFacebookPageForInstagramInfo($page->instagram_business_account_id, $accessToken);

                    $this->businessAccounts[] = [
                        'page_name' => $page->name,
                        'profile_picture_url' => $page->profile_picture_url,
                        'instagram' => [
                            'instagram_business_account_id' => $page->instagram_business_account_id,
                            'instagram_id' => $instagramInfo['ig_id'] ?? null,
                            'username' => $instagramInfo['username'] ?? null,
                        ],
                    ];
                }

                Cache::put($cacheKey, $this->businessAccounts, now()->addMinutes(10));
            } catch (\Exception $e) {
                toast('Hesaplar yüklenirken bir hata oluştu.', 'error');
            }
        }

        if ($this->status === "no-selected-account") {
            toast('Please select an Instagram account.', 'warning');
        }
    }

    public function fetchInstagramData()
    {
        if (!$this->selectedAccount) {
            return redirect()->route('select.account', ['status' => 'no-selected-account']);
        }

        try {
            $accessToken = $this->userService->getUserAccessToken($this->userId);
            $instagramInfo = $this->userService->getInstagramUserInfo($this->selectedAccount, $accessToken);
            $this->userService->saveInstagramAccount([
                'user_id' => $this->userId,
                'instagram_business_account_id' => $instagramInfo['id'],
                'instagram_id' => $instagramInfo['ig_id'],
                'username' => $instagramInfo['username'],
                'name' => $instagramInfo['name'],
                'profile_picture_url' => $instagramInfo['profile_picture_url'],
                'website' => $instagramInfo['website'] ?? null,
                'followers_count' => $instagramInfo['followers_count'] ?? 0,
                'follows_count' => $instagramInfo['follows_count'] ?? 0,
                'media_count' => $instagramInfo['media_count'] ?? 0,
                'biography' => $instagramInfo['biography'] ?? null,
            ]);

            toast('New Instagram account connected!', 'success');
            return redirect()->route('accounts');
        } catch (\Exception $e) {
            toast('Failed to retrieve Instagram information.', 'error');
            return redirect()->route('select.account');
        }
    }

    public function render()
    {
        return view('livewire.select-account-component');
    }
}
