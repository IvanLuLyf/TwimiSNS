<?php
/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2017/10/23
 * Time: 13:46
 */

use BunnyPHP\BunnyPHP;

const APP_PATH = __DIR__ . '/';
const APP_DEBUG = true;
date_default_timezone_set('PRC');
require(APP_PATH . 'vendor/autoload.php');
(new BunnyPHP(BunnyPHP::MODE_API))->run();