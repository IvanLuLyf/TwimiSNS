<?php

use BunnyPHP\Request;
use BunnyPHP\Service;

/**
 * @author IvanLu
 * @time 2018/7/30 0:50
 */
class UserService extends Service
{
    public function getLoginUser()
    {
        if ($_ENV['BUNNY_COOKIE_TOKEN'] ?? false) {
            $token = Request::cookie('bunny_user_token');
        } else {
            $token = Request::session('token');
        }
        if ($token) {
            return (new UserModel)->check($token);
        }
        return null;
    }

    public function setLoginUser($token)
    {
        if ($_ENV['BUNNY_COOKIE_TOKEN'] ?? false) {
            Request::cookie('bunny_user_token', $token, time() + 86400);
        } else {
            Request::session('token', $token);
        }
    }
}