<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Filter;

/**
 * @author IvanLu
 * @time 2018/11/12 1:22
 */
class AuthFilter extends Filter
{
    public function doFilter($param = []): int
    {
        if (BUNNY_APP_MODE == BunnyPHP::MODE_NORMAL) {
            if ($_ENV['BUNNY_COOKIE_TOKEN'] ?? false) {
                $token = $_COOKIE['bunny_user_token'] ?? '';
            } else {
                $token = BunnyPHP::getRequest()->getSession('token');
            }
            if (!$token) $token = BunnyPHP::getRequest()->getHeader('token');
            if ($token) {
                $user = (new UserModel)->check($token);
                if ($user != null) {
                    BunnyPHP::app()->set('tp_user', $user);
                    $this->assign('tp_user', $user);
                    return self::NEXT;
                } else {
                    $this->redirect('user', 'login', ['referer' => $_SERVER['REQUEST_URI']]);
                }
            } else {
                $this->redirect('user', 'login', ['referer' => $_SERVER['REQUEST_URI']]);
            }
        } elseif (BUNNY_APP_MODE == BunnyPHP::MODE_API) {
            if (isset($_POST['client_id']) && isset($_POST['token'])) {
                $appKey = $_POST['client_id'];
                $appToken = $_POST['token'];
                if ($apiInfo = (new ApiModel())->check($appKey)) {
                    if ($apiInfo['type'] == ConstUtil::APP_SYSTEM || $param[0] == '' || in_array($param[0], $apiInfo['scope'])) {
                        $userId = (new OauthTokenModel())->check($appKey, $appToken);
                        if ($userId != 0) {
                            $user = (new UserModel)->getUserByUid($userId);
                            BunnyPHP::app()->set('tp_user', $user);
                            BunnyPHP::app()->set('tp_api', $apiInfo);
                            return self::NEXT;
                        } else {
                            $this->error(['ret' => 2003, 'status' => 'invalid token']);
                        }
                    } else {
                        $this->error(['ret' => 2002, 'status' => 'permission denied']);
                    }
                } else {
                    $this->error(['ret' => 2001, 'status' => 'invalid client id']);
                }
            } else {
                $this->error(['ret' => -7, 'status' => 'parameter cannot be empty']);
            }
        } elseif (BUNNY_APP_MODE == BunnyPHP::MODE_AJAX) {
            if (BunnyPHP::app()->get('tp_ajax') === true) {
                $token = BunnyPHP::getRequest()->getSession('token');
                if (!$token) $token = BunnyPHP::getRequest()->getHeader('token');
                if ($token) {
                    $user = (new UserModel)->check($token);
                    if ($user != null) {
                        BunnyPHP::app()->set('tp_user', $user);
                        return self::NEXT;
                    } else {
                        $this->redirect('user', 'login', ['referer' => $_SERVER['REQUEST_URI']]);
                    }
                } else {
                    $this->redirect('user', 'login', ['referer' => $_SERVER['REQUEST_URI']]);
                }
            } else {
                $this->error(['ret' => 2002, 'status' => 'permission denied']);
            }
        } else {
            $this->error(['ret' => 2002, 'status' => 'permission denied']);
        }
        return self::STOP;
    }
}