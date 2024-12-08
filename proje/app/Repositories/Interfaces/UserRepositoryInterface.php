<?php

namespace App\Repositories\Interfaces;

interface UserRepositoryInterface
{
    /**
     * Yeni bir kullanıcı oluşturur.
     *
     * @param array $data
     * @return mixed
     */
    public function createUser(array $data);

    /**
     * Facebook ID'ye göre kullanıcıyı alır.
     *
     * @param string $facebookId
     * @return mixed
     */
    public function getUserByFacebookId($facebookId);

    /**
     * Kullanıcı token'ını günceller.
     *
     * @param int $userId
     * @param string $token
     * @return mixed
     */
    public function updateToken($userId, $token);

    /**
     * ID'ye göre kullanıcıyı alır.
     *
     * @param int $userId
     * @return mixed
     */
    public function getUserById($userId);

    /**
     * Kullanıcının erişim token'ını alır.
     *
     * @param int $userId
     * @return string|null
     */
    public function getUserAccessToken($userId);
}
