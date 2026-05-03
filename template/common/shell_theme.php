<?php

use BunnyPHP\Config;

$shellThemeColor = '#1996ff';
if (Config::check('config')) {
    $tc = trim((string)Config::load('config')->get('theme_color', '#1996ff'));
    if (preg_match('/^#[0-9A-Fa-f]{6}$/', $tc)) {
        $shellThemeColor = $tc;
    }
}
$shellCfg = Config::check('config') ? Config::load('config') : null;
$shellUiLocale = $shellCfg ? trim((string)$shellCfg->get('locale', 'zh-CN')) : 'zh-CN';
$shellHtmlLang = (stripos($shellUiLocale, 'en') === 0) ? 'en' : 'zh-CN';
