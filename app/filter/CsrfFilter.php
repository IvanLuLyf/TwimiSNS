<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Filter;

/**
 * @author IvanLu
 * @time 2019/3/1 15:45
 */
class CsrfFilter extends Filter
{
    public function doFilter($param = []): int
    {
        if (BUNNY_APP_MODE == BunnyPHP::MODE_NORMAL || BUNNY_APP_MODE == BunnyPHP::MODE_AJAX) {
            $csrf_token = BunnyPHP::getRequest()->getSession('csrf_token');
            if (in_array('check', $param)) {
                if ($csrf_token && !empty($_POST['csrf_token']) && $_POST['csrf_token'] == $csrf_token) {
                    unset($_SESSION['csrf_token']);
                } else {
                    $this->error(['ret' => 1, 'status' => 'invalid csrf token', 'tp_error_msg' => '非法的请求操作']);
                    return self::STOP;
                }
            }
            $token = md5(time() . rand(1, 1000));
            BunnyPHP::getRequest()->setSession('csrf_token', $token);
            $this->assign('csrf_token', $token);
            return self::NEXT;
        } else {
            return self::NEXT;
        }
    }
}