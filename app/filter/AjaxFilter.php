<?php

use BunnyPHP\BunnyPHP;
use BunnyPHP\Filter;

/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2019/3/3
 * Time: 15:31
 */
class AjaxFilter extends Filter
{
    public function doFilter($param = []): int
    {
        BunnyPHP::app()->set('tp_ajax', true);
        return self::NEXT;
    }
}