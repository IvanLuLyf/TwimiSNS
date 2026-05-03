import {ajaxGet, ajaxPost, formPost, jsonGet, jsonPost} from './api.js';
import {createIcon} from './icons.js';
import {getLocale, initI18n, t, toggleLocale} from './i18n.js';
import {prepareFeedMarkdownPreview, renderMarkdown, escapeHtml} from './md.js';

function readBootstrap() {
    const el = document.getElementById('ts-bootstrap');
    if (!el) return {};
    try {
        return JSON.parse(el.textContent);
    } catch {
        return {};
    }
}

let bootstrap = readBootstrap();
initI18n(bootstrap);
let csrfToken = bootstrap.csrfToken || '';

function closeDrawer() {
    document.getElementById('app')?.classList.remove('ts-drawer-open');
    document.body.style.overflow = '';
    document.querySelectorAll('.ts-menu-toggle').forEach((el) => el.setAttribute('aria-expanded', 'false'));
}

/** @type {null | (() => void)} */
let composeModalTeardown = null;

function closeComposeModal() {
    if (composeModalTeardown) {
        composeModalTeardown();
        composeModalTeardown = null;
    }
}

/** @type {HTMLElement | null} */
let uploadLoadingMaskEl = null;

function showUploadLoadingMask() {
    if (uploadLoadingMaskEl) return;
    uploadLoadingMaskEl = document.createElement('div');
    uploadLoadingMaskEl.id = 'ts-upload-loading-mask';
    uploadLoadingMaskEl.className = 'ts-loading-mask';
    uploadLoadingMaskEl.setAttribute('role', 'status');
    uploadLoadingMaskEl.setAttribute('aria-live', 'polite');
    uploadLoadingMaskEl.innerHTML = `<div class="ts-loading-mask-inner"><div class="ts-loading-mask-spinner" aria-hidden="true"></div><p class="ts-loading-mask-text">${escapeHtml(t('settingsUploading'))}</p></div>`;
    document.body.appendChild(uploadLoadingMaskEl);
}

function hideUploadLoadingMask() {
    uploadLoadingMaskEl?.remove();
    uploadLoadingMaskEl = null;
}

function openComposeModal() {
    if (!bootstrap.user) {
        navigate(buildLoginUrl(`${window.location.pathname}${window.location.search}`));
        return;
    }
    closeDrawer();
    closeComposeModal();

    const root = document.createElement('div');
    root.className = 'ts-modal-root';

    const backdrop = document.createElement('button');
    backdrop.type = 'button';
    backdrop.className = 'ts-modal-backdrop';
    backdrop.setAttribute('aria-label', t('closeDialog'));

    const dialog = document.createElement('div');
    dialog.className = 'ts-modal-dialog';
    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('aria-modal', 'true');
    dialog.setAttribute('aria-labelledby', 'ts-compose-modal-title');

    const header = document.createElement('div');
    header.className = 'ts-modal-header';
    const titleEl = document.createElement('h2');
    titleEl.id = 'ts-compose-modal-title';
    titleEl.className = 'ts-modal-title';
    titleEl.textContent = t('composeQuick');
    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'ts-modal-close ts-header-icon-btn ts-btn ts-btn--ghost';
    closeBtn.setAttribute('aria-label', t('closeDialog'));
    closeBtn.appendChild(createIcon('x', 22));
    header.append(titleEl, closeBtn);

    const body = document.createElement('div');
    body.className = 'ts-modal-body';
    body.appendChild(
        buildComposeForm(true, (tid) => {
            closeComposeModal();
            navigate(`/post/view/${tid}`);
        }, 'modal'),
    );

    const foot = document.createElement('div');
    foot.className = 'ts-modal-footer';
    const fullLink = document.createElement('a');
    fullLink.href = '/post/create';
    fullLink.className = 'ts-modal-footer-link';
    fullLink.textContent = t('composeFullEditor');
    fullLink.addEventListener('click', (e) => {
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
        e.preventDefault();
        closeComposeModal();
        navigate('/post/create');
    });
    foot.appendChild(fullLink);

    dialog.append(header, body, foot);
    root.append(backdrop, dialog);
    document.body.appendChild(root);

    const prevBodyOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    const onKeyDown = (e) => {
        if (e.key === 'Escape') {
            e.preventDefault();
            closeComposeModal();
        }
    };
    document.addEventListener('keydown', onKeyDown);

    const teardown = () => {
        document.removeEventListener('keydown', onKeyDown);
        document.body.style.overflow = prevBodyOverflow;
        root.remove();
        composeModalTeardown = null;
    };
    composeModalTeardown = teardown;

    backdrop.addEventListener('click', teardown);
    closeBtn.addEventListener('click', teardown);

    requestAnimationFrame(() => {
        document.getElementById('ts-co-title-modal')?.focus();
    });
}

function truncateHeaderTitle(text, max = 18) {
    const s = String(text || '');
    if (s.length <= max) return s;
    return `${s.slice(0, Math.max(0, max - 1))}…`;
}

let headerMenuCloserInstalled = false;

function installHeaderMenuCloser() {
    if (headerMenuCloserInstalled) return;
    headerMenuCloserInstalled = true;
    document.addEventListener('click', (e) => {
        const t = e.target;
        if (!(t instanceof Element)) return;
        if (t.closest('.ts-header-menu')) return;
        document.querySelectorAll('.ts-header-menu[open]').forEach((det) => det.removeAttribute('open'));
    });
}

function closeOtherHeaderMenus(except) {
    document.querySelectorAll('details.ts-header-menu[open]').forEach((d) => {
        if (d !== except) d.removeAttribute('open');
    });
}

/**
 * @param {{ includeProfile?: boolean }} [opts]
 * @returns {{ href: string, label: string, icon: string }[]}
 */
function getShellAccountMenuRows(opts = {}) {
    if (!bootstrap.user) return [];
    const u = bootstrap.user.username;
    const includeProfile = opts.includeProfile !== false;
    const rows = [];
    if (includeProfile) {
        rows.push({href: `/user/detail/${encodeURIComponent(u)}`, label: t('profile'), icon: 'user'});
    }
    rows.push(
        {href: '/notify/view', label: t('notify'), icon: 'bell'},
        {href: '/pay/wallet', label: t('walletMenu'), icon: 'wallet'},
        {href: '/setting', label: t('settings'), icon: 'sliders'},
    );
    return rows;
}

function buildAccountHeaderDropdown(navigateFn) {
    installHeaderMenuCloser();
    if (!bootstrap.user) return null;

    const det = document.createElement('details');
    det.className = 'ts-header-menu ts-account-menu ts-visible-desktop';

    const summary = document.createElement('summary');
    summary.className = 'ts-header-account-trigger';
    summary.setAttribute('aria-haspopup', 'menu');
    summary.setAttribute('aria-label', t('accountMenu'));

    const av = document.createElement('img');
    av.className = 'ts-header-account-avatar';
    av.src = `/user/avatar?username=${encodeURIComponent(bootstrap.user.username)}`;
    av.alt = '';

    const meta = document.createElement('span');
    meta.className = 'ts-header-account-meta';
    const nm = document.createElement('span');
    nm.className = 'ts-header-account-name';
    nm.textContent = bootstrap.user.nickname || bootstrap.user.username;
    meta.appendChild(nm);
    summary.append(av, meta);

    const panel = document.createElement('div');
    panel.className = 'ts-header-menu-panel ts-header-menu-panel--account';
    panel.setAttribute('role', 'menu');

    getShellAccountMenuRows().forEach((row) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ts-header-menu-item ts-header-menu-item--row';
        btn.setAttribute('role', 'menuitem');
        const ic = document.createElement('span');
        ic.className = 'ts-header-menu-item-icon';
        ic.appendChild(createIcon(row.icon, 18));
        const tx = document.createElement('span');
        tx.className = 'ts-header-menu-item-text';
        tx.textContent = row.label;
        btn.append(ic, tx);
        btn.addEventListener('click', () => {
            det.removeAttribute('open');
            navigateFn(row.href);
        });
        panel.appendChild(btn);
    });

    const locBtn = document.createElement('button');
    locBtn.type = 'button';
    locBtn.className = 'ts-header-menu-item ts-header-menu-item--row';
    locBtn.setAttribute('role', 'menuitem');
    const locIc = document.createElement('span');
    locIc.className = 'ts-header-menu-item-icon';
    locIc.appendChild(createIcon('globe', 18));
    const locTx = document.createElement('span');
    locTx.className = 'ts-header-menu-item-text';
    locTx.textContent = getLocale() === 'zh-CN' ? t('switchToEn') : t('switchToZh');
    locBtn.append(locIc, locTx);
    locBtn.addEventListener('click', () => {
        det.removeAttribute('open');
        toggleLocale();
    });
    panel.appendChild(locBtn);

    const outBtn = document.createElement('button');
    outBtn.type = 'button';
    outBtn.className = 'ts-header-menu-item ts-header-menu-item--row ts-header-menu-item--danger';
    outBtn.setAttribute('role', 'menuitem');
    const outIc = document.createElement('span');
    outIc.className = 'ts-header-menu-item-icon';
    outIc.appendChild(createIcon('logOut', 18));
    const outTx = document.createElement('span');
    outTx.className = 'ts-header-menu-item-text';
    outTx.textContent = t('logout');
    outBtn.append(outIc, outTx);
    outBtn.addEventListener('click', async () => {
        det.removeAttribute('open');
        await jsonPost('/index/out', {csrf_token: csrfToken});
        await refreshBootstrap();
        navigateFn('/');
    });
    panel.appendChild(outBtn);

    det.append(summary, panel);
    det.addEventListener('toggle', () => {
        if (det.open) closeOtherHeaderMenus(det);
    });
    return det;
}

let composePopoverCloserInstalled = false;

function installComposePopoverCloser() {
    if (composePopoverCloserInstalled) return;
    composePopoverCloserInstalled = true;
    document.addEventListener('click', (e) => {
        const t = e.target;
        if (!(t instanceof Element)) return;
        if (t.closest('.ts-compose-popover')) return;
        document.querySelectorAll('details.ts-compose-popover[open]').forEach((det) => det.removeAttribute('open'));
    });
}

function buildComposeHeaderQuickButton() {
    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'ts-header-icon-btn ts-btn ts-btn--ghost';
    btn.setAttribute('aria-label', t('composeQuick'));
    btn.appendChild(createIcon('edit', 22));
    btn.addEventListener('click', () => openComposeModal());
    return btn;
}

function buildHomeHeaderAction() {
    const hi = document.createElement('a');
    hi.href = '/';
    hi.className = 'ts-header-icon-btn ts-btn ts-btn--ghost';
    hi.setAttribute('aria-label', t('backHome'));
    hi.appendChild(createIcon('home', 22));
    hi.addEventListener('click', (e) => {
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
        e.preventDefault();
        navigate('/');
    });
    return hi;
}

function debounce(fn, ms) {
    let timer = null;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), ms);
    };
}

function setCsrf(t) {
    if (t) csrfToken = t;
}

function normalizeHex(c) {
    if (!c || typeof c !== 'string') return '#1996ff';
    let h = c.trim();
    if (!h.startsWith('#')) h = `#${h}`;
    return /^#[0-9A-Fa-f]{6}$/.test(h) ? h : '#1996ff';
}

function applyThemeFromHex(hex) {
    const h = normalizeHex(hex);
    const n = parseInt(h.slice(1), 16);
    const r = (n >> 16) & 255;
    const g = (n >> 8) & 255;
    const b = n & 255;
    const clamp = (x) => Math.max(0, Math.min(255, Math.round(x)));
    const dim = `#${[clamp(r * 0.72), clamp(g * 0.72), clamp(b * 0.72)]
        .map((x) => x.toString(16).padStart(2, '0'))
        .join('')}`;
    const hover = `#${[clamp(r + (255 - r) * 0.12), clamp(g + (255 - g) * 0.12), clamp(b + (255 - b) * 0.12)]
        .map((x) => x.toString(16).padStart(2, '0'))
        .join('')}`;
    const root = document.documentElement;
    root.style.setProperty('--tw-accent', h);
    root.style.setProperty('--tw-accent-dim', dim);
    root.style.setProperty('--tw-accent-hover', hover);
    root.style.setProperty('--tw-accent-glow', `rgba(${r},${g},${b},0.24)`);
    root.style.setProperty('--tw-brand-social', h);
    root.style.setProperty('--tw-brand-social-dim', dim);
    root.style.setProperty('--tw-brand-social-glow', `rgba(${r},${g},${b},0.22)`);
    const meta = document.querySelector('meta[name="theme-color"]');
    if (meta) meta.setAttribute('content', h);
}

applyThemeFromHex(bootstrap.themeColor || '#1996ff');

async function refreshBootstrap() {
    const routePre = parseRoute(window.location.pathname);
    if (routePre.name !== 'oauth_authorize') delete bootstrap.oauthAuthorize;
    if (routePre.name !== 'oauth_bind') delete bootstrap.oauthBind;

    const j = await jsonGet('/index/boot');
    if (j.ret !== 0) return j;

    if (j.theme_color) {
        applyThemeFromHex(j.theme_color);
        bootstrap.themeColor = j.theme_color;
    }
    if (j.csrf_token) {
        setCsrf(j.csrf_token);
        bootstrap.user = j.user;
    }
    const bootStrKeys = ['copyright', 'icpBeian', 'icpBeianUrl', 'poweredByUrl', 'poweredByName', 'poweredByPrefix', 'poweredByPlain', 'locale'];
    for (const k of bootStrKeys) {
        if (typeof j[k] === 'string') bootstrap[k] = j[k];
    }
    if (Object.prototype.hasOwnProperty.call(j, 'legalFooterOneLine')) {
        bootstrap.legalFooterOneLine = !!j.legalFooterOneLine;
    }
    if (Object.prototype.hasOwnProperty.call(j, 'wallet')) bootstrap.wallet = j.wallet;
    if (Object.prototype.hasOwnProperty.call(j, 'allow_reg')) bootstrap.allowReg = !!j.allow_reg;
    if (Array.isArray(j.oauth)) bootstrap.oauth = j.oauth;
    return j;
}

function navLink(path, label, iconName = null) {
    const a = document.createElement('a');
    a.href = path;
    if (iconName) {
        const ic = document.createElement('span');
        ic.className = 'ts-nav-link-icon';
        ic.appendChild(createIcon(iconName, 17));
        a.appendChild(ic);
        a.appendChild(document.createTextNode(label));
    } else {
        a.textContent = label;
    }
    a.addEventListener('click', (e) => {
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
        e.preventDefault();
        navigate(path);
    });
    return a;
}

function sideNavLink(path, label, navigateFn, iconName = null) {
    const a = document.createElement('a');
    a.href = path;
    if (iconName) {
        const ic = document.createElement('span');
        ic.className = 'ts-side-nav-icon';
        ic.appendChild(createIcon(iconName, 18));
        a.appendChild(ic);
        const t = document.createElement('span');
        t.className = 'ts-side-nav-text';
        t.textContent = label;
        a.appendChild(t);
    } else {
        a.textContent = label;
    }
    a.addEventListener('click', (e) => {
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
        e.preventDefault();
        navigateFn(path);
    });
    return a;
}

function appendPoweredByNodes(host) {
    const url = (bootstrap.poweredByUrl || '').trim();
    const name = (bootstrap.poweredByName || '').trim() || 'TwimiSNS';
    const prefixCfg = (bootstrap.poweredByPrefix || '').trim();
    const plainCfg = (bootstrap.poweredByPlain || '').trim();

    if (url) {
        const prefix = prefixCfg || t('poweredByPrefix');
        const spaced = prefix.endsWith(' ') ? prefix : `${prefix} `;
        host.appendChild(document.createTextNode(spaced));
        const a = document.createElement('a');
        a.href = url;
        a.rel = 'noopener noreferrer';
        a.target = '_blank';
        a.textContent = name;
        host.appendChild(a);
    } else if (plainCfg) {
        host.appendChild(document.createTextNode(plainCfg));
    } else {
        host.appendChild(document.createTextNode(t('poweredBy')));
    }
}

function appendSiteLegalContent(parent) {
    const copy = (bootstrap.copyright || '').trim();
    const icp = (bootstrap.icpBeian || '').trim();
    const icpUrl = (bootstrap.icpBeianUrl || '').trim();
    const oneLine = bootstrap.legalFooterOneLine === true;

    if (oneLine) {
        const line = document.createElement('div');
        line.className = 'ts-site-footer-line ts-site-footer-line--muted ts-site-footer--oneline';
        const frag = document.createDocumentFragment();
        const chunks = [];
        if (copy) {
            chunks.push(() => frag.appendChild(document.createTextNode(copy)));
        }
        if (icp) {
            chunks.push(() => {
                if (icpUrl) {
                    const a = document.createElement('a');
                    a.href = icpUrl;
                    a.rel = 'noopener noreferrer';
                    a.target = '_blank';
                    a.textContent = icp;
                    frag.appendChild(a);
                } else {
                    frag.appendChild(document.createTextNode(icp));
                }
            });
        }
        chunks.push(() => appendPoweredByNodes(frag));
        chunks.forEach((fn, i) => {
            if (i > 0) {
                frag.appendChild(document.createTextNode(' '));
            }
            fn();
        });
        line.appendChild(frag);
        parent.appendChild(line);
        return;
    }

    if (copy) {
        const p = document.createElement('div');
        p.className = 'ts-site-footer-line';
        p.textContent = copy;
        parent.appendChild(p);
    }

    if (icp) {
        const p = document.createElement('div');
        p.className = 'ts-site-footer-line';
        if (icpUrl) {
            const a = document.createElement('a');
            a.href = icpUrl;
            a.rel = 'noopener noreferrer';
            a.target = '_blank';
            a.textContent = icp;
            p.appendChild(a);
        } else {
            p.textContent = icp;
        }
        parent.appendChild(p);
    }

    const powered = document.createElement('div');
    powered.className = 'ts-site-footer-line ts-site-footer-line--muted';
    appendPoweredByNodes(powered);
    parent.appendChild(powered);

    if (!copy && !icp) {
        const p = document.createElement('div');
        p.className = 'ts-site-footer-line ts-site-footer-line--muted';
        p.textContent = bootstrap.siteName || 'TwimiSNS';
        parent.appendChild(p);
    }
}

function drawerMenuLink(path, label, navigateFn, iconName) {
    const a = document.createElement('a');
    a.href = path;
    a.className = 'ts-drawer-menu-item';
    const ic = document.createElement('span');
    ic.className = 'ts-drawer-menu-icon';
    ic.appendChild(createIcon(iconName, 20));
    const t = document.createElement('span');
    t.className = 'ts-drawer-menu-text';
    t.textContent = label;
    a.append(ic, t);
    a.addEventListener('click', (e) => {
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
        e.preventDefault();
        navigateFn(path);
    });
    return a;
}

function populateSidebars(navigateFn) {
    const left = document.getElementById('ts-sidebar-left');
    const right = document.getElementById('ts-sidebar-right');
    if (!left || !right) return;

    left.innerHTML = '';
    right.innerHTML = '';

    const navDesktop = document.createElement('nav');
    navDesktop.className = 'ts-side-nav ts-visible-desktop';
    navDesktop.appendChild(sideNavLink('/', t('home'), navigateFn, 'home'));
    navDesktop.appendChild(sideNavLink('/post/search', t('search'), navigateFn, 'search'));
    navDesktop.appendChild(sideNavLink('/friend', t('friends'), navigateFn, 'users'));
    if (bootstrap.user) {
        navDesktop.appendChild(
            sideNavLink(
                `/user/detail/${encodeURIComponent(bootstrap.user.username)}`,
                t('profile'),
                navigateFn,
                'user',
            ),
        );
    }
    left.appendChild(navDesktop);

    const drawerMobile = document.createElement('div');
    drawerMobile.className = 'ts-drawer-mobile';

    if (bootstrap.user) {
        const u = bootstrap.user.username;
        const profileHref = `/user/detail/${encodeURIComponent(u)}`;
        const profileHead = document.createElement('a');
        profileHead.href = profileHref;
        profileHead.className = 'ts-drawer-profile';
        profileHead.innerHTML = `
            <img class="ts-drawer-avatar" src="/user/avatar?username=${encodeURIComponent(u)}" alt="">
            <div class="ts-drawer-profile-text">
                <div class="ts-drawer-name">${escapeHtml(bootstrap.user.nickname || u)}</div>
                <div class="ts-drawer-handle">@${escapeHtml(u)}</div>
            </div>`;
        profileHead.addEventListener('click', (e) => {
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
            e.preventDefault();
            navigateFn(profileHref);
        });
        drawerMobile.appendChild(profileHead);

        const menu = document.createElement('nav');
        menu.className = 'ts-drawer-menu';
        menu.setAttribute('aria-label', '功能菜单');
        getShellAccountMenuRows({includeProfile: false}).forEach((row) => {
            menu.appendChild(drawerMenuLink(row.href, row.label, navigateFn, row.icon));
        });
        const lo = document.createElement('button');
        lo.type = 'button';
        lo.className = 'ts-drawer-menu-item ts-drawer-menu-item--danger';
        const loIc = document.createElement('span');
        loIc.className = 'ts-drawer-menu-icon';
        loIc.appendChild(createIcon('logOut', 20));
        const loTx = document.createElement('span');
        loTx.className = 'ts-drawer-menu-text';
        loTx.textContent = t('logout');
        lo.append(loIc, loTx);
        lo.addEventListener('click', async () => {
            await jsonPost('/index/out', {csrf_token: csrfToken});
            await refreshBootstrap();
            navigateFn('/');
        });
        menu.appendChild(lo);
        drawerMobile.appendChild(menu);
    } else {
        const menu = document.createElement('nav');
        menu.className = 'ts-drawer-menu';
        menu.appendChild(drawerMenuLink('/user/login', t('login'), navigateFn, 'logIn'));
        if (bootstrap.allowReg) {
            menu.appendChild(drawerMenuLink('/user/register', t('register'), navigateFn, 'userPlus'));
        }
        drawerMobile.appendChild(menu);
    }

    const langRow = document.createElement('button');
    langRow.type = 'button';
    langRow.className = 'ts-drawer-menu-item ts-drawer-menu-item--muted';
    const langIc = document.createElement('span');
    langIc.className = 'ts-drawer-menu-icon';
    langIc.appendChild(createIcon('globe', 20));
    const langTx = document.createElement('span');
    langTx.className = 'ts-drawer-menu-text';
    langTx.textContent = getLocale() === 'zh-CN' ? t('switchToEn') : t('switchToZh');
    langRow.append(langIc, langTx);
    langRow.addEventListener('click', () => toggleLocale());
    drawerMobile.appendChild(langRow);

    left.appendChild(drawerMobile);

    const about = document.createElement('div');
    about.className = 'ts-aside-note ts-visible-desktop';
    about.innerHTML = `<div class="ts-aside-kicker">${escapeHtml(t('about'))}</div><p class="ts-text-muted">${escapeHtml(bootstrap.siteName || 'TwimiSNS')}</p>`;
    right.appendChild(about);

    const legal = document.createElement('div');
    legal.className = 'ts-aside-foot';
    const legalInner = document.createElement('div');
    legalInner.className = 'ts-site-footer-inner ts-aside-foot-inner';
    appendSiteLegalContent(legalInner);
    legal.appendChild(legalInner);
    right.appendChild(legal);
}

function isStandalonePublicPath(pathname) {
    const p = pathname.replace(/\/+$/, '') || '/';
    return p.startsWith('/oauth/connect');
}

/**
 * @param {string | Node} inner
 * @param {{ authMode?: 'login' | 'register' }} shellOpts
 */
function mountAuthShell(inner, shellOpts = {}) {
    const app = document.getElementById('app');
    if (!app) return;
    app.innerHTML = '';
    app.className = 'ts-shell ts-shell--auth';

    const floatbar = document.createElement('div');
    floatbar.className = 'ts-auth-floatbar';

    const floatLeading = document.createElement('div');
    floatLeading.className = 'ts-auth-floatbar-leading';
    const homeBtn = document.createElement('button');
    homeBtn.type = 'button';
    homeBtn.className = 'ts-btn ts-btn--ghost ts-auth-float-btn';
    homeBtn.setAttribute('aria-label', t('backHome'));
    homeBtn.appendChild(createIcon('home', 22));
    homeBtn.addEventListener('click', () => navigate('/'));
    floatLeading.appendChild(homeBtn);

    const floatTrailing = document.createElement('div');
    floatTrailing.className = 'ts-auth-floatbar-trailing';
    const q = window.location.search || '';
    const mode = shellOpts.authMode;
    if (mode === 'login' && bootstrap.allowReg) {
        const a = navLink(`/user/register${q}`, t('register'));
        a.classList.add('ts-auth-float-link');
        floatTrailing.appendChild(a);
    } else if (mode === 'register') {
        const a = navLink(`/user/login${q}`, t('login'));
        a.classList.add('ts-auth-float-link');
        floatTrailing.appendChild(a);
    }
    const langBtn = document.createElement('button');
    langBtn.type = 'button';
    langBtn.className = 'ts-btn ts-btn--ghost ts-auth-float-btn ts-auth-float-lang';
    langBtn.textContent = getLocale() === 'zh-CN' ? 'EN' : '中文';
    langBtn.setAttribute('aria-label', getLocale() === 'zh-CN' ? t('switchToEn') : t('switchToZh'));
    langBtn.addEventListener('click', () => toggleLocale());
    floatTrailing.appendChild(langBtn);

    floatbar.append(floatLeading, floatTrailing);

    const stage = document.createElement('div');
    stage.className = 'ts-auth-stage';

    const stageMain = document.createElement('div');
    stageMain.className = 'ts-auth-stage-main';
    if (typeof inner === 'string') {
        stageMain.innerHTML = inner;
    } else if (inner instanceof Node) {
        stageMain.appendChild(inner);
    }

    const legalFoot = document.createElement('footer');
    legalFoot.className = 'ts-auth-footer';
    legalFoot.setAttribute('role', 'contentinfo');
    const legalInner = document.createElement('div');
    legalInner.className = 'ts-site-footer-inner ts-auth-footer-inner';
    appendSiteLegalContent(legalInner);
    legalFoot.appendChild(legalInner);

    stage.append(stageMain, legalFoot);

    app.appendChild(floatbar);
    app.appendChild(stage);
}

function buildAuthPageHero(titleKey) {
    const hero = document.createElement('div');
    hero.className = 'ts-auth-hero';

    const brandRow = document.createElement('div');
    brandRow.className = 'ts-auth-brand-row';

    const logo = document.createElement('img');
    logo.className = 'ts-auth-logo';
    logo.src = '/static/img/logo.png';
    logo.alt = '';
    logo.decoding = 'async';

    const wordmark = document.createElement('span');
    wordmark.className = 'ts-auth-brand-wordmark';
    wordmark.textContent = bootstrap.siteName || 'TwimiSNS';

    logo.addEventListener('load', () => {
        const w = logo.naturalWidth;
        const h = logo.naturalHeight || 1;
        if (w / h >= 2) {
            wordmark.hidden = true;
        }
    });
    logo.addEventListener('error', () => {
        logo.remove();
        wordmark.classList.add('ts-auth-brand-wordmark--solo');
    });

    brandRow.append(logo, wordmark);

    const h1 = document.createElement('h1');
    h1.className = 'ts-auth-heading';
    h1.textContent = t(titleKey);
    hero.append(brandRow, h1);
    return hero;
}

function mountShell(inner, shellOpts = {}) {
    if (shellOpts.shellVariant === 'auth') {
        mountAuthShell(inner, shellOpts);
        return;
    }

    const headerTitle = shellOpts.headerTitle ?? null;
    const suppressComposeHeader = shellOpts.suppressComposeHeader === true;
    const explicitHeaderActions = Object.prototype.hasOwnProperty.call(shellOpts, 'headerActions');
    let headerActions = explicitHeaderActions ? shellOpts.headerActions : null;
    if (!explicitHeaderActions && bootstrap.user && !suppressComposeHeader) {
        headerActions = buildComposeHeaderQuickButton();
    }

    const app = document.getElementById('app');
    if (!app) return;
    app.innerHTML = '';
    app.className = 'ts-shell';
    if (shellOpts.shellVariant === 'settings') {
        app.classList.add('ts-shell--settings-focus');
    }

    const nav = document.createElement('header');
    nav.className = 'ts-topnav';

    const leading = document.createElement('div');
    leading.className = 'ts-topnav-leading';

    const menuToggle = document.createElement('button');
    menuToggle.type = 'button';
    menuToggle.className = 'ts-menu-toggle ts-btn ts-btn--ghost';
    menuToggle.setAttribute('aria-expanded', 'false');
    menuToggle.setAttribute('aria-controls', 'ts-sidebar-left');
    if (bootstrap.user) {
        menuToggle.classList.add('ts-menu-toggle--avatar');
        menuToggle.setAttribute('aria-label', t('accountMenu'));
        const av = document.createElement('img');
        av.className = 'ts-drawer-toggle-avatar';
        av.src = `/user/avatar?username=${encodeURIComponent(bootstrap.user.username)}`;
        av.alt = '';
        menuToggle.appendChild(av);
    } else {
        menuToggle.setAttribute('aria-label', t('openMenu'));
        const menuIcWrap = document.createElement('span');
        menuIcWrap.className = 'ts-menu-toggle-icon';
        menuIcWrap.appendChild(createIcon('menu', 22));
        menuToggle.appendChild(menuIcWrap);
    }
    menuToggle.addEventListener('click', () => {
        const open = app.classList.toggle('ts-drawer-open');
        menuToggle.setAttribute('aria-expanded', String(open));
        document.body.style.overflow = open ? 'hidden' : '';
    });
    leading.appendChild(menuToggle);

    const brandDesktop = document.createElement('a');
    brandDesktop.className = 'ts-brand ts-brand--desktop';
    brandDesktop.href = '/';
    brandDesktop.textContent = bootstrap.siteName || 'TwimiSNS';
    brandDesktop.addEventListener('click', (e) => {
        if (e.metaKey || e.ctrlKey) return;
        e.preventDefault();
        navigate('/');
    });
    leading.appendChild(brandDesktop);

    const center = document.createElement('div');
    center.className = 'ts-topnav-center';
    const siteName = bootstrap.siteName || 'TwimiSNS';
    if (headerTitle) {
        const sp = document.createElement('span');
        sp.className = 'ts-topnav-page-title';
        sp.textContent = truncateHeaderTitle(headerTitle, 20);
        center.appendChild(sp);
    } else {
        const bm = document.createElement('a');
        bm.className = 'ts-brand ts-brand--mobile';
        bm.href = '/';
        bm.textContent = siteName;
        bm.addEventListener('click', (e) => {
            if (e.metaKey || e.ctrlKey) return;
            e.preventDefault();
            navigate('/');
        });
        center.appendChild(bm);
    }

    const trailing = document.createElement('div');
    trailing.className = 'ts-topnav-trailing';

    const actionsSlot = document.createElement('div');
    actionsSlot.className = 'ts-header-actions';
    actionsSlot.id = 'ts-header-actions-slot';
    if (headerActions) {
        actionsSlot.appendChild(headerActions);
    }
    if (bootstrap.user) {
        const acct = buildAccountHeaderDropdown(navigate);
        if (acct) actionsSlot.appendChild(acct);
    }
    trailing.appendChild(actionsSlot);

    if (!bootstrap.user) {
        const links = document.createElement('nav');
        links.className = 'ts-nav-links ts-nav-links--compact ts-header-nav-desktop';
        links.appendChild(navLink('/user/login', t('login'), 'logIn'));
        if (bootstrap.allowReg) {
            links.appendChild(navLink('/user/register', t('register'), 'userPlus'));
        }
        trailing.appendChild(links);
    }

    nav.append(leading, center, trailing);

    const backdrop = document.createElement('div');
    backdrop.className = 'ts-drawer-backdrop';
    backdrop.addEventListener('click', () => {
        app.classList.remove('ts-drawer-open');
        menuToggle.setAttribute('aria-expanded', 'false');
        document.body.style.overflow = '';
    });

    const layout = document.createElement('div');
    layout.className = 'ts-layout';

    const asideL = document.createElement('aside');
    asideL.className = 'ts-layout-side ts-layout-side--left';
    asideL.id = 'ts-sidebar-left';

    const main = document.createElement('main');
    main.className = 'ts-layout-main';
    if (typeof inner === 'string') {
        main.innerHTML = inner;
    } else if (inner instanceof Node) {
        main.appendChild(inner);
    }

    const asideR = document.createElement('aside');
    asideR.className = 'ts-layout-side ts-layout-side--right';
    asideR.id = 'ts-sidebar-right';

    layout.append(asideL, main, asideR);

    app.appendChild(nav);
    app.appendChild(backdrop);
    app.appendChild(layout);
    app.appendChild(buildAppTabbar());

    void populateSidebars(navigate);
}

function parseRoute(pathname) {
    let p = pathname || '/';
    if (p.includes('/index.php/')) {
        p = `/${p.split('/index.php/')[1] || ''}`;
    }
    if (p.endsWith('/index.php')) p = p.slice(0, -'/index.php'.length) || '/';
    p = p.replace(/\/+$/, '') || '/';
    const segs = p.split('/').filter(Boolean);

    if (segs.length === 0) return {name: 'home'};

    if (segs[0] === 'post') {
        if (segs.length === 1 || (segs.length === 2 && segs[1] === 'list')) return {name: 'home'};
        if (segs[1] === 'view' && segs[2]) return {name: 'post', tid: segs[2]};
        if (segs[1] === 'buy' && segs[2]) return {name: 'buy', tid: segs[2]};
        if (segs[1] === 'create') return {name: 'create'};
        if (segs[1] === 'search') return {name: 'search'};
    }
    if (segs[0] === 'user') {
        if (segs[1] === 'login') return {name: 'login'};
        if (segs[1] === 'register') return {name: 'register'};
        if (segs[1] === 'info') return {name: 'user', username: '', tab: ''};
        if (segs[1] === 'panel') {
            if (!segs[2]) return {name: 'user', username: '', tab: ''};
            if (segs[3] === 'post') {
                return {name: 'user', username: decodeURIComponent(segs[2]), tab: 'post'};
            }
            return {name: 'user', username: decodeURIComponent(segs[2]), tab: ''};
        }
        if (segs[1] === 'detail' && segs[2]) {
            return {name: 'user', username: decodeURIComponent(segs[2]), tab: segs[3] === 'post' ? 'post' : ''};
        }
        if (segs[1] === 'detail' && !segs[2]) {
            return {name: 'user', username: '', tab: ''};
        }
    }
    if (segs[0] === 'setting') {
        if (!segs[1]) return {name: 'setting', section: 'index'};
        if (segs[1] === 'avatar') return {name: 'setting', section: 'avatar'};
        if (segs[1] === 'oauth') return {name: 'setting', section: 'oauth'};
        return {name: 'unknown', path: p};
    }

    if (segs[0] === 'pay' && segs[1] === 'start') {
        return {name: 'pay_start'};
    }
    if (segs[0] === 'pay' && (segs[1] === 'wallet' || segs[1] === 'balance')) {
        return {name: 'wallet'};
    }

    if (segs[0] === 'oauth') {
        if (segs[1] === 'authorize') return {name: 'oauth_authorize'};
        if (segs[1] === 'callback' && segs[2]) return {name: 'oauth_bind', bindType: segs[2]};
    }

    if (segs[0] === 'friend') return {name: 'friend'};

    if (segs[0] === 'notify' && segs[1] === 'view') return {name: 'notify'};

    return {name: 'unknown', path: p};
}

function buildAppTabbar() {
    const route = parseRoute(window.location.pathname);
    let active = 'home';
    if (route.name === 'search') active = 'search';
    else if (route.name === 'friend') active = 'friend';
    else if (
        bootstrap.user &&
        route.name === 'user' &&
        (route.username === '' || route.username === bootstrap.user.username)
    ) {
        active = 'me';
    }

    const bar = document.createElement('nav');
    bar.className = 'ts-app-tabbar';
    bar.setAttribute('aria-label', '主导航');

    const meHref = bootstrap.user
        ? `/user/detail/${encodeURIComponent(bootstrap.user.username)}`
        : '/user/login';

    const items = [
        {key: 'home', href: '/', label: t('home'), icon: 'home'},
        {key: 'search', href: '/post/search', label: t('search'), icon: 'search'},
        {key: 'friend', href: '/friend', label: t('friends'), icon: 'users'},
        {key: 'me', href: meHref, label: t('me'), icon: 'user'},
    ];

    for (const {key, href, label, icon} of items) {
        const a = document.createElement('a');
        a.href = href;
        a.className = 'ts-app-tab' + (active === key ? ' ts-app-tab--active' : '');
        if (active === key) a.setAttribute('aria-current', 'page');
        const icWrap = document.createElement('span');
        icWrap.className = 'ts-app-tab-icon';
        icWrap.appendChild(createIcon(icon, 22));
        const span = document.createElement('span');
        span.className = 'ts-app-tab-label';
        span.textContent = label;
        a.append(icWrap, span);
        bar.appendChild(a);
    }

    return bar;
}

function navigate(path) {
    closeComposeModal();
    closeDrawer();
    if (path !== window.location.pathname) {
        history.pushState(null, '', path);
    }
    void render();
}

window.addEventListener('popstate', () => void render());

document.addEventListener('click', (e) => {
    const a = e.target.closest('a[href]');
    if (!a || e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
    const u = new URL(a.href, window.location.origin);
    if (u.origin !== window.location.origin) return;
    if (u.pathname.startsWith('/install')) return;
    if (isStandalonePublicPath(u.pathname)) return;
    e.preventDefault();
    navigate(u.pathname + u.search);
});

function formatWalletCredit(n) {
    if (n === null || n === undefined || Number.isNaN(Number(n))) return '—';
    const x = Number(n);
    if (Number.isInteger(x)) return String(x);
    return x.toFixed(2).replace(/\.?0+$/, '');
}

function fmtTime(ts) {
    if (!ts) return '';
    const n = Number(ts);
    const ms = n > 1e12 ? n : n * 1000;
    return new Date(ms).toLocaleString();
}

function fmtRelativeTime(ts) {
    if (!ts) return '';
    const n = Number(ts);
    const ms = n > 1e12 ? n : n * 1000;
    const diff = Date.now() - ms;
    const sec = Math.floor(diff / 1000);
    if (sec < 45) return '刚刚';
    const min = Math.floor(sec / 60);
    if (min < 60) return `${min}分钟前`;
    const hr = Math.floor(min / 60);
    if (hr < 24) return `${hr}小时前`;
    const day = Math.floor(hr / 24);
    if (day < 7) return `${day}天前`;
    return new Date(ms).toLocaleDateString();
}

function postShowState(post) {
    const n = Number(post.show_state);
    if (n === 1 || n === 2) return n;
    const c = post.content || '';
    if (c === '[付费帖子]') return 1;
    if (c === '[登录可见]') return 2;
    return 0;
}

function safeRefererPath(raw) {
    const r = raw && String(raw).trim();
    if (r && r.startsWith('/') && !r.startsWith('//')) return r;
    return null;
}

function buildLoginUrl(returnPath) {
    return `/user/login?referer=${encodeURIComponent(returnPath)}`;
}

function oauthConnectHref(type, refererPath) {
    const q = refererPath ? `?referer=${encodeURIComponent(refererPath)}` : '';
    return `/oauth/connect/${encodeURIComponent(type)}${q}`;
}

async function afterAuthSessionRefreshNavigate() {
    await refreshBootstrap();
    const ref = safeRefererPath(new URLSearchParams(window.location.search).get('referer'));
    navigate(ref || '/');
}

function renderVisibilityGate({showState, tid, coin}, navigateFn) {
    const wrap = document.createElement('div');
    wrap.className = 'ts-content-gate';
    const inner = document.createElement('div');
    inner.className = 'ts-content-gate-inner';

    if (showState === 2) {
        const lead = document.createElement('div');
        lead.className = 'ts-gate-lead';
        lead.innerHTML = '<span class="ts-gate-icon" aria-hidden="true">🔒</span>';
        const strong = document.createElement('strong');
        strong.textContent = t('gateLoginStrong');
        lead.appendChild(strong);
        const desc = document.createElement('p');
        desc.className = 'ts-gate-desc';
        desc.textContent = t('gateLoginDesc');
        const login = navLink(buildLoginUrl(`/post/view/${tid}`), t('loginToView'));
        login.classList.add('ts-btn', 'primary');
        inner.append(lead, desc, login);
    } else if (showState === 1) {
        const lead = document.createElement('div');
        lead.className = 'ts-gate-lead';
        lead.innerHTML = '<span class="ts-gate-icon" aria-hidden="true">💎</span>';
        const strong = document.createElement('strong');
        strong.textContent = t('gatePaidStrong');
        lead.appendChild(strong);
        const desc = document.createElement('p');
        desc.className = 'ts-gate-desc';
        const priceLabel = document.createElement('strong');
        priceLabel.textContent = coin != null && coin !== '' ? String(coin) : '?';
        desc.append(t('gatePaidPay'), priceLabel, t('gatePaidAfter'));
        const actions = document.createElement('div');
        actions.className = 'ts-gate-actions';
        if (!bootstrap.user) {
            const login = navLink(buildLoginUrl(`/post/view/${tid}`), t('loginAndBuy'));
            login.classList.add('ts-btn', 'primary');
            actions.appendChild(login);
        } else {
            const buy = document.createElement('button');
            buy.type = 'button';
            buy.className = 'ts-btn primary';
            buy.textContent = `${t('unlockNow')}${priceLabel.textContent}`;
            buy.addEventListener('click', () => navigateFn(`/post/buy/${tid}`));
            actions.appendChild(buy);
        }
        inner.append(lead, desc, actions);
    }

    wrap.appendChild(inner);
    return wrap;
}

function buildFeedPostCard(post, navigateFn) {
    const ss = postShowState(post);
    const article = document.createElement('article');
    article.className = 'ts-feed-item';
    const postPath = `/post/view/${post.tid}`;
    article.addEventListener('click', (e) => {
        if (e.target.closest('a, button, input, textarea, select, label')) return;
        navigateFn(postPath);
    });

    const avatar = document.createElement('img');
    avatar.className = 'ts-feed-avatar';
    avatar.src = `/user/avatar?username=${encodeURIComponent(post.username)}`;
    avatar.alt = '';

    const body = document.createElement('div');
    body.className = 'ts-feed-body';

    const head = document.createElement('div');
    head.className = 'ts-feed-head';

    const nameLink = document.createElement('a');
    nameLink.className = 'ts-feed-name';
    nameLink.href = `/user/detail/${encodeURIComponent(post.username)}`;
    nameLink.textContent = post.nickname || post.username;
    nameLink.addEventListener('click', (e) => {
        if (e.metaKey || e.ctrlKey) return;
        e.preventDefault();
        navigateFn(`/user/detail/${encodeURIComponent(post.username)}`);
    });

    const handle = document.createElement('span');
    handle.className = 'ts-feed-handle';
    handle.textContent = `@${post.username}`;

    const time = document.createElement('time');
    time.className = 'ts-feed-time';
    time.dateTime = String(post.timestamp ?? '');
    time.textContent = fmtRelativeTime(post.timestamp);
    time.title = fmtTime(post.timestamp);

    head.append(nameLink, handle, document.createTextNode(' · '), time);

    if (ss !== 0) {
        const badge = document.createElement('span');
        badge.className = ss === 1 ? 'ts-badge ts-badge--paid' : 'ts-badge ts-badge--login';
        badge.textContent = ss === 1 ? t('paidBadge') : t('loginBadge');
        head.appendChild(badge);
    }

    const titleEl = document.createElement('h2');
    titleEl.className = 'ts-feed-title';
    const ta = document.createElement('a');
    ta.href = `/post/view/${post.tid}`;
    ta.textContent = post.title;
    ta.addEventListener('click', (ev) => {
        ev.preventDefault();
        navigateFn(`/post/view/${post.tid}`);
    });
    titleEl.appendChild(ta);
    body.append(head, titleEl);

    const contentWrap = document.createElement('div');
    contentWrap.className = 'ts-feed-content';
    if (ss === 0 && post.content && !post.content.startsWith('[')) {
        const {textMd, images, overflowImageCount} = prepareFeedMarkdownPreview(post.content, {
            maxTextLen: 680,
            maxImages: 9,
        });
        if (textMd.trim()) {
            const textWrap = document.createElement('div');
            textWrap.className = 'ts-feed-text';
            textWrap.appendChild(renderMarkdown(textMd));
            contentWrap.appendChild(textWrap);
        }
        if (images.length > 0) {
            const grid = document.createElement('div');
            grid.className = `ts-feed-thumb-grid ts-feed-thumb-grid--count-${images.length}`;
            grid.setAttribute('role', 'presentation');
            images.forEach((src, idx) => {
                const cell = document.createElement('div');
                cell.className = 'ts-feed-thumb-cell';
                const img = document.createElement('img');
                img.className = 'ts-feed-thumb-img';
                img.src = src;
                img.alt = '';
                img.loading = 'lazy';
                img.decoding = 'async';
                img.referrerPolicy = 'no-referrer';
                cell.appendChild(img);
                if (idx === images.length - 1 && overflowImageCount > 0) {
                    cell.classList.add('ts-feed-thumb-cell--more');
                    const more = document.createElement('span');
                    more.className = 'ts-feed-thumb-more';
                    more.textContent = `+${overflowImageCount}`;
                    cell.appendChild(more);
                }
                grid.appendChild(cell);
            });
            contentWrap.appendChild(grid);
        }
        if (!textMd.trim() && images.length === 0) {
            const fallback = document.createElement('div');
            fallback.className = 'ts-feed-text';
            const plain = post.content.slice(0, 400) + (post.content.length > 400 ? '…' : '');
            fallback.textContent = plain;
            contentWrap.appendChild(fallback);
        }
    } else if (ss !== 0) {
        contentWrap.appendChild(
            renderVisibilityGate({showState: ss, tid: post.tid, coin: post.coin}, navigateFn),
        );
    } else {
        contentWrap.classList.add('ts-md');
        contentWrap.textContent = post.content || '';
    }
    body.appendChild(contentWrap);

    article.append(avatar, body);
    return article;
}

function buildPostDetailAuthor(post, showState, navigateFn) {
    const row = document.createElement('div');
    row.className = 'ts-detail-author';

    const avatar = document.createElement('img');
    avatar.className = 'ts-avatar';
    avatar.src = `/user/avatar?username=${encodeURIComponent(post.username)}`;
    avatar.alt = '';

    const info = document.createElement('div');
    info.className = 'ts-detail-author-info';

    const head = document.createElement('div');
    head.className = 'ts-feed-head';

    const nameLink = document.createElement('a');
    nameLink.className = 'ts-feed-name';
    nameLink.href = `/user/detail/${encodeURIComponent(post.username)}`;
    nameLink.textContent = post.nickname || post.username;
    nameLink.addEventListener('click', (e) => {
        if (e.metaKey || e.ctrlKey) return;
        e.preventDefault();
        navigateFn(`/user/detail/${encodeURIComponent(post.username)}`);
    });

    const handle = document.createElement('span');
    handle.className = 'ts-feed-handle';
    handle.textContent = `@${post.username}`;

    const time = document.createElement('time');
    time.className = 'ts-feed-time';
    time.dateTime = String(post.timestamp ?? '');
    time.textContent = fmtRelativeTime(post.timestamp);
    time.title = fmtTime(post.timestamp);

    head.append(nameLink, handle, document.createTextNode(' · '), time);

    if (showState !== 0) {
        const badge = document.createElement('span');
        badge.className =
            showState === 1 ? 'ts-badge ts-badge--paid' : 'ts-badge ts-badge--login';
        badge.textContent = showState === 1 ? t('paidBadge') : t('loginBadge');
        head.appendChild(badge);
    }

    info.appendChild(head);
    row.append(avatar, info);
    return row;
}

async function renderBuy(tidStr) {
    const tid = String(tidStr);
    const wrap = document.createElement('div');
    wrap.className = 'ts-main-stack ts-card';
    const loading = document.createElement('p');
    loading.className = 'ts-meta';
    loading.style.marginBottom = '0';
    loading.textContent = t('loading');
    wrap.appendChild(loading);
    mountShell(wrap, {headerTitle: t('pay'), headerActions: null});

    const j = await ajaxGet(`/post/pay_preview/${tid}`);
    wrap.innerHTML = '';

    if (j.ret === 2002) {
        const h = document.createElement('h1');
        h.textContent = t('needLogin');
        const p = document.createElement('p');
        p.className = 'ts-gate-desc';
        p.textContent = t('payLoginHint');
        const login = navLink(buildLoginUrl(`/post/view/${tid}`), t('goLogin'));
        login.classList.add('ts-btn', 'primary');
        wrap.append(h, p, login);
        return;
    }

    if (j.ret !== 0) {
        const err = document.createElement('div');
        err.className = 'ts-error';
        err.textContent = j.tp_error_msg || j.status || t('loadFailed');
        wrap.appendChild(err);
        return;
    }

    if (j.unlocked) {
        navigate(`/post/view/${tid}`);
        return;
    }

    if (j.need_wallet) {
        const h = document.createElement('h1');
        h.textContent = t('walletHint');
        const p = document.createElement('p');
        p.className = 'ts-gate-desc';
        p.textContent = t('walletDesc');
        const row = document.createElement('div');
        row.className = 'ts-wallet-gate-actions';
        const openPage = document.createElement('a');
        openPage.href = '/pay/wallet';
        openPage.className = 'ts-btn primary';
        openPage.textContent = t('walletMenu');
        openPage.addEventListener('click', (e) => {
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
            e.preventDefault();
            navigate('/pay/wallet');
        });
        const go = document.createElement('a');
        go.href = '/pay/start';
        go.className = 'ts-btn ts-btn--ghost';
        go.textContent = t('goWallet');
        go.addEventListener('click', (e) => {
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
            e.preventDefault();
            navigate('/pay/start');
        });
        row.append(openPage, go);
        wrap.append(h, p, row);
        return;
    }

    const h = document.createElement('h1');
    h.textContent = t('payUnlockTitle');
    const sub = document.createElement('p');
    sub.className = 'ts-meta';
    sub.style.marginBottom = '0.35rem';
    sub.textContent = j.title || '';

    const priceRow = document.createElement('p');
    priceRow.className = 'ts-gate-desc';
    const ps = document.createElement('strong');
    ps.textContent = String(j.price ?? '');
    const bs = document.createElement('strong');
    bs.textContent = String(j.balance ?? '');
    priceRow.append(t('payPrice'), ps, '　', t('payBalance'), bs);

    const passField = document.createElement('div');
    passField.className = 'ts-field';
    const passLab = document.createElement('label');
    passLab.htmlFor = 'ts-pay-commit-pass';
    passLab.textContent = t('payPassLabel');
    const passInput = document.createElement('input');
    passInput.id = 'ts-pay-commit-pass';
    passInput.type = 'password';
    passInput.name = 'pass';
    passInput.required = true;
    passInput.maxLength = 6;
    passInput.autocomplete = 'current-password';
    passInput.inputMode = 'numeric';
    passInput.className = 'ts-pay-start-input';
    passField.append(passLab, passInput);

    const err = document.createElement('div');
    err.className = 'ts-error';

    const btn = document.createElement('button');
    btn.type = 'button';
    btn.className = 'ts-btn primary';
    btn.textContent = t('payConfirm');

    btn.addEventListener('click', async () => {
        err.textContent = '';
        const pass = passInput.value.trim();
        if (!pass) {
            err.textContent = t('payPassRequired');
            return;
        }
        const r = await ajaxPost(`/post/pay_commit/${tid}`, {csrf_token: csrfToken, pass});
        if (r.ret !== 0) {
            err.textContent = r.tp_error_msg || r.status || t('payFail');
            return;
        }
        await refreshBootstrap();
        navigate(`/post/view/${tid}`);
    });

    wrap.append(h, sub, priceRow, passField, err, btn);
}

async function renderPayStart() {
    const wrap = document.createElement('div');
    wrap.className = 'ts-main-stack ts-stack-gap';

    if (!bootstrap.user) {
        window.location.href = buildLoginUrl('/pay/start');
        return;
    }

    const st = await ajaxGet('/pay/json_start_state');
    if (st.ret === 2002) {
        window.location.href = buildLoginUrl('/pay/start');
        return;
    }
    if (st.ret !== 0) {
        mountShell(wrap, {headerTitle: t('walletSetupTitle'), headerActions: buildHomeHeaderAction()});
        const errBox = document.createElement('div');
        errBox.className = 'ts-card ts-error';
        errBox.textContent = st.tp_error_msg || st.status || t('loadFailed');
        wrap.appendChild(errBox);
        return;
    }
    if (!st.need_setup && !st.need_pay_pass) {
        navigate('/pay/wallet');
        return;
    }

    const onlyPayPass = Boolean(st.need_pay_pass && !st.need_setup);
    mountShell(wrap, {
        headerTitle: onlyPayPass ? t('walletPayPassOnlyTitle') : t('walletSetupTitle'),
        headerActions: buildHomeHeaderAction(),
    });

    const card = document.createElement('div');
    card.className = 'ts-card ts-pay-start-card';

    const intro = document.createElement('p');
    intro.className = 'ts-text-muted';
    intro.style.marginTop = '0';
    intro.textContent = onlyPayPass ? t('walletPayPassOnlyDesc') : t('walletSetupDesc');

    const form = document.createElement('form');
    form.className = 'ts-pay-start-form';

    const err = document.createElement('div');
    err.className = 'ts-error';

    const field = document.createElement('div');
    field.className = 'ts-field';
    const lab = document.createElement('label');
    lab.htmlFor = 'ts-pay-start-pass';
    lab.textContent = t('walletSetupPassLabel');
    const input = document.createElement('input');
    input.id = 'ts-pay-start-pass';
    input.type = 'password';
    input.name = 'pass';
    input.required = true;
    input.maxLength = 6;
    input.autocomplete = 'new-password';
    input.inputMode = 'numeric';
    input.className = 'ts-pay-start-input';
    field.append(lab, input);

    const btn = document.createElement('button');
    btn.type = 'submit';
    btn.className = 'ts-btn primary';
    btn.textContent = onlyPayPass ? t('walletPayPassOnlySubmit') : t('walletSetupSubmit');

    form.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        err.textContent = '';
        const pass = input.value.trim();
        if (!pass) {
            err.textContent = t('walletSetupPassRequired');
            return;
        }
        const r = await ajaxPost('/pay/json_start_post', {csrf_token: csrfToken, pass});
        if (r.ret !== 0) {
            err.textContent = r.tp_error_msg || r.status || t('actionFailed');
            return;
        }
        await refreshBootstrap();
        navigate('/pay/wallet');
    });

    form.append(err, field, btn);
    card.append(intro, form);
    wrap.appendChild(card);
}

async function renderWallet() {
    const wrap = document.createElement('div');
    wrap.className = 'ts-main-stack ts-stack-gap';
    mountShell(wrap, {headerTitle: t('walletPageTitle'), headerActions: buildHomeHeaderAction()});

    if (!bootstrap.user) {
        window.location.href = buildLoginUrl('/pay/wallet');
        return;
    }

    async function paint() {
        wrap.innerHTML = '';
        const j = await ajaxGet('/pay/wallet');
        if (j.ret === 2002) {
            window.location.href = buildLoginUrl('/pay/wallet');
            return;
        }
        if (j.ret !== 0) {
            const err = document.createElement('div');
            err.className = 'ts-card ts-error';
            err.textContent = j.tp_error_msg || j.status || t('walletLoadFailed');
            wrap.appendChild(err);
            return;
        }

        const card = document.createElement('section');
        card.className = 'ts-wallet-card ts-surface-soft';

        if (j.wallet_active) {
            if (j.has_pay_pass === false) {
                const warn = document.createElement('div');
                warn.className = 'ts-wallet-paypass-banner';
                const wt = document.createElement('p');
                wt.className = 'ts-text-muted';
                wt.style.marginTop = '0';
                wt.textContent = t('walletMissingPayPassHint');
                const wbtn = document.createElement('a');
                wbtn.href = '/pay/start';
                wbtn.className = 'ts-btn primary';
                wbtn.style.marginTop = '0.65rem';
                wbtn.style.display = 'inline-flex';
                wbtn.textContent = t('walletSetPayPassAction');
                wbtn.addEventListener('click', (e) => {
                    if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
                    e.preventDefault();
                    navigate('/pay/start');
                });
                warn.append(wt, wbtn);
                card.appendChild(warn);
            }
            const bal = document.createElement('div');
            bal.className = 'ts-wallet-balance';
            bal.textContent = formatWalletCredit(j.credit);
            const lab = document.createElement('div');
            lab.className = 'ts-meta ts-wallet-balance-label';
            lab.textContent = t('walletBalanceLabel');
            const note = document.createElement('p');
            note.className = 'ts-meta';
            note.style.marginTop = '0.65rem';
            note.textContent = t('walletPayPasswordNote');
            card.append(bal, lab, note);
        } else {
            const ih = document.createElement('h2');
            ih.className = 'ts-wallet-inactive-title';
            ih.textContent = t('walletHint');
            const ip = document.createElement('p');
            ip.className = 'ts-text-muted';
            ip.style.marginTop = '0.35rem';
            ip.textContent = t('walletInactiveHint');
            const leg = document.createElement('a');
            leg.href = '/pay/start';
            leg.className = 'ts-btn primary';
            leg.style.marginTop = '0.85rem';
            leg.style.display = 'inline-flex';
            leg.textContent = t('walletSetupTitle');
            leg.addEventListener('click', (e) => {
                if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
                e.preventDefault();
                navigate('/pay/start');
            });
            card.append(ih, ip, leg);
        }

        const actions = document.createElement('div');
        actions.className = 'ts-wallet-actions';
        const refresh = document.createElement('button');
        refresh.type = 'button';
        refresh.className = 'ts-btn ts-btn--ghost';
        refresh.textContent = t('walletRefresh');
        refresh.addEventListener('click', async () => {
            await refreshBootstrap();
            await paint();
        });
        actions.appendChild(refresh);

        wrap.append(card, actions);
    }

    await paint();
}

/**
 * @param {boolean} compact 首页顶部精简发帖
 * @param {(tid: string | number) => void} onPosted
 * @param {string} [streamKey] 区分多处精简表单时的 input id（如 feed / modal）
 */
function buildComposeForm(compact, onPosted, streamKey) {
    const suf =
        streamKey !== undefined && streamKey !== null && String(streamKey) !== ''
            ? String(streamKey)
            : compact
                ? 'h'
                : 'p';
    const form = document.createElement('form');
    form.className = compact ? 'ts-card ts-compose-card ts-compose-card--compact' : 'ts-card ts-compose-card';

    if (!compact) {
        const h = document.createElement('h1');
        h.className = 'ts-compose-title';
        h.textContent = t('composePublish');
        form.appendChild(h);
    }

    const stream = document.createElement('div');
    stream.className = 'ts-compose-stream';

    if (bootstrap.user) {
        const av = document.createElement('img');
        av.className = 'ts-compose-avatar';
        av.src = `/user/avatar?username=${encodeURIComponent(bootstrap.user.username)}`;
        av.alt = '';
        stream.appendChild(av);
    }

    const mainCol = document.createElement('div');
    mainCol.className = 'ts-compose-main';

    const titleWrap = document.createElement('div');
    titleWrap.className = 'ts-compose-field ts-compose-field--title';
    const ti = document.createElement('input');
    ti.className = 'ts-compose-title-input';
    ti.id = `ts-co-title-${suf}`;
    ti.name = 'title';
    ti.required = true;
    ti.setAttribute('aria-label', t('composeTitleLabel'));
    ti.placeholder = t('composeTitleLabel');
    ti.autocomplete = 'off';
    titleWrap.appendChild(ti);

    const bodyWrap = document.createElement('div');
    bodyWrap.className = 'ts-compose-field ts-compose-field--body';
    const ta = document.createElement('textarea');
    ta.className = 'ts-compose-body-input';
    ta.id = `ts-co-content-${suf}`;
    ta.name = 'content';
    ta.rows = compact ? 3 : 12;
    ta.required = true;
    ta.setAttribute('aria-label', t('composeBodyAria'));
    ta.placeholder = compact ? t('composeBodyShort') : t('composeBodyLong');
    bodyWrap.appendChild(ta);

    const visInput = document.createElement('input');
    visInput.type = 'hidden';
    visInput.name = 'visibility';
    visInput.value = 'public';

    const priceInp = document.createElement('input');
    priceInp.type = 'number';
    priceInp.name = 'price';
    priceInp.id = `ts-co-price-${suf}`;
    priceInp.className = 'ts-compose-price-input';
    priceInp.min = '0.01';
    priceInp.step = 'any';
    priceInp.placeholder = t('priceAmount');
    priceInp.setAttribute('aria-label', t('priceAmount'));

    installComposePopoverCloser();

    /** @type {'public' | 'login' | 'paid'} */
    let visMode = 'public';

    const modeLabels = [
        ['public', t('visPublic')],
        ['login', t('visLogin')],
        ['paid', t('visPaid')],
    ];

    const details = document.createElement('details');
    details.className = 'ts-compose-popover';

    const summary = document.createElement('summary');
    summary.className = 'ts-compose-popover-trigger';
    summary.setAttribute('aria-label', t('composeOptionsAria'));
    summary.appendChild(createIcon('sliders', compact ? 18 : 20));
    const trigText = document.createElement('span');
    trigText.className = 'ts-compose-popover-trigger-text';
    summary.appendChild(trigText);

    const panel = document.createElement('div');
    panel.className = 'ts-compose-popover-panel';

    const heading = document.createElement('div');
    heading.className = 'ts-compose-popover-heading';
    heading.textContent = t('composeVisLabel');

    const chipsWrap = document.createElement('div');
    chipsWrap.className = 'ts-compose-vis-chips';
    chipsWrap.setAttribute('role', 'radiogroup');
    chipsWrap.setAttribute('aria-label', t('composeVisLabel'));

    modeLabels.forEach(([mode, label]) => {
        const btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ts-compose-vis-chip';
        btn.dataset.mode = mode;
        btn.textContent = label;
        btn.setAttribute('role', 'radio');
        btn.setAttribute('aria-checked', 'false');
        btn.addEventListener('click', () => {
            visMode = /** @type {'public' | 'login' | 'paid'} */ (mode);
            syncVisibility();
        });
        chipsWrap.appendChild(btn);
    });

    const priceRow = document.createElement('div');
    priceRow.className = 'ts-compose-popover-price';
    priceRow.hidden = true;
    const priceLbl = document.createElement('span');
    priceLbl.className = 'ts-compose-popover-price-label';
    priceLbl.textContent = t('priceUnit');
    priceRow.append(priceLbl, priceInp);

    panel.append(heading, chipsWrap, priceRow);
    details.append(summary, panel);

    details.addEventListener('toggle', () => {
        if (!details.open) return;
        document.querySelectorAll('details.ts-compose-popover[open]').forEach((d) => {
            if (d !== details) d.removeAttribute('open');
        });
    });

    function syncVisibility() {
        if (visMode === 'paid') {
            visInput.value = 'paid';
            priceRow.hidden = false;
            priceInp.required = true;
        } else if (visMode === 'login') {
            visInput.value = 'login';
            priceRow.hidden = true;
            priceInp.required = false;
            priceInp.value = '';
        } else {
            visInput.value = 'public';
            priceRow.hidden = true;
            priceInp.required = false;
            priceInp.value = '';
        }
        const curLabel = modeLabels.find((m) => m[0] === visMode);
        trigText.textContent = curLabel ? curLabel[1] : '';
        chipsWrap.querySelectorAll('.ts-compose-vis-chip').forEach((btn) => {
            const active = btn.dataset.mode === visMode;
            btn.classList.toggle('ts-compose-vis-chip--active', active);
            btn.setAttribute('aria-checked', active ? 'true' : 'false');
        });
    }

    const visCluster = document.createElement('div');
    visCluster.className = 'ts-compose-vis-cluster';
    visCluster.appendChild(details);

    const footer = document.createElement('div');
    footer.className = 'ts-compose-footer';

    const submit = document.createElement('button');
    submit.type = 'submit';
    submit.className = 'ts-btn primary ts-compose-submit';
    submit.textContent = compact ? t('compose') : t('composePublish');

    footer.append(visCluster, submit);

    const err = document.createElement('div');
    err.className = 'ts-error';

    mainCol.append(titleWrap, bodyWrap, visInput, footer, err);
    stream.appendChild(mainCol);
    form.appendChild(stream);

    form.addEventListener('submit', async (e) => {
        e.preventDefault();
        syncVisibility();
        err.textContent = '';
        const fd = new FormData(form);
        const r = await ajaxPost('/post/push', {
            csrf_token: csrfToken,
            title: fd.get('title'),
            content: fd.get('content'),
            visibility: fd.get('visibility'),
            price: fd.get('price') || '',
        });
        if (r.ret !== 0) {
            err.textContent = r.tp_error_msg || r.status || t('composeFail');
            return;
        }
        form.reset();
        visMode = 'public';
        details.removeAttribute('open');
        syncVisibility();
        onPosted(r.tid);
    });

    syncVisibility();

    return form;
}

async function renderHome() {
    const frag = document.createDocumentFragment();
    const wrap = document.createElement('div');
    wrap.className = 'ts-main-stack ts-stack-gap';

    if (bootstrap.user) {
        wrap.appendChild(
            buildComposeForm(true, (tid) => {
                navigate(`/post/view/${tid}`);
            }, 'feed'),
        );
    }

    const feed = document.createElement('div');
    feed.className = 'ts-feed';

    const footer = document.createElement('div');
    footer.className = 'ts-feed-footer';

    const statusLine = document.createElement('div');
    statusLine.className = 'ts-feed-status ts-meta';

    const retryBtn = document.createElement('button');
    retryBtn.type = 'button';
    retryBtn.className = 'ts-btn ts-feed-footer-retry';
    retryBtn.hidden = true;
    retryBtn.textContent = t('retry');

    const sentinel = document.createElement('div');
    sentinel.className = 'ts-feed-sentinel';

    footer.append(statusLine, retryBtn, sentinel);
    wrap.append(feed, footer);
    frag.appendChild(wrap);

    let endPage = 1;
    let lastLoadedPage = 0;
    let loading = false;
    /** @type {number | null} */
    let retryPage = null;
    /** @type {IntersectionObserver | null} */
    let observer = null;

    async function fetchPage(nextPage) {
        if (loading) return;
        loading = true;
        retryPage = null;
        retryBtn.hidden = true;
        statusLine.textContent = t('loading');
        const j = await ajaxGet(`/post/feed/${nextPage}`);
        loading = false;

        if (j.ret !== 0) {
            retryPage = nextPage;
            statusLine.textContent = j.tp_error_msg || j.status || t('loadFailed');
            retryBtn.hidden = false;
            if (nextPage === 1) {
                feed.innerHTML = `<div class="ts-card ts-error">${escapeHtml(j.tp_error_msg || j.status || t('loadFailed'))}</div>`;
            }
            return;
        }

        if (nextPage === 1) {
            feed.innerHTML = '';
        }

        const rawEnd = Number(j.end_page);
        endPage = Number.isFinite(rawEnd) ? Math.max(0, rawEnd) : 1;
        lastLoadedPage = nextPage;

        const posts = j.posts || [];
        if (nextPage === 1 && posts.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'ts-card ts-empty-hint';
            empty.textContent = bootstrap.user ? t('noFeedPostsLogin') : t('noFeedPosts');
            feed.appendChild(empty);
        } else {
            posts.forEach((post) => {
                feed.appendChild(buildFeedPostCard(post, navigate));
            });
        }

        const pagingDone = lastLoadedPage >= endPage;
        retryBtn.hidden = true;
        const showEndHint =
            pagingDone && endPage > 0 && (nextPage > 1 || posts.length > 0);
        statusLine.textContent = !pagingDone ? '' : showEndHint ? t('loadedAll') : '';

        if (pagingDone && observer) {
            observer.disconnect();
            observer = null;
        }
    }

    retryBtn.addEventListener('click', () => {
        if (retryPage != null) void fetchPage(retryPage);
    });

    mountShell(frag, {});

    observer = new IntersectionObserver(
        (entries) => {
            if (!entries.some((e) => e.isIntersecting)) return;
            if (loading || retryPage != null) return;
            if (lastLoadedPage >= endPage) return;
            void fetchPage(lastLoadedPage + 1);
        },
        {root: null, rootMargin: '180px', threshold: 0},
    );
    observer.observe(sentinel);

    await fetchPage(1);
}

async function renderPost(tid) {
    const j = await ajaxGet(`/post/thread/${tid}`);
    const frag = document.createDocumentFragment();
    if (j.ret !== 0) {
        const stack = document.createElement('div');
        stack.className = 'ts-main-stack';
        const d = document.createElement('div');
        d.className = 'ts-card ts-error';
        d.textContent = j.tp_error_msg || t('postNotFound');
        stack.appendChild(d);
        mountShell(stack, {headerTitle: t('post')});
        return;
    }
    const {post, comments, show_state: ssTop} = j;
    const rawSs = post.show_state;
    const ss = rawSs === undefined || rawSs === null ? ssTop : Number(rawSs);
    const thread = document.createElement('div');
    thread.className = 'ts-thread ts-thread--sheet';

    const card = document.createElement('article');
    card.className = 'ts-card ts-post-detail-card';
    card.appendChild(buildPostDetailAuthor(post, ss, navigate));

    const h = document.createElement('h1');
    h.className = 'ts-post-detail-title';
    h.textContent = post.title;
    card.appendChild(h);

    const body = document.createElement('div');
    if (ss === 0 && post.content && !post.content.startsWith('[')) {
        body.className = 'ts-md';
        body.appendChild(renderMarkdown(post.content));
    } else if (ss !== 0) {
        const coinVal = j.coin != null ? j.coin : post.coin;
        body.appendChild(renderVisibilityGate({showState: ss, tid: post.tid, coin: coinVal}, navigate));
    } else {
        body.className = 'ts-md';
        body.textContent = post.content || '';
    }
    card.appendChild(body);

    if (bootstrap.user && ss === 0) {
        const form = document.createElement('form');
        form.className = 'ts-thread-compose';
        form.innerHTML = `<div class="ts-field"><label>${escapeHtml(t('commentLabel'))}</label><textarea name="content" rows="3" required></textarea></div>`;
        const err = document.createElement('div');
        err.className = 'ts-error';
        const btn = document.createElement('button');
        btn.className = 'ts-btn primary';
        btn.type = 'submit';
        btn.textContent = t('sendComment');
        form.appendChild(err);
        form.appendChild(btn);
        form.addEventListener('submit', async (ev) => {
            ev.preventDefault();
            err.textContent = '';
            const fd = new FormData(form);
            const content = fd.get('content');
            const r = await ajaxPost(`/post/reply/${tid}`, {
                csrf_token: csrfToken,
                content,
            });
            if (r.ret !== 0) {
                err.textContent = r.tp_error_msg || r.status || '失败';
                return;
            }
            await refreshBootstrap();
            navigate(`/post/view/${tid}`);
        });
        thread.appendChild(card);
        thread.appendChild(form);
    } else {
        thread.appendChild(card);
    }

    const list = document.createElement('div');
    list.className = 'ts-comments-panel';
    const ch = document.createElement('h2');
    ch.className = 'ts-comments-heading';
    ch.textContent = t('comments');
    list.appendChild(ch);
    if (ss !== 0) {
        const hint = document.createElement('p');
        hint.className = 'ts-gate-desc';
        hint.textContent = t('gateComments');
        list.appendChild(hint);
    } else {
        (comments || []).forEach((c) => {
            const row = document.createElement('div');
            row.className = 'ts-row';
            row.style.marginBottom = '0.75rem';
            const side = document.createElement('div');
            side.innerHTML = `<img class="ts-avatar" src="/user/avatar?username=${encodeURIComponent(c.username)}" alt="">`;
            const text = document.createElement('div');
            text.innerHTML = `<div class="ts-meta">${escapeHtml(c.nickname || c.username)} · ${fmtTime(c.timestamp)}</div><div>${escapeHtml(c.content)}</div>`;
            row.appendChild(side);
            row.appendChild(text);
            list.appendChild(row);
        });
    }
    thread.appendChild(list);
    frag.appendChild(thread);

    mountShell(frag, {headerTitle: truncateHeaderTitle(post.title, 22)});
}

function renderCreate() {
    if (!bootstrap.user) {
        navigate(buildLoginUrl(`${window.location.pathname}${window.location.search}`));
        return;
    }
    const wrap = document.createElement('div');
    wrap.className = 'ts-thread';
    wrap.appendChild(buildComposeForm(false, (tid) => navigate(`/post/view/${tid}`)));
    mountShell(wrap, {headerTitle: t('compose'), headerActions: buildHomeHeaderAction()});
}

async function renderSearch() {
    const q = new URLSearchParams(window.location.search).get('word') || '';
    const wrap = document.createElement('div');
    wrap.className = 'ts-main-stack ts-stack-gap';
    const form = document.createElement('form');
    form.className = 'ts-card';
    form.innerHTML = `<div class="ts-field"><label>${escapeHtml(t('keyword'))}</label><input name="word" value="${escapeHtml(q)}"></div><button class="ts-btn primary" type="submit">${escapeHtml(t('submitSearch'))}</button>`;
    form.addEventListener('submit', (ev) => {
        ev.preventDefault();
        const fd = new FormData(form);
        const word = fd.get('word') || '';
        navigate(`/post/search?word=${encodeURIComponent(word)}`);
    });
    wrap.appendChild(form);

    if (!q) {
        mountShell(wrap, {headerTitle: t('search')});
        return;
    }

    const feed = document.createElement('div');
    feed.className = 'ts-feed';

    const footer = document.createElement('div');
    footer.className = 'ts-feed-footer';

    const statusLine = document.createElement('div');
    statusLine.className = 'ts-feed-status ts-meta';

    const retryBtn = document.createElement('button');
    retryBtn.type = 'button';
    retryBtn.className = 'ts-btn ts-feed-footer-retry';
    retryBtn.hidden = true;
    retryBtn.textContent = t('retry');

    const sentinel = document.createElement('div');
    sentinel.className = 'ts-feed-sentinel';

    footer.append(statusLine, retryBtn, sentinel);
    wrap.append(feed, footer);

    let endPage = 1;
    let lastLoadedPage = 0;
    let loading = false;
    /** @type {number | null} */
    let retryPage = null;
    /** @type {IntersectionObserver | null} */
    let observer = null;

    async function fetchPage(nextPage) {
        if (loading) return;
        loading = true;
        retryPage = null;
        retryBtn.hidden = true;
        statusLine.textContent = t('loading');
        const j = await ajaxGet(`/post/find?word=${encodeURIComponent(q)}&page=${nextPage}`);
        loading = false;

        if (j.ret !== 0) {
            retryPage = nextPage;
            statusLine.textContent = j.tp_error_msg || j.status || t('loadFailed');
            retryBtn.hidden = false;
            if (nextPage === 1) {
                feed.innerHTML = `<div class="ts-card ts-error">${escapeHtml(j.tp_error_msg || j.status || t('loadFailed'))}</div>`;
            }
            return;
        }

        if (nextPage === 1) {
            feed.innerHTML = '';
        }

        const rawEnd = Number(j.end_page);
        endPage = Number.isFinite(rawEnd) ? Math.max(0, rawEnd) : 1;
        lastLoadedPage = nextPage;

        const posts = j.posts || [];
        if (nextPage === 1 && posts.length === 0) {
            const empty = document.createElement('div');
            empty.className = 'ts-card ts-empty-hint';
            empty.textContent = t('searchNoResults');
            feed.appendChild(empty);
        } else {
            posts.forEach((post) => {
                feed.appendChild(buildFeedPostCard(post, navigate));
            });
        }

        const pagingDone = lastLoadedPage >= endPage;
        retryBtn.hidden = true;
        const showEndHint =
            pagingDone && endPage > 0 && (nextPage > 1 || posts.length > 0);
        statusLine.textContent = !pagingDone ? '' : showEndHint ? t('searchLoadedAll') : '';

        if (pagingDone && observer) {
            observer.disconnect();
            observer = null;
        }
    }

    retryBtn.addEventListener('click', () => {
        if (retryPage != null) void fetchPage(retryPage);
    });

    mountShell(wrap, {headerTitle: t('search')});

    observer = new IntersectionObserver(
        (entries) => {
            if (!entries.some((e) => e.isIntersecting)) return;
            if (loading || retryPage != null) return;
            if (lastLoadedPage >= endPage) return;
            void fetchPage(lastLoadedPage + 1);
        },
        {root: null, rootMargin: '180px', threshold: 0},
    );
    observer.observe(sentinel);

    await fetchPage(1);
}

function profileDetailPath(u) {
    return `/user/detail/${encodeURIComponent(u)}`;
}

async function renderUser(username, tab) {
    let path = '/user/panel';
    if (username) {
        path += `/${encodeURIComponent(username)}`;
        if (tab === 'post') {
            path += '/post';
        }
    } else if (tab === 'post') {
        path += '?tab=post';
    }
    const j = await ajaxGet(path);
    const stack = document.createElement('div');
    stack.className = 'ts-main-stack ts-stack-gap';
    if (j.ret !== 0) {
        const d = document.createElement('div');
        d.className = 'ts-card ts-error';
        d.textContent = j.tp_error_msg || t('loadFailed');
        stack.appendChild(d);
        mountShell(stack, {headerTitle: t('profile')});
        return;
    }
    const uname = j.user.username;
    const profileBase = profileDetailPath(uname);

    const card = document.createElement('div');
    card.className = 'ts-profile-card ts-surface-soft';
    const sig =
        j.user_info && typeof j.user_info.signature === 'string' && j.user_info.signature.trim()
            ? escapeHtml(j.user_info.signature.trim())
            : '';
    card.innerHTML = `
        <div class="ts-profile-head">
            <img class="ts-avatar ts-profile-avatar" src="/user/avatar?username=${encodeURIComponent(uname)}" alt="">
            <div class="ts-profile-head-text">
                <h1>${escapeHtml(j.user.nickname || uname)}</h1>
                <div class="ts-meta">@${escapeHtml(uname)}</div>
            </div>
        </div>`;
    stack.appendChild(card);

    const tabs = document.createElement('div');
    tabs.className = 'ts-profile-tabs';
    const addTab = (label, href, active) => {
        const a = document.createElement('a');
        a.href = href;
        a.className = active ? 'ts-profile-tab ts-profile-tab--active' : 'ts-profile-tab';
        a.textContent = label;
        a.addEventListener('click', (e) => {
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
            e.preventDefault();
            navigate(href);
        });
        tabs.appendChild(a);
    };
    addTab(t('bioTab'), profileBase, tab !== 'post');
    addTab(t('postsTab'), `${profileBase}/post`, tab === 'post');
    stack.appendChild(tabs);

    if (tab !== 'post') {
        const bio = document.createElement('div');
        bio.className = 'ts-profile-bio ts-surface-soft';
        bio.innerHTML = sig
            ? `<p class="ts-profile-bio-text">${sig}</p>`
            : `<p class="ts-meta">${escapeHtml(t('noSignature'))}</p>`;
        stack.appendChild(bio);
    } else if (!j.posts || !j.posts.length) {
        const empty = document.createElement('div');
        empty.className = 'ts-empty-hint ts-surface-soft';
        empty.textContent = t('noPosts');
        stack.appendChild(empty);
    } else {
        const feed = document.createElement('div');
        feed.className = 'ts-feed';
        j.posts.forEach((post) => {
            feed.appendChild(buildFeedPostCard(post, navigate));
        });
        stack.appendChild(feed);
    }
    mountShell(stack, {headerTitle: truncateHeaderTitle(j.user.nickname || uname, 20)});
}

async function renderFriend() {
    if (!bootstrap.user) {
        const stack = document.createElement('div');
        stack.className = 'ts-main-stack ts-stack-gap';
        const card = document.createElement('div');
        card.className = 'ts-friend-guest ts-surface-soft';
        const p1 = document.createElement('p');
        p1.className = 'ts-text-muted';
        p1.textContent = t('friendLoginHint');
        const p2 = document.createElement('p');
        const login = navLink(`/user/login?referer=${encodeURIComponent('/friend')}`, t('goLogin'));
        p2.appendChild(login);
        card.append(p1, p2);
        stack.appendChild(card);
        mountShell(stack, {headerTitle: t('friends')});
        return;
    }

    const j = await ajaxGet('/friend/json');
    const stack = document.createElement('div');
    stack.className = 'ts-main-stack ts-stack-gap';
    if (j.ret !== 0) {
        const d = document.createElement('div');
        d.className = 'ts-card ts-error';
        d.textContent = j.tp_error_msg || t('loadFailed');
        stack.appendChild(d);
        mountShell(stack, {headerTitle: t('friends')});
        return;
    }

    const friendPrimary = (f) => {
        const r = f.remark != null ? String(f.remark).trim() : '';
        if (r) return r;
        return f.nickname || f.username;
    };

    const head = document.createElement('section');
    head.className = 'ts-friend-head ts-surface-soft';
    const ht = document.createElement('h1');
    ht.className = 'ts-compose-title';
    ht.style.margin = '0 0 0.75rem';
    ht.textContent = t('addFriend');
    head.appendChild(ht);

    const searchWrap = document.createElement('div');
    searchWrap.className = 'ts-friend-search';
    const flab = document.createElement('label');
    flab.className = 'ts-friend-add-label';
    flab.htmlFor = 'ts-friend-search-q';
    flab.textContent = t('friendSearchLabel');
    const searchInp = document.createElement('input');
    searchInp.id = 'ts-friend-search-q';
    searchInp.type = 'search';
    searchInp.className = 'ts-friend-search-input';
    searchInp.placeholder = t('friendSearchPlaceholder');
    searchInp.autocomplete = 'off';
    const searchHint = document.createElement('div');
    searchHint.className = 'ts-meta ts-friend-search-hint';
    searchHint.textContent = t('friendSearchShort');
    const searchErr = document.createElement('div');
    searchErr.className = 'ts-error';
    const resultsEl = document.createElement('div');
    resultsEl.className = 'ts-friend-search-results';

    const runLookup = debounce(async () => {
        searchErr.textContent = '';
        const q = searchInp.value.trim();
        resultsEl.innerHTML = '';
        if (q.length < 2) {
            searchHint.hidden = false;
            return;
        }
        searchHint.hidden = true;
        const res = await ajaxGet(`/user/json_lookup?q=${encodeURIComponent(q)}`);
        if (res.ret !== 0) {
            searchErr.textContent = res.tp_error_msg || res.status || t('loadFailed');
            return;
        }
        const users = res.users || [];
        if (!users.length) {
            const empty = document.createElement('p');
            empty.className = 'ts-text-muted ts-friend-search-empty';
            empty.textContent = t('friendNoResults');
            resultsEl.appendChild(empty);
            return;
        }
        users.forEach((u) => {
            const row = document.createElement('div');
            row.className = 'ts-friend-search-row';
            const img = document.createElement('img');
            img.className = 'ts-friend-search-row__avatar';
            img.src = `/user/avatar?username=${encodeURIComponent(u.username)}`;
            img.alt = '';
            const mid = document.createElement('div');
            mid.className = 'ts-friend-search-row__text';
            const nm = document.createElement('div');
            nm.className = 'ts-friend-search-row__name';
            nm.textContent = u.nickname || u.username;
            const un = document.createElement('div');
            un.className = 'ts-meta';
            un.textContent = `@${u.username}`;
            mid.append(nm, un);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ts-btn primary';
            btn.textContent = t('sendRequest');
            const rowErr = document.createElement('span');
            rowErr.className = 'ts-error';
            btn.addEventListener('click', async () => {
                rowErr.textContent = '';
                const r = await jsonPost('/friend/json_add', {
                    csrf_token: csrfToken,
                    username: u.username,
                });
                if (r.ret !== 0) {
                    rowErr.textContent = r.tp_error_msg || r.status || t('actionFailed');
                    return;
                }
                navigate('/friend');
            });
            row.append(img, mid, btn, rowErr);
            resultsEl.appendChild(row);
        });
    }, 320);

    searchInp.addEventListener('input', () => {
        searchHint.hidden = searchInp.value.trim().length >= 2;
        runLookup();
    });

    searchWrap.append(flab, searchInp, searchHint, searchErr, resultsEl);
    head.appendChild(searchWrap);
    stack.appendChild(head);

    const pendIn = j.pending_in || [];
    if (pendIn.length) {
        const box = document.createElement('section');
        box.className = 'ts-friend-block ts-surface-soft';
        const ph = document.createElement('h2');
        ph.className = 'ts-friend-section-title';
        ph.textContent = t('pendingIn');
        box.appendChild(ph);
        pendIn.forEach((row) => {
            const rowEl = document.createElement('div');
            rowEl.className = 'ts-friend-pending-row';
            const avatar = document.createElement('img');
            avatar.className = 'ts-friend-row__avatar';
            avatar.src = `/user/avatar?username=${encodeURIComponent(row.username)}`;
            avatar.alt = '';
            const info = document.createElement('div');
            info.className = 'ts-friend-row__text';
            const line1 = document.createElement('div');
            line1.className = 'ts-friend-row__primary';
            line1.textContent = friendPrimary(row);
            const line2 = document.createElement('div');
            line2.className = 'ts-friend-row__meta ts-meta';
            line2.textContent = `@${row.username}`;
            info.append(line1, line2);
            const btn = document.createElement('button');
            btn.type = 'button';
            btn.className = 'ts-btn primary';
            btn.textContent = t('accept');
            const err = document.createElement('span');
            err.className = 'ts-error';
            btn.addEventListener('click', async () => {
                err.textContent = '';
                const r = await jsonPost('/friend/json_accept', {
                    csrf_token: csrfToken,
                    username: row.username,
                });
                if (r.ret !== 0) {
                    err.textContent = r.tp_error_msg || t('actionFailed');
                    return;
                }
                navigate('/friend');
            });
            rowEl.append(avatar, info, btn, err);
            box.appendChild(rowEl);
        });
        stack.appendChild(box);
    }

    const pendOut = j.pending_out || [];
    if (pendOut.length) {
        const box = document.createElement('section');
        box.className = 'ts-friend-block ts-surface-soft';
        const oh = document.createElement('h2');
        oh.className = 'ts-friend-section-title';
        oh.textContent = t('pendingOut');
        box.appendChild(oh);
        const pul = document.createElement('ul');
        pul.className = 'ts-friends-mini';
        pendOut.forEach((r) => {
            const li = document.createElement('li');
            li.className = 'ts-friend-row ts-friend-row--inline';
            const avatar = document.createElement('img');
            avatar.className = 'ts-friend-row__avatar';
            avatar.src = `/user/avatar?username=${encodeURIComponent(r.username)}`;
            avatar.alt = '';
            const info = document.createElement('div');
            info.className = 'ts-friend-row__text';
            const line1 = document.createElement('div');
            line1.className = 'ts-friend-row__primary';
            line1.textContent = friendPrimary(r);
            const line2 = document.createElement('div');
            line2.className = 'ts-friend-row__meta ts-meta';
            line2.textContent = `@${r.username}`;
            info.append(line1, line2);
            li.append(avatar, info);
            pul.appendChild(li);
        });
        box.appendChild(pul);
        stack.appendChild(box);
    }

    const friends = j.friends || [];
    const listCard = document.createElement('section');
    listCard.className = 'ts-friend-block ts-surface-soft';
    const mh = document.createElement('h2');
    mh.className = 'ts-friend-section-title';
    mh.textContent = t('myFriends');
    listCard.appendChild(mh);
    if (!friends.length) {
        const np = document.createElement('p');
        np.className = 'ts-text-muted';
        np.textContent = t('noFriendsYet');
        listCard.appendChild(np);
    } else {
        const ul = document.createElement('ul');
        ul.className = 'ts-friends-list';
        friends.forEach((f) => {
            const li = document.createElement('li');
            li.className = 'ts-friend-row';
            const a = document.createElement('a');
            a.className = 'ts-friend-row__link';
            a.href = `/user/detail/${encodeURIComponent(f.username)}`;
            const img = document.createElement('img');
            img.className = 'ts-friend-row__avatar';
            img.src = `/user/avatar?username=${encodeURIComponent(f.username)}`;
            img.alt = '';
            const info = document.createElement('div');
            info.className = 'ts-friend-row__text';
            const line1 = document.createElement('div');
            line1.className = 'ts-friend-row__primary';
            line1.textContent = friendPrimary(f);
            const line2 = document.createElement('div');
            line2.className = 'ts-friend-row__meta ts-meta';
            line2.textContent = `@${f.username}`;
            info.append(line1, line2);
            a.append(img, info);
            a.addEventListener('click', (e) => {
                if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
                e.preventDefault();
                navigate(`/user/detail/${encodeURIComponent(f.username)}`);
            });
            li.appendChild(a);
            ul.appendChild(li);
        });
        listCard.appendChild(ul);
    }
    stack.appendChild(listCard);

    mountShell(stack, {headerTitle: t('friends')});
}

async function renderNotify() {
    const j = await ajaxGet('/notify/view');
    const stack = document.createElement('div');
    stack.className = 'ts-main-stack ts-stack-gap';
    if (j.ret !== 0) {
        const d = document.createElement('div');
        d.className = 'ts-card ts-error';
        d.textContent = j.tp_error_msg || t('notifyNeedLogin');
        stack.appendChild(d);
        mountShell(stack, {headerTitle: t('notify')});
        return;
    }
    const box = document.createElement('div');
    box.className = 'ts-notify-page ts-surface-soft';
    const nh = document.createElement('h1');
    nh.textContent = t('notify');
    box.appendChild(nh);
    (j.notifications || []).forEach((n) => {
        const row = document.createElement('div');
        row.style.marginBottom = '0.6rem';
        row.textContent = JSON.stringify(n);
        box.appendChild(row);
    });
    stack.appendChild(box);
    mountShell(stack, {headerTitle: t('notify')});
}

function buildSettingsNav(section, navigateFn) {
    const nav = document.createElement('nav');
    nav.className = 'ts-settings-tabs';
    nav.setAttribute('aria-label', t('settings'));
    const mk = (href, label, active) => {
        const a = document.createElement('a');
        a.href = href;
        a.className = active ? 'ts-settings-tab ts-settings-tab--active' : 'ts-settings-tab';
        a.textContent = label;
        a.addEventListener('click', (e) => {
            if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
            e.preventDefault();
            navigateFn(href);
        });
        nav.appendChild(a);
    };
    mk('/setting', t('settingsOverview'), section === 'index');
    mk('/setting/avatar', t('settingsAvatar'), section === 'avatar');
    if ((bootstrap.oauth || []).length > 0) {
        mk('/setting/oauth', t('settingsOAuth'), section === 'oauth');
    }
    return nav;
}

const AVATAR_CROP_VIEW = 280;
const AVATAR_CROP_OUT = 512;

/**
 * 正方形裁剪预览 + 导出 512×512 JPEG；确认后关闭弹层，由外部再上传。
 * @param {File} file
 * @param {(blob: Blob) => void} onCropApply
 */
function openAvatarCropModal(file, onCropApply) {
    closeDrawer();
    closeComposeModal();

    const root = document.createElement('div');
    root.className = 'ts-modal-root ts-modal-root--centered';

    const backdrop = document.createElement('button');
    backdrop.type = 'button';
    backdrop.className = 'ts-modal-backdrop';
    backdrop.setAttribute('aria-label', t('closeDialog'));

    const dialog = document.createElement('div');
    dialog.className = 'ts-modal-dialog ts-avatar-crop-dialog';
    dialog.setAttribute('role', 'dialog');
    dialog.setAttribute('aria-modal', 'true');

    const header = document.createElement('div');
    header.className = 'ts-modal-header';
    const titleEl = document.createElement('h2');
    titleEl.className = 'ts-modal-title';
    titleEl.textContent = t('settingsCropTitle');
    const closeBtn = document.createElement('button');
    closeBtn.type = 'button';
    closeBtn.className = 'ts-modal-close ts-header-icon-btn ts-btn ts-btn--ghost';
    closeBtn.setAttribute('aria-label', t('closeDialog'));
    closeBtn.appendChild(createIcon('x', 22));
    header.append(titleEl, closeBtn);

    const body = document.createElement('div');
    body.className = 'ts-modal-body ts-avatar-crop-body';

    const hint = document.createElement('p');
    hint.className = 'ts-meta ts-avatar-crop-hint';
    hint.textContent = t('settingsCropHint');

    const viewport = document.createElement('div');
    viewport.className = 'ts-avatar-crop-viewport';
    const canvas = document.createElement('canvas');
    canvas.width = AVATAR_CROP_VIEW;
    canvas.height = AVATAR_CROP_VIEW;
    canvas.className = 'ts-avatar-crop-canvas';
    viewport.appendChild(canvas);

    const zoomRow = document.createElement('div');
    zoomRow.className = 'ts-avatar-crop-zoom';
    const zoomLab = document.createElement('label');
    zoomLab.className = 'ts-avatar-crop-zoom-label';
    zoomLab.textContent = t('settingsCropZoom');
    const zoomInp = document.createElement('input');
    zoomInp.type = 'range';
    zoomInp.min = '1';
    zoomInp.max = '3';
    zoomInp.step = '0.02';
    zoomInp.value = '1';
    zoomRow.append(zoomLab, zoomInp);

    body.append(hint, viewport, zoomRow);

    const foot = document.createElement('div');
    foot.className = 'ts-modal-footer ts-avatar-crop-footer';
    const cancelBtn = document.createElement('button');
    cancelBtn.type = 'button';
    cancelBtn.className = 'ts-btn ts-btn--ghost';
    cancelBtn.textContent = t('settingsCropCancel');
    const okBtn = document.createElement('button');
    okBtn.type = 'button';
    okBtn.className = 'ts-btn primary';
    okBtn.textContent = t('settingsCropApply');
    foot.append(cancelBtn, okBtn);

    dialog.append(header, body, foot);
    root.append(backdrop, dialog);
    document.body.appendChild(root);

    const prevOverflow = document.body.style.overflow;
    document.body.style.overflow = 'hidden';

    let zoom = 1;
    let panX = 0;
    let panY = 0;
    /** @type {HTMLImageElement | null} */
    let imgEl = null;

    function baseScale(nw, nh) {
        return Math.max(AVATAR_CROP_VIEW / nw, AVATAR_CROP_VIEW / nh);
    }

    function drawPreview() {
        if (!imgEl) return;
        const ctx = canvas.getContext('2d');
        if (!ctx) return;
        const nw = imgEl.naturalWidth;
        const nh = imgEl.naturalHeight;
        const s = baseScale(nw, nh) * zoom;
        ctx.save();
        ctx.clearRect(0, 0, AVATAR_CROP_VIEW, AVATAR_CROP_VIEW);
        ctx.beginPath();
        ctx.rect(0, 0, AVATAR_CROP_VIEW, AVATAR_CROP_VIEW);
        ctx.clip();
        ctx.translate(AVATAR_CROP_VIEW / 2 + panX, AVATAR_CROP_VIEW / 2 + panY);
        ctx.scale(s, s);
        ctx.drawImage(imgEl, -nw / 2, -nh / 2);
        ctx.restore();
    }

    function exportBlob() {
        if (!imgEl) return;
        const nw = imgEl.naturalWidth;
        const nh = imgEl.naturalHeight;
        const s = baseScale(nw, nh) * zoom;
        const scaleOut = AVATAR_CROP_OUT / AVATAR_CROP_VIEW;
        const out = document.createElement('canvas');
        out.width = AVATAR_CROP_OUT;
        out.height = AVATAR_CROP_OUT;
        const ctx = out.getContext('2d');
        if (!ctx) return;
        ctx.save();
        ctx.beginPath();
        ctx.rect(0, 0, AVATAR_CROP_OUT, AVATAR_CROP_OUT);
        ctx.clip();
        ctx.translate(AVATAR_CROP_OUT / 2 + panX * scaleOut, AVATAR_CROP_OUT / 2 + panY * scaleOut);
        ctx.scale(s * scaleOut, s * scaleOut);
        ctx.drawImage(imgEl, -nw / 2, -nh / 2);
        ctx.restore();
        out.toBlob(
            (blob) => {
                teardown();
                if (blob) onCropApply(blob);
            },
            'image/jpeg',
            0.92,
        );
    }

    const blobUrl = URL.createObjectURL(file);

    function onEscKey(e) {
        if (e.key === 'Escape') teardown();
    }

    function teardown() {
        document.removeEventListener('keydown', onEscKey);
        document.body.style.overflow = prevOverflow;
        URL.revokeObjectURL(blobUrl);
        root.remove();
    }

    const img = new Image();
    img.onload = () => {
        imgEl = img;
        drawPreview();
    };
    img.onerror = () => {
        teardown();
    };
    img.src = blobUrl;

    zoomInp.addEventListener('input', () => {
        zoom = Number(zoomInp.value) || 1;
        drawPreview();
    });

    let drag = false;
    let lx = 0;
    let ly = 0;
    viewport.addEventListener('pointerdown', (e) => {
        if (!imgEl) return;
        drag = true;
        lx = e.clientX;
        ly = e.clientY;
        viewport.setPointerCapture(e.pointerId);
    });
    viewport.addEventListener('pointermove', (e) => {
        if (!drag || !imgEl) return;
        panX += e.clientX - lx;
        panY += e.clientY - ly;
        lx = e.clientX;
        ly = e.clientY;
        drawPreview();
    });
    viewport.addEventListener('pointerup', () => {
        drag = false;
    });
    viewport.addEventListener('pointercancel', () => {
        drag = false;
    });

    backdrop.addEventListener('click', teardown);
    closeBtn.addEventListener('click', teardown);
    cancelBtn.addEventListener('click', teardown);
    okBtn.addEventListener('click', () => exportBlob());

    document.addEventListener('keydown', onEscKey);
}

function buildSettingsOverview(navigateFn) {
    const wrap = document.createElement('div');
    wrap.className = 'ts-settings-overview';
    const p = document.createElement('p');
    p.className = 'ts-text-muted';
    p.textContent = t('settingsIntro');
    wrap.appendChild(p);
    const grid = document.createElement('div');
    grid.className = 'ts-settings-cards';
    const addCard = (href, title, desc) => {
        const b = document.createElement('button');
        b.type = 'button';
        b.className = 'ts-settings-card ts-surface-soft';
        const t1 = document.createElement('div');
        t1.className = 'ts-settings-card-title';
        t1.textContent = title;
        const t2 = document.createElement('div');
        t2.className = 'ts-settings-card-desc ts-meta';
        t2.textContent = desc;
        b.append(t1, t2);
        b.addEventListener('click', () => navigateFn(href));
        grid.appendChild(b);
    };
    addCard('/pay/wallet', t('walletMenu'), t('settingsWalletDesc'));
    addCard('/setting/avatar', t('settingsAvatar'), t('settingsAvatarDesc'));
    if ((bootstrap.oauth || []).length > 0) {
        addCard('/setting/oauth', t('settingsOAuth'), t('settingsOAuthDesc'));
    }
    wrap.appendChild(grid);
    return wrap;
}

function buildSettingsAvatarPanel() {
    /** @type {Blob | null} */
    let pendingBlob = null;
    /** @type {string | null} */
    let cropPreviewUrl = null;

    const serverAvatarSrc = () =>
        `/user/avatar?username=${encodeURIComponent(bootstrap.user.username)}`;

    const wrap = document.createElement('div');
    wrap.className = 'ts-settings-panel ts-surface-soft';
    const h = document.createElement('h2');
    h.className = 'ts-settings-panel-title';
    h.textContent = t('settingsAvatar');
    const preview = document.createElement('img');
    preview.className = 'ts-settings-avatar-preview';
    preview.src = serverAvatarSrc();
    preview.alt = '';

    const err = document.createElement('div');
    err.className = 'ts-error';

    const inp = document.createElement('input');
    inp.type = 'file';
    inp.accept = 'image/jpeg,image/png,image/gif,image/bmp';
    inp.className = 'ts-settings-file';

    const pendingHint = document.createElement('p');
    pendingHint.className = 'ts-meta';
    pendingHint.hidden = true;
    pendingHint.style.marginTop = '0.35rem';
    pendingHint.textContent = t('settingsPendingCropHint');

    const row = document.createElement('div');
    row.className = 'ts-settings-actions';
    const pick = document.createElement('button');
    pick.type = 'button';
    pick.className = 'ts-btn primary';
    pick.textContent = t('settingsPickImage');
    pick.addEventListener('click', () => inp.click());

    const saveBtn = document.createElement('button');
    saveBtn.type = 'button';
    saveBtn.className = 'ts-btn primary';
    saveBtn.textContent = t('settingsSaveAvatar');
    saveBtn.disabled = true;

    const discardBtn = document.createElement('button');
    discardBtn.type = 'button';
    discardBtn.className = 'ts-btn ts-btn--ghost';
    discardBtn.textContent = t('settingsDiscardCrop');
    discardBtn.hidden = true;

    function clearPending() {
        if (cropPreviewUrl) {
            URL.revokeObjectURL(cropPreviewUrl);
            cropPreviewUrl = null;
        }
        pendingBlob = null;
        saveBtn.disabled = true;
        discardBtn.hidden = true;
        pendingHint.hidden = true;
        preview.src = `${serverAvatarSrc()}&t=${Date.now()}`;
    }

    function applyPending(blob) {
        if (cropPreviewUrl) URL.revokeObjectURL(cropPreviewUrl);
        cropPreviewUrl = URL.createObjectURL(blob);
        pendingBlob = blob;
        preview.src = cropPreviewUrl;
        saveBtn.disabled = false;
        discardBtn.hidden = false;
        pendingHint.hidden = false;
    }

    saveBtn.addEventListener('click', async () => {
        if (!pendingBlob) return;
        showUploadLoadingMask();
        err.textContent = '';
        pick.disabled = true;
        discardBtn.disabled = true;
        saveBtn.disabled = true;
        try {
            const fd = new FormData();
            fd.append('avatar', pendingBlob, 'avatar.jpg');
            fd.append('csrf_token', csrfToken);
            const r = await formPost('/ajax/user/json_avatar', fd);
            if (r.ret !== 0) {
                err.textContent = r.tp_error_msg || r.status || t('actionFailed');
                saveBtn.disabled = false;
                return;
            }
            await refreshBootstrap();
            if (cropPreviewUrl) {
                URL.revokeObjectURL(cropPreviewUrl);
                cropPreviewUrl = null;
            }
            pendingBlob = null;
            discardBtn.hidden = true;
            pendingHint.hidden = true;
            preview.src = `${serverAvatarSrc()}&t=${Date.now()}`;
        } finally {
            pick.disabled = false;
            discardBtn.disabled = false;
            hideUploadLoadingMask();
        }
    });

    discardBtn.addEventListener('click', () => {
        err.textContent = '';
        clearPending();
    });

    inp.addEventListener('change', () => {
        err.textContent = '';
        const f = inp.files?.[0];
        inp.value = '';
        if (!f) return;
        if (f.size > 2000000) {
            err.textContent = t('settingsFileTooLarge');
            return;
        }
        if (!/^image\/(jpeg|png|gif|bmp)$/i.test(f.type)) {
            err.textContent = t('settingsFileType');
            return;
        }
        openAvatarCropModal(f, (blob) => {
            err.textContent = '';
            applyPending(blob);
        });
    });

    row.append(pick, saveBtn, discardBtn);

    const grav = document.createElement('a');
    grav.href = '/setting/gravatar';
    grav.className = 'ts-btn ts-btn--ghost ts-settings-grav';
    grav.textContent = t('settingsUseGravatar');

    const note = document.createElement('p');
    note.className = 'ts-meta';
    note.style.marginTop = '0.65rem';
    note.textContent = t('settingsAvatarCropNote');

    wrap.append(h, preview, err, inp, row, pendingHint, grav, note);
    return wrap;
}

async function buildSettingsOAuthPanel() {
    const wrap = document.createElement('div');
    wrap.className = 'ts-settings-panel ts-surface-soft';
    const h = document.createElement('h2');
    h.className = 'ts-settings-panel-title';
    h.textContent = t('settingsOAuth');
    const p = document.createElement('p');
    p.className = 'ts-text-muted';
    p.textContent = t('settingsOAuthIntro');
    wrap.append(h, p);

    const res = await ajaxGet('/setting/json_binds');
    const binds = res.ret === 0 && Array.isArray(res.binds) ? res.binds : [];
    const byType = Object.fromEntries(binds.map((b) => [b.type, b]));

    const list = bootstrap.oauth || [];
    list.forEach(([type, name]) => {
        const row = document.createElement('div');
        row.className = 'ts-settings-oauth-row';

        const info = document.createElement('div');
        info.className = 'ts-settings-oauth-info';
        const title = document.createElement('div');
        title.className = 'ts-settings-oauth-name';
        title.textContent = String(name);
        const sub = document.createElement('div');
        sub.className = 'ts-meta ts-settings-oauth-status';
        const b = byType[type];
        if (b) {
            sub.textContent = b.masked
                ? t('settingsOAuthLinkedHint').replace('{id}', b.masked)
                : t('settingsOAuthLinkedBare');
        } else {
            sub.textContent = t('settingsOAuthNotLinked');
        }
        info.append(title, sub);

        const actions = document.createElement('div');
        actions.className = 'ts-settings-oauth-actions';
        const a = document.createElement('a');
        a.href = `/oauth/connect/${encodeURIComponent(type)}?referer=${encodeURIComponent('/setting/oauth')}`;
        a.className = 'ts-btn primary';
        a.textContent = b ? t('settingsOAuthRebind') : t('settingsOAuthBind').replace('{name}', String(name));
        actions.appendChild(a);

        row.append(info, actions);
        wrap.appendChild(row);
    });
    return wrap;
}

async function renderSetting(section) {
    if (!bootstrap.user) {
        navigate(buildLoginUrl(`${window.location.pathname}${window.location.search}`));
        return;
    }

    const oauthOn = (bootstrap.oauth || []).length > 0;
    const navHighlight = section === 'oauth' && !oauthOn ? 'index' : section;

    const layout = document.createElement('div');
    layout.className = 'ts-settings-layout';

    const nav = buildSettingsNav(navHighlight, navigate);
    const main = document.createElement('div');
    main.className = 'ts-settings-body';

    if (section === 'index') {
        main.appendChild(buildSettingsOverview(navigate));
    } else if (section === 'avatar') {
        main.appendChild(buildSettingsAvatarPanel());
    } else if (section === 'oauth') {
        if (!oauthOn) {
            const d = document.createElement('div');
            d.className = 'ts-card ts-error';
            d.textContent = t('settingsOAuthDisabled');
            main.appendChild(d);
        } else {
            main.appendChild(await buildSettingsOAuthPanel());
        }
    }

    layout.append(nav, main);
    mountShell(layout, {
        headerTitle: t('settings'),
        headerActions: buildHomeHeaderAction(),
        shellVariant: 'settings',
    });
}

async function renderLogin() {
    document.title = `${t('authPageLogin')} · ${bootstrap.siteName || 'TwimiSNS'}`;
    const qRef = new URLSearchParams(window.location.search).get('referer');

    const wrap = document.createElement('div');
    wrap.className = 'ts-auth-stack';

    wrap.appendChild(buildAuthPageHero('authPageLogin'));

    const err = document.createElement('div');
    err.className = 'ts-error';
    err.style.display = 'none';
    wrap.appendChild(err);

    const card = document.createElement('div');
    card.className = 'ts-card ts-auth-card';

    const form = document.createElement('form');
    form.innerHTML = `<div class="ts-field"><label>${escapeHtml(t('authUsername'))}</label><input name="username" autocomplete="username" required></div>
<div class="ts-field"><label>${escapeHtml(t('authPassword'))}</label><input name="password" type="password" autocomplete="current-password" required></div>
<button class="ts-btn primary" type="submit">${escapeHtml(t('authSubmitLogin'))}</button>`;

    form.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        err.style.display = 'none';
        const fd = new FormData(form);
        const payload = {
            csrf_token: csrfToken,
            username: fd.get('username'),
            password: fd.get('password'),
        };
        if (qRef) payload.referer = qRef;
        const j = await ajaxPost('/user/json_login_post', payload);
        if (j.ret === 0) {
            await afterAuthSessionRefreshNavigate();
        } else {
            err.textContent = j.tp_error_msg || t('actionFailed');
            err.style.display = 'block';
        }
    });

    card.appendChild(form);

    const foot = document.createElement('div');
    foot.className = 'ts-auth-foot';
    foot.appendChild(navLink('/user/forgot', t('authForgotPassword')));
    card.appendChild(foot);

    const oauthKeys = bootstrap.oauth || [];
    if (oauthKeys.length) {
        const bar = document.createElement('div');
        bar.className = 'ts-auth-oauth';
        const lb = document.createElement('div');
        lb.className = 'ts-auth-oauth-label';
        lb.textContent = t('authOAuthBar');
        bar.appendChild(lb);
        const refPath = `${window.location.pathname}${window.location.search}`;
        oauthKeys.forEach((o) => {
            if (!o || !o[0]) return;
            const a = document.createElement('a');
            a.href = oauthConnectHref(o[0], refPath);
            a.title = o[1] || o[0];
            const img = document.createElement('img');
            img.src = `/static/img/${o[0]}.png`;
            img.alt = o[1] || o[0];
            a.appendChild(img);
            bar.appendChild(a);
        });
        card.appendChild(bar);
    }

    wrap.appendChild(card);
    mountShell(wrap, {shellVariant: 'auth', authMode: 'login'});
}

async function renderRegister() {
    document.title = `${t('authPageRegister')} · ${bootstrap.siteName || 'TwimiSNS'}`;
    const wrap = document.createElement('div');
    wrap.className = 'ts-auth-stack';

    wrap.appendChild(buildAuthPageHero('authPageRegister'));

    if (!bootstrap.allowReg) {
        const card = document.createElement('div');
        card.className = 'ts-card ts-auth-card';
        const p = document.createElement('p');
        p.className = 'ts-text-muted';
        p.textContent = t('authRegisterClosed');
        card.appendChild(p);
        const foot = document.createElement('div');
        foot.className = 'ts-auth-foot';
        foot.appendChild(navLink('/', t('backHome')));
        card.appendChild(foot);
        wrap.appendChild(card);
        mountShell(wrap, {shellVariant: 'auth', authMode: 'register'});
        return;
    }

    const qRef = new URLSearchParams(window.location.search).get('referer');

    const err = document.createElement('div');
    err.className = 'ts-error';
    err.style.display = 'none';
    wrap.appendChild(err);

    const card = document.createElement('div');
    card.className = 'ts-card ts-auth-card';

    const form = document.createElement('form');
    form.innerHTML = `<div class="ts-field"><label>${escapeHtml(t('authUsername'))}</label><input name="username" autocomplete="username" required></div>
<div class="ts-field"><label>${escapeHtml(t('authPassword'))}</label><input name="password" type="password" autocomplete="new-password" required></div>
<div class="ts-field"><label>${escapeHtml(t('authEmail'))}</label><input name="email" type="email" autocomplete="email" required></div>
<div class="ts-field"><label>${escapeHtml(t('authNickname'))}</label><input name="nickname" autocomplete="nickname"></div>
<button class="ts-btn primary" type="submit">${escapeHtml(t('authSubmitRegister'))}</button>`;

    form.addEventListener('submit', async (ev) => {
        ev.preventDefault();
        err.style.display = 'none';
        const fd = new FormData(form);
        const payload = {
            csrf_token: csrfToken,
            username: fd.get('username'),
            password: fd.get('password'),
            email: fd.get('email'),
            nickname: fd.get('nickname') || '',
        };
        if (qRef) payload.referer = qRef;
        const j = await ajaxPost('/user/json_register_post', payload);
        if (j.ret === 0) {
            await afterAuthSessionRefreshNavigate();
        } else {
            err.textContent = j.tp_error_msg || t('actionFailed');
            err.style.display = 'block';
        }
    });

    card.appendChild(form);

    wrap.appendChild(card);
    mountShell(wrap, {shellVariant: 'auth', authMode: 'register'});
}

function renderOauthBind(route) {
    document.title = `${t('authBindTitle')} · ${bootstrap.siteName || 'TwimiSNS'}`;
    const bind = bootstrap.oauthBind;
    if (!bind || !bind.type || bind.type !== route.bindType) {
        const wrap = document.createElement('div');
        wrap.className = 'ts-main-stack ts-auth-stack';
        const card = document.createElement('div');
        card.className = 'ts-card ts-auth-card';
        const p = document.createElement('p');
        p.className = 'ts-error';
        p.textContent = t('authBindMissing');
        card.appendChild(p);
        const foot = document.createElement('div');
        foot.className = 'ts-auth-foot';
        foot.appendChild(navLink('/', t('backHome')));
        card.appendChild(foot);
        wrap.appendChild(card);
        mountShell(wrap, {headerTitle: t('authBindTitle'), headerActions: buildHomeHeaderAction()});
        return;
    }

    const wrap = document.createElement('div');
    wrap.className = 'ts-main-stack ts-auth-stack';

    const err = document.createElement('div');
    err.className = 'ts-error';
    err.style.display = bind.errorMsg ? 'block' : 'none';
    err.textContent = bind.errorMsg || '';
    wrap.appendChild(err);

    const card = document.createElement('div');
    card.className = 'ts-card ts-auth-card';

    const allowReg = !!bind.allowReg;
    const preferReg = bind.bindMode === 'reg';

    function buildRegForm() {
        const formReg = document.createElement('form');
        formReg.method = 'post';
        formReg.action = `/oauth/bind/${encodeURIComponent(bind.type)}?bind_type=reg`;
        formReg.innerHTML = `<input type="hidden" name="csrf_token" value="${escapeHtml(csrfToken)}">
<div class="ts-field"><label>${escapeHtml(t('authUsername'))}</label><input name="username" required></div>
<div class="ts-field"><label>${escapeHtml(t('authPassword'))}</label><input name="password" type="password" required></div>
<div class="ts-field"><label>${escapeHtml(t('authEmail'))}</label><input name="email" type="email" required></div>
<div class="ts-field"><label>${escapeHtml(t('authNickname'))}</label><input name="nickname" value="${escapeHtml(bind.nickname || '')}" required></div>
<button class="ts-btn primary" type="submit">${escapeHtml(t('authBindSubmitReg'))}</button>`;
        return formReg;
    }

    function buildLoginForm() {
        const formLogin = document.createElement('form');
        formLogin.method = 'post';
        formLogin.action = `/oauth/bind/${encodeURIComponent(bind.type)}?bind_type=login`;
        formLogin.innerHTML = `<input type="hidden" name="csrf_token" value="${escapeHtml(csrfToken)}">
<div class="ts-field"><label>${escapeHtml(t('authUsername'))}</label><input name="username" required></div>
<div class="ts-field"><label>${escapeHtml(t('authPassword'))}</label><input name="password" type="password" required></div>
<button class="ts-btn primary" type="submit">${escapeHtml(t('authBindSubmitLogin'))}</button>`;
        return formLogin;
    }

    if (!allowReg) {
        card.appendChild(buildLoginForm());
    } else {
        const tabs = document.createElement('div');
        tabs.className = 'ts-auth-tabs';
        const btnReg = document.createElement('button');
        btnReg.type = 'button';
        btnReg.className = 'ts-auth-tab';
        btnReg.textContent = t('authBindTabReg');
        const btnLogin = document.createElement('button');
        btnLogin.type = 'button';
        btnLogin.className = 'ts-auth-tab';
        btnLogin.textContent = t('authBindTabLogin');

        const paneReg = document.createElement('div');
        paneReg.className = 'ts-auth-tabpane';
        paneReg.appendChild(buildRegForm());
        const paneLogin = document.createElement('div');
        paneLogin.className = 'ts-auth-tabpane';
        paneLogin.appendChild(buildLoginForm());

        function setTab(which) {
            const isReg = which === 'reg';
            btnReg.classList.toggle('ts-auth-tab--active', isReg);
            btnLogin.classList.toggle('ts-auth-tab--active', !isReg);
            paneReg.classList.toggle('ts-auth-tabpane--active', isReg);
            paneLogin.classList.toggle('ts-auth-tabpane--active', !isReg);
        }

        btnReg.addEventListener('click', () => setTab('reg'));
        btnLogin.addEventListener('click', () => setTab('login'));
        tabs.append(btnReg, btnLogin);
        card.appendChild(tabs);
        const panes = document.createElement('div');
        panes.append(paneReg, paneLogin);
        card.appendChild(panes);
        setTab(preferReg ? 'reg' : 'login');
    }

    wrap.appendChild(card);
    mountShell(wrap, {headerTitle: t('authBindTitle'), headerActions: buildHomeHeaderAction()});
}

function renderOauthAuthorize() {
    const oa = bootstrap.oauthAuthorize;
    if (!oa) {
        renderNotFound(window.location.pathname);
        return;
    }
    document.title = `${t('authOauthTitle')} · ${bootstrap.siteName || 'TwimiSNS'}`;
    const wrap = document.createElement('div');
    wrap.className = 'ts-main-stack ts-auth-stack';

    const errTop = document.createElement('div');
    errTop.className = 'ts-error';
    errTop.style.display = oa.errorMsg ? 'block' : 'none';
    errTop.textContent = oa.errorMsg || '';
    wrap.appendChild(errTop);

    if (oa.hideForm) {
        const foot = document.createElement('div');
        foot.className = 'ts-auth-foot';
        foot.appendChild(navLink('/', t('backHome')));
        wrap.appendChild(foot);
        mountShell(wrap, {headerTitle: t('authOauthTitle'), headerActions: buildHomeHeaderAction()});
        return;
    }

    const card = document.createElement('div');
    card.className = 'ts-card ts-auth-card';

    const lead = document.createElement('p');
    lead.style.marginBottom = '0.75rem';
    const appLinkRaw = oa.appUrl || '';
    const appHref =
        appLinkRaw.includes('://') || appLinkRaw.startsWith('//')
            ? appLinkRaw
            : `https://${appLinkRaw.replace(/^\/+/, '')}`;
    lead.innerHTML =
        `${escapeHtml(t('authOauthPrompt'))} ` +
        `<a href="${escapeHtml(appHref)}" target="_blank" rel="noopener noreferrer">${escapeHtml(oa.clientName || '')}</a>`;
    card.appendChild(lead);

    const form = document.createElement('form');
    form.method = 'post';
    form.action = '/oauth/authorize';

    const hidCid = document.createElement('input');
    hidCid.type = 'hidden';
    hidCid.name = 'client_id';
    hidCid.value = oa.clientId;
    const hidRedir = document.createElement('input');
    hidRedir.type = 'hidden';
    hidRedir.name = 'redirect_uri';
    hidRedir.value = oa.redirectUri;
    const hidCsrf = document.createElement('input');
    hidCsrf.type = 'hidden';
    hidCsrf.name = 'csrf_token';
    hidCsrf.value = csrfToken;
    form.append(hidCid, hidRedir, hidCsrf);

    if (oa.loggedIn && oa.user) {
        const preview = document.createElement('div');
        preview.className = 'ts-auth-oauth-preview';
        const uAv = document.createElement('img');
        uAv.src = `/user/avatar?username=${encodeURIComponent(oa.user.username)}`;
        uAv.alt = '';
        const arrow = document.createElement('span');
        arrow.className = 'ts-auth-oauth-arrow';
        arrow.textContent = '→';
        const cAv = document.createElement('img');
        cAv.src = oa.clientIcon || '/static/img/logo.png';
        cAv.alt = '';
        preview.append(uAv, arrow, cAv);
        form.appendChild(preview);

        const who = document.createElement('p');
        who.className = 'ts-meta';
        who.style.textAlign = 'center';
        who.textContent = oa.user.nickname || oa.user.username;
        form.appendChild(who);

        const submit = document.createElement('button');
        submit.type = 'submit';
        submit.className = 'ts-btn primary';
        submit.style.width = '100%';
        submit.textContent = t('authOauthAuthorize');
        form.appendChild(submit);
    } else {
        const uField = document.createElement('div');
        uField.className = 'ts-field';
        uField.innerHTML = `<label>${escapeHtml(t('authUsername'))}</label><input name="username" autocomplete="username" required>`;
        const pField = document.createElement('div');
        pField.className = 'ts-field';
        pField.innerHTML = `<label>${escapeHtml(t('authPassword'))}</label><input name="password" type="password" autocomplete="current-password" required>`;
        const submit = document.createElement('button');
        submit.type = 'submit';
        submit.className = 'ts-btn primary';
        submit.style.width = '100%';
        submit.textContent = t('authOauthLogin');
        form.append(uField, pField, submit);
    }

    card.appendChild(form);

    if (!(oa.loggedIn && oa.user)) {
        const foot = document.createElement('div');
        foot.className = 'ts-auth-foot';
        const authReturn = `/oauth/authorize?client_id=${encodeURIComponent(oa.clientId)}&redirect_uri=${encodeURIComponent(oa.redirectUri)}`;
        foot.appendChild(navLink(`/user/register?referer=${encodeURIComponent(authReturn)}`, t('authOauthRegisterLink')));
        foot.appendChild(navLink('/user/forgot', t('authForgotPassword')));
        card.appendChild(foot);
    }

    wrap.appendChild(card);
    mountShell(wrap, {headerTitle: t('authOauthTitle'), headerActions: buildHomeHeaderAction()});
}

function renderNotFound(path) {
    document.title = `${t('site404')} · ${bootstrap.siteName || 'TwimiSNS'}`;
    const wrap = document.createElement('div');
    wrap.className = 'ts-main-stack ts-empty-page';
    const h = document.createElement('h1');
    h.className = 'ts-empty-page-title';
    h.textContent = t('site404');
    const p = document.createElement('p');
    p.className = 'ts-empty-page-desc';
    p.textContent = t('site404Desc');
    const code = document.createElement('p');
    code.className = 'ts-meta';
    code.style.marginTop = '0.35rem';
    code.textContent = path ? String(path) : '';
    const btn = document.createElement('a');
    btn.href = '/';
    btn.className = 'ts-btn primary';
    btn.textContent = t('backHome');
    btn.addEventListener('click', (e) => {
        if (e.metaKey || e.ctrlKey || e.shiftKey || e.button !== 0) return;
        e.preventDefault();
        navigate('/');
    });
    wrap.append(h, p, code, btn);

    mountShell(wrap, {headerTitle: t('site404'), headerActions: buildHomeHeaderAction()});
}

async function render() {
    closeComposeModal();
    await refreshBootstrap();
    const route = parseRoute(window.location.pathname);
    switch (route.name) {
        case 'home':
            await renderHome();
            break;
        case 'post':
            await renderPost(route.tid);
            break;
        case 'buy':
            await renderBuy(route.tid);
            break;
        case 'wallet':
            await renderWallet();
            break;
        case 'pay_start':
            await renderPayStart();
            break;
        case 'create':
            renderCreate();
            break;
        case 'search':
            await renderSearch();
            break;
        case 'user':
            await renderUser(route.username, route.tab);
            break;
        case 'friend':
            await renderFriend();
            break;
        case 'notify':
            await renderNotify();
            break;
        case 'setting':
            await renderSetting(route.section || 'index');
            break;
        case 'login':
            await renderLogin();
            break;
        case 'register':
            await renderRegister();
            break;
        case 'oauth_bind':
            renderOauthBind(route);
            break;
        case 'oauth_authorize':
            renderOauthAuthorize();
            break;
        default:
            renderNotFound(route.path || window.location.pathname);
    }
}

document.addEventListener('DOMContentLoaded', () => void render());
