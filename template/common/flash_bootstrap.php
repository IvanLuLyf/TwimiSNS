<?php

use BunnyPHP\Config;

require APP_PATH . 'template/common/shell_theme.php';

if (!isset($tp_user)) {
    $tp_user = (new UserService())->getLoginUser();
}

$shellAllowReg = Config::check('config') && (bool)Config::load('config')->get('allow_reg');
$shellSiteName = htmlspecialchars(constant('TP_SITE_NAME'), ENT_QUOTES, 'UTF-8');
$shellTpUser = $tp_user;
