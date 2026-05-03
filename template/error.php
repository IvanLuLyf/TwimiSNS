<?php
/**
 * @var string|null $tp_error_msg
 * @var string|null $bunny_error
 * @var mixed $ret
 * @var mixed $status
 * @var array|null $bunny_error_trace
 */

use BunnyPHP\Config;

require APP_PATH . 'template/common/flash_bootstrap.php';

$rawMsg = trim($tp_error_msg ?? $bunny_error ?? '');
if ($rawMsg === '') {
    $rawMsg = $shellHtmlLang === 'en' ? 'Something went wrong' : '操作失败';
}
$msg = htmlspecialchars($rawMsg, ENT_QUOTES, 'UTF-8');
$showTrace = (defined('APP_DEBUG') && APP_DEBUG === true) && !empty($bunny_error_trace) && is_array($bunny_error_trace);

$shellNavTitle = $shellHtmlLang === 'en' ? 'Error' : '错误';
$homeLabel = $shellHtmlLang === 'en' ? 'Home' : '返回首页';
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($shellHtmlLang, ENT_QUOTES, 'UTF-8') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="<?= htmlspecialchars($shellThemeColor, ENT_QUOTES, 'UTF-8') ?>">
    <title><?= $shellSiteName ?></title>
    <link rel="stylesheet" href="/static/app/styles/app.css?v=56">
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
                <div class="ts-card ts-flash-card ts-flash-card--error" role="alert">
                    <div class="ts-flash-hero">
                        <div class="ts-flash-icon ts-flash-icon--error" aria-hidden="true">
                            <svg xmlns="http://www.w3.org/2000/svg" width="26" height="26" viewBox="0 0 24 24"
                                 fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round"
                                 stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"/>
                                <line x1="12" y1="8" x2="12" y2="12"/>
                                <line x1="12" y1="16" x2="12.01" y2="16"/>
                            </svg>
                        </div>
                        <div class="ts-flash-hero-body">
                            <p class="ts-flash-kicker"><?= $shellHtmlLang === 'en' ? 'Something went wrong' : '无法完成操作' ?></p>
                            <h1 class="ts-flash-title"><?= $msg ?></h1>
                            <?php if (isset($ret) || isset($status)): ?>
                                <div class="ts-flash-meta-code">
                                    <?php if (isset($ret)): ?>
                                        <span>ret <?= htmlspecialchars((string)$ret, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                    <?php if (isset($status)): ?>
                                        <span><?= htmlspecialchars((string)$status, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php if ($showTrace): ?>
                        <h2 class="ts-flash-trace-title"><?= $shellHtmlLang === 'en' ? 'Stack trace' : '调用栈' ?></h2>
                        <div class="ts-flash-trace-wrap">
                            <table class="ts-flash-trace-table">
                                <thead>
                                <tr>
                                    <th>#</th>
                                    <th>File</th>
                                    <th>Line</th>
                                    <th>Call</th>
                                </tr>
                                </thead>
                                <tbody>
                                <?php foreach ($bunny_error_trace as $i => $t): ?>
                                    <tr>
                                        <td><?= (int)($i + 1) ?></td>
                                        <td><?= htmlspecialchars((string)($t['file'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)($t['line'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                        <td><?= htmlspecialchars((string)(($t['class'] ?? '') . ($t['type'] ?? '') . ($t['function'] ?? '') . '()'), ENT_QUOTES, 'UTF-8') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
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
