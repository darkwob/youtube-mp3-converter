<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Instagram\User\BusinessDiscovery;
use Instagram\FacebookLogin\FacebookLogin;
use Instagram\AccessToken\AccessToken;
use Illuminate\Support\Facades\Http;
use Instagram\User\User;
use Instagram\Page\Page;

class TestController extends Controller
{
    private $appID = "555256357192947";
    private $appSecret = "e5a6df5ed8ac9689956dc14ee2a5cbbd";
    private $redirectUri = 'https://localhost/igtest'; // Yönlendirme URI'si

    // Ana index metodu: Kullanıcı giriş yapıp code alır
    public function index(Request $request)
    {
        $code = $request->query('code'); // 'code' parametresini alıyoruz

        if ($code) {
            // Kısa süreli token alma ve uzun süreli token'e çevirme
            $newToken = $this->getAccessTokenFromCode($code);
            $accessTokenValue = $newToken['access_token']; // Token'i alıyoruz

            // Token'i Session'a kaydet
            session(['access_token' => $accessTokenValue]);

            // View'e yönlendirme (veya başka bir işlev)
            return view('igtest', ['token' => $newToken]);
        } else {
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
            ];

            // Facebook Login URL'sini oluştur
            $facebookLogin = new FacebookLogin([
                'app_id' => $this->appID,
                'app_secret' => $this->appSecret,
            ]);

            // Facebook login linkini gösteriyoruz
            return view('index', ['url' => $facebookLogin->getLoginDialogUrl($this->redirectUri, $permissions)]);
        }
    }

    // Koddan kısa süreli ve uzun süreli Access Token alma işlemi
    private function getAccessTokenFromCode($code)
    {
        $accessToken = new AccessToken([
            'app_id' => $this->appID,
            'app_secret' => $this->appSecret,
        ]);

        // Koddan kısa süreli token alıyoruz
        $shortLivedToken = $accessToken->getAccessTokenFromCode($code, $this->redirectUri);

        // Kısa süreli token'ı kontrol edip gerekirse uzun süreli yapıyoruz
        if (!$accessToken->isLongLived()) {
            $longLivedToken = $accessToken->getLongLivedAccessToken($shortLivedToken['access_token']);
            return $longLivedToken;
        }

        return $shortLivedToken;
    }

    // Access Token'ın debug edilmesi ve geçerlilik kontrolü
    public function debug()
    {
        // Session'dan token al
        $accessTokenValue = session('access_token');

        if (!$accessTokenValue) {
            return response()->json(['error' => 'Access token bulunamadı.'], 400);
        }

        try {
            // Token'i debug etmek için Facebook Graph API'ye istek yapıyoruz
            $debugResponse = Http::get('https://graph.facebook.com/debug_token', [
                'input_token' => $accessTokenValue,
                'access_token' => $accessTokenValue,
            ]);

            $debugInfo = $debugResponse->json();

            // Hata kontrolü
            if ($debugResponse->failed() || isset($debugInfo['error'])) {
                $errorMessage = $debugInfo['error']['message'] ?? 'Bilinmeyen bir hata oluştu.';
                return response()->json(['error' => $errorMessage], $debugResponse->status());
            }

            // Kullanıcının izinlerini almak için AccessToken sınıfını kullanıyoruz
            $accessToken = new AccessToken([
                'user_id' => $debugInfo['data']['user_id'],
                'access_token' => $accessTokenValue,
            ]);

            $permissions = $accessToken->getPermissions(); // İzinleri alıyoruz

            // İzinleri ve debug bilgilerini birleştiriyoruz
            $result = [
                'debug_info' => $debugInfo,
                'permissions' => $permissions,
            ];

            // JSON formatında geri dönüyoruz
            return response()->json($result);

        } catch (\Exception $e) {
            // Hata durumunda yakalıyoruz
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    // Kullanıcıya ait sayfaları getiren fonksiyon
    public function getUserPages()
    {
        // Session'dan token alıyoruz
        $accessTokenValue = session('access_token');

        if (!$accessTokenValue) {
            return response()->json(['error' => 'Access token bulunamadı.'], 400);
        }

        try {
            // Kullanıcı nesnesini başlatıyoruz
            $user = new User([
                'access_token' => $accessTokenValue,
            ]);

            // Kullanıcıya ait sayfaları alıyoruz
            $pages = $user->getUserPages();

            // JSON formatında sayfa bilgilerini döndürüyoruz
            return view("getPages", ['pages' => $pages]);

        } catch (\Exception $e) {
            // Hata durumunda exception yakalıyoruz
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
    public function getPageInfo($pageId)
    {
        // Session'dan access token alıyoruz
        $accessTokenValue = session('access_token');

        if (!$accessTokenValue) {
            return response()->json(['error' => 'Access token bulunamadı.'], 400);
        }

        try {
            // Page sınıfını kullanarak sayfa nesnesini başlatıyoruz
            $page = new Page([
                'page_id' => $pageId,
                'access_token' => $accessTokenValue,
            ]);

            // Sayfa bilgilerini alıyoruz
            $pageInfo = $page->getSelf();

            // JSON formatında yanıt döndürüyoruz
            return view("getPageInfo", ['pageInfo' => $pageInfo]);

        } catch (\Exception $e) {
            // Hata durumunda yakalıyoruz
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getUserInfo($instagramId)
{
   try{
    $accessTokenValue = session('access_token');

    $config = array( // instantiation config params
        'user_id' => $instagramId,
        'access_token' => $accessTokenValue,
    );

    // instantiate user for use
    $user = new User( $config );

    $params = [
        'fields' => 'id,username,name,profile_picture_url,website,followers_count,follows_count,media_count,media{username,caption,like_count,comments_count,timestamp,media_product_type,media_type,permalink,media_url,thumbnail_url}'
    ];
    $userInfo = $user->getSelf($params);
        return view('getUserProfile', ['userInfo' => $userInfo]);

    } catch (\Exception $e) {
        return response()->json(['error' => $e->getMessage()], 500);
    }
}









}
