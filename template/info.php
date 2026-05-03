<?php
/**
 * @var string|null $bunny_info
 * @var string|null $tp_info_msg
 */

use BunnyPHP\Config;

require APP_PATH . 'template/common/flash_bootstrap.php';

$rawBody = (string)($tp_info_msg ?? $bunny_info ?? '');
if (trim($rawBody) === '') {
    if ($shellHtmlLang === 'en') {
        $rawBody = 'Done.';
    } elseif ($shellHtmlLang === 'ja') {
        $rawBody = '完了しました。';
    } else {
        $rawBody = '已完成。';
    }
}
$raw = htmlspecialchars($rawBody, ENT_QUOTES, 'UTF-8');

if ($shellHtmlLang === 'en') {
    $shellNavTitle = 'Notice';
    $homeLabel = 'Home';
    $infoLead = 'Notice';
} elseif ($shellHtmlLang === 'ja') {
    $shellNavTitle = 'お知らせ';
    $homeLabel = 'ホームへ';
    $infoLead = 'お知らせ';
} else {
    $shellNavTitle = '提示';
    $homeLabel = '返回首页';
    $infoLead = '提示';
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($shellHtmlLang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="<?= htmlspecialchars($shellThemeColor, ENT_QUOTES, 'UTF-8') ?>">
    <title><?= $shellSiteName ?></title>
    <link rel="stylesheet" href="/static/app/styles/app.css?v=57">
    <style id="ts-theme-inline">
        :root {
            --ts-accent: <?= htmlspecialchars($shellThemeColor, ENT_QUOTES, 'UTF-8') ?>;
        }
    </style>
</head>
<body>
<div id="app" class="ts-shell ts-shell--flash">
    <?php include APP_PATH . 'template/common/system_shell_nav.php'; ?>
    <div class="ts-drawer-backdrop" aria-hidden="true"></div>
    <div class="ts-layout">
        <aside class="ts-layout-side ts-layout-side--left" id="ts-sidebar-left" aria-hidden="true"></aside>
        <main class="ts-layout-main">
            <div class="ts-main-stack ts-stack-gap ts-flash-stack">
                <div class="ts-card ts-flash-card ts-flash-card--info" role="status">
                    <div class="ts-flash-hero">
                        <div class="ts-flash-icon ts-flash-icon--info" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="16" x2="12" y2="12"/>
                                <line x1="12" y1="8" x2="12.01" y2="8"/>
                            </svg>
                        </div>
                        <div class="ts-flash-hero-body">
                            <h1 class="ts-flash-title ts-flash-title--neutral"><?= htmlspecialchars($infoLead, ENT_QUOTES, 'UTF-8') ?></h1>
                        </div>
                    </div>
                    <div class="ts-flash-pre-panel">
                        <pre class="ts-flash-pre"><?= $raw ?></pre>
                    </div>
                    <div class="ts-flash-actions">
                        <a class="ts-btn primary" href="/"><?= htmlspecialchars($homeLabel, ENT_QUOTES, 'UTF-8') ?></a>
                    </div>
                </div>
                <?php if (Config::check('config')): ?>
                    <?php $cp = trim((string)Config::load('config')->get('copyright', '')); ?>
                    <?php if ($cp !== ''): ?>
                        <p class="ts-meta ts-flash-foot"><?= htmlspecialchars($cp, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </main>
        <aside class="ts-layout-side ts-layout-side--right" aria-hidden="true"></aside>
    </div>
</div>
</body>
</html>
