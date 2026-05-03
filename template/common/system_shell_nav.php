<?php
/**
 * @var string $shellSiteName
 * @var string $shellNavTitle
 * @var mixed $shellTpUser
 * @var bool $shellAllowReg
 * @var string $shellHtmlLang
 */

$navEsc = htmlspecialchars($shellNavTitle, ENT_QUOTES, 'UTF-8');
$isEn = ($shellHtmlLang === 'en');
$lblHome = $isEn ? 'Home' : '首页';
$lblLogin = $isEn ? 'Log in' : '登录';
$lblReg = $isEn ? 'Sign up' : '注册';
$lblProfile = $isEn ? 'Profile' : '我的';
$loginHref = '/user/login';
$regHref = '/user/register';
?>
<header class="ts-topnav">
    <div class="ts-topnav-leading">
        <a href="/" class="ts-menu-toggle ts-btn ts-btn--ghost ts-flash-lead-link"
           aria-label="<?= htmlspecialchars($lblHome, ENT_QUOTES, 'UTF-8') ?>">
            <span class="ts-menu-toggle-icon" aria-hidden="true">
                <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none"
                     stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path
                            d="m15 18-6-6 6-6"/></svg>
            </span>
        </a>
        <a class="ts-brand ts-brand--desktop" href="/"><?= $shellSiteName ?></a>
    </div>
    <div class="ts-topnav-center">
        <span class="ts-topnav-page-title"><?= $navEsc ?></span>
    </div>
    <div class="ts-topnav-trailing">
        <div class="ts-header-actions">
            <?php if (is_array($shellTpUser) && !empty($shellTpUser['username'])): ?>
                <?php
                $un = $shellTpUser['username'];
                $panelHref = '/user/panel/' . rawurlencode($un);
                $avSrc = '/user/avatar?username=' . rawurlencode($un);
                ?>
                <a class="ts-header-icon-btn" href="<?= htmlspecialchars($panelHref, ENT_QUOTES, 'UTF-8') ?>"
                   aria-label="<?= htmlspecialchars($lblProfile, ENT_QUOTES, 'UTF-8') ?>">
                    <img class="ts-drawer-toggle-avatar" src="<?= htmlspecialchars($avSrc, ENT_QUOTES, 'UTF-8') ?>"
                         alt="">
                </a>
            <?php else: ?>
                <nav class="ts-nav-links ts-nav-links--compact ts-flash-nav-auth">
                    <a href="<?= htmlspecialchars($loginHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($lblLogin, ENT_QUOTES, 'UTF-8') ?></a>
                    <?php if ($shellAllowReg): ?>
                        <a href="<?= htmlspecialchars($regHref, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($lblReg, ENT_QUOTES, 'UTF-8') ?></a>
                    <?php endif; ?>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</header>
