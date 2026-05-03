<?php

use BunnyPHP\Config;
use BunnyPHP\Request;

require APP_PATH . 'template/common/shell_theme.php';
$themeColor = $shellThemeColor;
$htmlLang = $shellHtmlLang;

$csrf_token = md5((string)microtime(true) . random_int(1, 100000));
Request::session('csrf_token', $csrf_token);

if (!isset($tp_user)) {
    $tp_user = (new UserService())->getLoginUser();
}

$oauthKeys = [];
if (Config::check('oauth')) {
    $oauthKeys = Config::load('oauth')->get('enabled', []);
}

$cfg = Config::load('config');
$uiLocale = $shellUiLocale;
$bootstrap = [
    'siteName' => TP_SITE_NAME,
    'locale' => $uiLocale,
    'allowReg' => (bool)$cfg->get('allow_reg'),
    'themeColor' => $themeColor,
    'csrfToken' => $csrf_token,
    'basePath' => '',
    'bootUrl' => '/index/boot',
    'user' => null,
    'oauth' => $oauthKeys,
    'copyright' => trim((string)$cfg->get('copyright', '')),
    'icpBeian' => trim((string)$cfg->get('icp_beian', '')),
    'icpBeianUrl' => trim((string)$cfg->get('icp_beian_url', 'https://beian.miit.gov.cn/')),
    'poweredByUrl' => trim((string)$cfg->get('powered_by_url', '')),
    'poweredByName' => trim((string)$cfg->get('powered_by_name', 'TwimiSNS')),
    'poweredByPrefix' => trim((string)$cfg->get('powered_by_prefix', '')),
    'poweredByPlain' => trim((string)$cfg->get('powered_by_plain', '')),
    'legalFooterOneLine' => (bool)$cfg->get('legal_footer_one_line', false),
];
$bootstrap['wallet'] = null;
if ($tp_user) {
    $bootstrap['user'] = [
        'uid' => (int)$tp_user['uid'],
        'username' => $tp_user['username'],
        'nickname' => $tp_user['nickname'],
        'avatar' => $tp_user['avatar'] ?? '',
    ];
    $wb = (new CreditModel())->balance($tp_user['uid']);
    $bootstrap['wallet'] = [
        'active' => $wb != -1,
        'credit' => $wb != -1 ? (float)$wb : null,
    ];
}
if (isset($oauthAuthorize) && is_array($oauthAuthorize)) {
    $bootstrap['oauthAuthorize'] = $oauthAuthorize;
}
if (isset($oauthBind) && is_array($oauthBind)) {
    $bootstrap['oauthBind'] = $oauthBind;
}
$jsonFlags = JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT;
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($htmlLang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="<?= htmlspecialchars($themeColor, ENT_QUOTES, 'UTF-8') ?>">
    <title><?= htmlspecialchars(constant('TP_SITE_NAME'), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="stylesheet" href="/static/app/styles/app.css?v=55">
    <style id="ts-theme-inline">
        :root {
            --tw-accent: <?= htmlspecialchars($themeColor, ENT_QUOTES, 'UTF-8') ?>;
        }
    </style>
    <script type="application/json" id="ts-bootstrap"><?= json_encode($bootstrap, $jsonFlags) ?></script>
</head>
<body>
<div id="app" class="ts-shell">
    <div class="ts-loading" role="status" aria-live="polite"></div>
</div>
<script type="module" src="/static/app/main.js?v=60"></script>
</body>
</html>
