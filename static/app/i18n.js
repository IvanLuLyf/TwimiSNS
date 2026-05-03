/** @typedef {'zh-CN' | 'en' | 'ja'} Locale */

const STORAGE_KEY = 'twimi_locale';

/** Display names are always native. @type {ReadonlyArray<{ id: Locale; label: string }>} */
export const LOCALE_OPTIONS = [
    {id: 'zh-CN', label: '中文'},
    {id: 'en', label: 'English'},
    {id: 'ja', label: '日本語'},
];

/** @type {readonly Locale[]} */
const VALID_LOCALES = ['zh-CN', 'en', 'ja'];

/**
 * Maps locale id to chunk URL (dynamic import only resolves these literals).
 * @param {Locale} loc
 */
function localeModule(loc) {
    switch (loc) {
        case 'en':
            return import('./locales/en.js');
        case 'ja':
            return import('./locales/ja.js');
        default:
            return import('./locales/zh-CN.js');
    }
}

/** @type {Locale} */
let activeLocale = 'zh-CN';

/** Current UI strings (one language per session load). */
/** @type {Record<string, string>} */
let messages = {};

function normalizeLocale(raw) {
    const s = String(raw || '').trim().toLowerCase();
    if (s === 'en' || s.startsWith('en-')) return 'en';
    if (s === 'ja' || s.startsWith('ja-')) return 'ja';
    return 'zh-CN';
}

function htmlLangFromLocale(loc) {
    if (loc === 'en') return 'en';
    if (loc === 'ja') return 'ja';
    return 'zh-CN';
}

/** @param {Record<string, unknown>} bootstrap */
export async function initI18n(bootstrap) {
    const saved = typeof localStorage !== 'undefined' ? localStorage.getItem(STORAGE_KEY) : null;
    const fromServer = bootstrap && typeof bootstrap.locale === 'string' ? bootstrap.locale : '';
    const nav =
        typeof navigator !== 'undefined' && navigator.language ? navigator.language : 'zh-CN';
    activeLocale = normalizeLocale(saved || fromServer || nav);
    if (!VALID_LOCALES.includes(activeLocale)) activeLocale = 'zh-CN';

    try {
        const mod = await localeModule(activeLocale);
        messages = mod.default || {};
    } catch (e) {
        console.warn('[i18n] failed to load locale', activeLocale, e);
        activeLocale = 'zh-CN';
        const mod = await localeModule('zh-CN');
        messages = mod.default || {};
    }

    if (typeof document !== 'undefined') {
        document.documentElement.lang = htmlLangFromLocale(activeLocale);
    }
}

export function getLocale() {
    return activeLocale;
}

/** @param {Locale} loc */
export function setLocale(loc) {
    const next = normalizeLocale(loc);
    activeLocale = VALID_LOCALES.includes(next) ? next : 'zh-CN';
    try {
        localStorage.setItem(STORAGE_KEY, activeLocale);
    } catch {
        /* ignore */
    }
    if (typeof document !== 'undefined') {
        document.documentElement.lang = htmlLangFromLocale(activeLocale);
    }
}

/** @param {string} loc */
export function applyLocaleChange(loc) {
    setLocale(loc);
    if (typeof location !== 'undefined') location.reload();
}

/** @param {string} key */
export function t(key) {
    return messages[key] ?? key;
}
