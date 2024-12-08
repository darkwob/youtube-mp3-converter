<?php

namespace App\Repositories\Eloquent;

use App\Models\User;
use App\Repositories\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    protected $user;

    public function __construct(User $user)
    {
        $this->user = $user;
    }

    /**
     * Yeni bir kullanıcı oluşturur.
     *
     * @param array $data
     * @return User
     */
    public function createUser(array $data)
    {
        return $this->user->create($data);
    }

    /**
     * Facebook ID'ye göre kullanıcıyı getirir.
     *
     * @param string $facebookId
     * @return User|null
     */
    public function getUserByFacebookId($facebookId)
    {
        return $this->user->where('facebook_id', $facebookId)->first();
    }

    /**
     * Kullanıcı ID'ye göre token'ı günceller.
     *
     * @param int $userId
     * @param string $token
     * @return User|null
     */
    public function updateToken($userId, $token)
    {
        $user = $this->user->find($userId);

        if ($user) {
            $user->access_token = $token;
            $user->save();
        }

        return $user;
    }

    /**
     * Kullanıcı ID'ye göre kullanıcıyı getirir.
     *
     * @param int $userId
     * @return User|null
     */
    public function getUserById($userId)
    {
        return $this->user->find($userId);
    }

    /**
     * Kullanıcı ID'ye göre access token'ı getirir.
     *
     * @param int $userId
     * @return string|null
     */
    public function getUserAccessToken($userId)
    {
        $user = $this->user->find($userId);
        return $user ? $user->access_token : null;
    }
}
