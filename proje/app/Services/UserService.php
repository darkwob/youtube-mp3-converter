<?php

namespace App\Services;

use Exception;
use Instagram\Page\Page;
use Instagram\User\User;
use Illuminate\Support\Str;
use App\Models\FacebookPage;
use App\Models\InstagramAccount;
use Illuminate\Support\Facades\Http;
use Instagram\AccessToken\AccessToken;
use Illuminate\Support\Facades\Session;
use Instagram\FacebookLogin\FacebookLogin;
use App\Repositories\Interfaces\UserRepositoryInterface;

class UserService
{
    private $appID;
    private $appSecret;
    private $redirectUri;
    private $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->appID = config('services.facebook.app_id');
        $this->appSecret = config('services.facebook.app_secret');
        $this->redirectUri = config('services.facebook.redirect_uri');
        $this->userRepository = $userRepository;
    }

    // Giriş URL'si oluşturur
    public function generateLoginUrl()
    {
        try {
            $facebookLogin = new FacebookLogin([
                'app_id' => $this->appID,
                'app_secret' => $this->appSecret,
            ]);

            $permissions = [
                'instagram_basic',
                'instagram_content_publish',
                'instagram_manage_insights',
                'instagram_manage_comments',
                'pages_show_list',
                'ads_management',
                'instagram_manage_insights',
                'business_management',
                'pages_manage_metadata',
                'pages_read_engagement',
                'email'
            ];

            return $facebookLogin->getLoginDialogUrl($this->redirectUri, $permissions);
        } catch (Exception $e) {
            throw new Exception("Giriş URL'si oluşturulurken hata oluştu: " . $e->getMessage());
        }
    }

    // Koddan access token alır
    public function exchangeCodeForToken($code)
    {
        try {
            $accessToken = new AccessToken([
                'app_id' => $this->appID,
                'app_secret' => $this->appSecret,
            ]);

            $shortLivedToken = $accessToken->getAccessTokenFromCode($code, $this->redirectUri);

            return $accessToken->isLongLived() ? $shortLivedToken : $accessToken->getLongLivedAccessToken($shortLivedToken['access_token']);
        } catch (Exception $e) {
            throw new Exception("Koddan token alınırken hata oluştu: " . $e->getMessage());
        }
    }

    // Kullanıcı login işlemi yapar
    public function handleUserLogin($code)
    {
        try {
            $tokenData = $this->exchangeCodeForToken($code);

            if (empty($tokenData['access_token'])) {
                throw new Exception('Kalıcı Access token alınamadı');
            }

            $accessToken = $tokenData['access_token'];
            Session::put('access_token', $accessToken);

            $facebookUserData = $this->getFacebookUserData($accessToken);

            $user = $this->userRepository->getUserByFacebookId($facebookUserData['id']);
            if (!$user) {
                $user = $this->userRepository->createUser([
                    'facebook_id' => $facebookUserData['id'],
                    'email' => $facebookUserData['email'] ?? null,
                    'name' => $facebookUserData['name'],
                    'password' => bcrypt(Str::random(16)),
                    'access_token' => $accessToken,
                ]);
            } else {
                $this->userRepository->updateToken($user->id, $accessToken);
            }

            return $user;
        } catch (Exception $e) {
            throw new Exception('Kullanıcı girişi sırasında hata oluştu: ' . $e->getMessage());
        }
    }

    // Facebook kullanıcı bilgilerini çeker
    private function getFacebookUserData($accessToken)
    {
        try {
            $response = Http::get("https://graph.facebook.com/me", [
                'fields' => 'id,name,email',
                'access_token' => $accessToken,
            ]);

            $userData = $response->json();

            if (isset($userData['error'])) {
                throw new Exception($userData['error']['message']);
            }

            return [
                'id' => $userData['id'],
                'name' => $userData['name'],
                'email' => $userData['email'] ?? null
            ];
        } catch (Exception $e) {
            throw new Exception('Facebook kullanıcı verisi alınamadı: ' . $e->getMessage());
        }
    }

    // Kullanıcının Facebook sayfalarını alır
    public function getUserPages($accessToken)
    {
        try {
            $user = new User(['access_token' => $accessToken]);
            return $user->getUserPages();
        } catch (Exception $e) {
            throw new Exception("Facebook sayfaları alınırken hata oluştu: " . $e->getMessage());
        }
    }

    // Facebook sayfa bilgilerini getirir
    public function getPageInfo($pageId, $accessToken)
    {
        try {
            $page = new Page([
                'page_id' => $pageId,
                'access_token' => $accessToken,
            ]);

            return $page->getSelf();
        } catch (Exception $e) {
            throw new Exception("Sayfa bilgileri alınırken hata oluştu: " . $e->getMessage());
        }
    }

    // Instagram hesabı bilgilerini alır
    public function getInstagramUserInfo($instagramId, $accessToken)
    {
        try {
            $user = new User([
                'user_id' => $instagramId,
                'access_token' => $accessToken,
            ]);
            $params = [
                'fields' => 'id,ig_id,username,name,profile_picture_url,website,followers_count,follows_count,media_count'
            ];
            return $user->getSelf($params);
        } catch (Exception $e) {
            throw new Exception("Instagram kullanıcı bilgileri alınırken hata oluştu: " . $e->getMessage());
        }
    }

    public function updateUserAccountInfo($userId, $pageId, $instagramBusinessAccountId)
    {
        $user = $this->userRepository->getUserById($userId);
        if ($user) {
            $user->instagram_business_account_id = $instagramBusinessAccountId;
            $user->facebook_page_id = $pageId;
            $user->save();
        }
    }

    public function getUserAccessToken($userId)
    {
        $user = $this->userRepository->getUserById($userId);

        if ($user) {
            return $user->access_token;
        }

        throw new Exception("Kullanıcı veya access token mevcut değil.");
    }

    public function saveFacebookPages($instagramAccountData)
    {
        $facebookPage = FacebookPage::where('instagram_business_account_id', $instagramAccountData['instagram_business_account_id'])->first();
        if (!$facebookPage) {
            FacebookPage::create($instagramAccountData);
        }
    }

    public function findInstagramAccount($instagramBusinessAccountId)
    {
        return FacebookPage::where('instagram_business_account_id', $instagramBusinessAccountId)->first();
    }

    public function findFacebookPages($userId)
    {
        return FacebookPage::where('user_id', $userId)->exists();
    }

    public function getPageProfilePictureUrl($pageId, $accessToken)
    {
        $response = Http::get("https://graph.facebook.com/{$pageId}/picture", [
            'access_token' => $accessToken,
            'redirect' => false
        ]);

        $data = $response->json();
        return $data['data']['url'] ?? null;
    }
    public function getUserFacebookPages($userId)
    {
        return FacebookPage::where('user_id', $userId)->get();
    }

    public function getInstagramAccountByBusinessAccountId($instagramBusinessAccountId)
    {
        return InstagramAccount::where('instagram_business_account_id', $instagramBusinessAccountId)->first();
    }
    public function getAndClearSessionAccessToken($userId)
    {
        // Eğer session'da access_token varsa onu siliyoruz
        if (Session::has('access_token')) {
            Session::forget('access_token');
        }

        // Veritabanındaki access_token'i alıyoruz
        return $this->getUserAccessToken($userId);
    }

    public function getFacebookPageForInstagramInfo($instagramId, $accessToken)
    {
        try {
            $user = new User([
                'user_id' => $instagramId,
                'access_token' => $accessToken,
            ]);
            $params = [
                'fields' => 'ig_id,username'
            ];
            return $user->getSelf($params);
        } catch (Exception $e) {
            throw new Exception("Instagram kullanıcı bilgileri alınırken hata oluştu: " . $e->getMessage());
        }
    }

    public function saveInstagramAccount(array $datas)
    {
        try {
            $instagramAccount = InstagramAccount::updateOrCreate(
                ['instagram_business_account_id' => $datas['instagram_business_account_id']],
                $datas
            );
            if ($instagramAccount) {
                logger('Kayıt başarılı: ' . json_encode($datas));
                return $instagramAccount;
            } else {
                logger('Kayıt başarısız: ' . json_encode($datas));
            }
        } catch (\Exception $e) {
            logger('Hata oluştu: ' . $e->getMessage());
            throw $e; // Hatanın dışarıda yakalanması için tekrar fırlatılır
        }
    }





}
