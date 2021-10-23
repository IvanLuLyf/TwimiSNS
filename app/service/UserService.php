<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Service;

/**
 * @author IvanLu
 * @time 2018/7/30 0:50
 */
class UserService extends Service
{
    public function getLoginUser()
    {
        if ($_ENV['BUNNY_COOKIE_TOKEN']) {
            $token = $_COOKIE['bunny_user_token'];
        } else {
            $token = BunnyPHP::getRequest()->getSession('token');
        }
        if ($token) {
            return (new UserModel)->check($token);
        }
        return null;
    }
}