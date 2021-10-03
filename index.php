<?php
/**
 * Created by PhpStorm.
 * User: IvanLu
 * Date: 2017/10/5
 * Time: 15:31
 */

use BunnyPHP\BunnyPHP;

const APP_PATH = __DIR__ . '/';
const APP_DEBUG = true;
date_default_timezone_set('PRC');
require(APP_PATH . 'vendor/autoload.php');
(new BunnyPHP())->run();