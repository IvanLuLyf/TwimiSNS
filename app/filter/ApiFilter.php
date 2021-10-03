<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Filter;

/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2018/1/1
 * Time: 23:26
 */
class ApiFilter extends Filter
{
    public function doFilter($param = []): int
    {
        if (BUNNY_APP_MODE == BunnyPHP::MODE_API) {
            if (isset($_POST['client_id']) && isset($_POST['client_secret'])) {
                $appKey = $_POST['client_id'];
                $appSecret = $_POST['client_secret'];
                if (($apiInfo = (new ApiModel())->validate($appKey, $appSecret)) != null) {
                    if ($apiInfo['type'] == 1 || ($param[0] != '' and $apiInfo[$param[0]] == true)) {
                        BunnyPHP::app()->set('tp_api', $apiInfo);
                        return self::NEXT;
                    } else {
                        $this->error(['ret' => 2002, 'status' => 'permission denied']);
                    }
                } else {
                    $this->error(['ret' => 2001, 'status' => 'invalid client id']);
                }
            } else {
                $this->error(['ret' => -7, 'status' => 'parameter cannot be empty']);
            }
            return self::STOP;
        } else if (BUNNY_APP_MODE == BunnyPHP::MODE_AJAX) {
            if (BunnyPHP::app()->get('tp_ajax') !== true) {
                return self::STOP;
            }
        }
        return self::NEXT;
    }
}