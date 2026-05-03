/**
 * Core Markdown → safe HTML: marked + DOMPurify strict whitelist.
 */
import DOMPurify from 'https://esm.sh/dompurify@3.1.6';
import {marked} from 'https://esm.sh/marked@12.0.2';

marked.use({
    gfm: true,
    breaks: true,
});

const PURIFY = {
    ALLOWED_TAGS: [
        'p', 'br', 'strong', 'em', 'code', 'pre', 'h1', 'h2', 'h3', 'h4',
        'ul', 'ol', 'li', 'a', 'blockquote', 'hr', 'table', 'thead', 'tbody', 'tr', 'th', 'td',
        'img',
    ],
    ALLOWED_ATTR: [
        'href', 'title', 'class',
        'src', 'alt', 'width', 'height', 'loading', 'decoding', 'referrerpolicy',
    ],
    ALLOW_DATA_ATTR: false,
};

function sanitizeHref(href) {
    if (!href || typeof href !== 'string') {
        return '';
    }
    const t = href.trim();
    if (/^https?:\/\//i.test(t) || t.startsWith('/') || t.startsWith('#') || t.startsWith('mailto:')) {
        return t;
    }
    return '';
}

/** 仅允许 http(s)、站内路径与无 scheme 的相对路径；拒绝 javascript:/data: 等 */
function sanitizeImgSrc(src) {
    if (!src || typeof src !== 'string') {
        return '';
    }
    const t = src.trim();
    if (/^https?:\/\//i.test(t) || t.startsWith('/')) {
        return t;
    }
    if (/^[a-zA-Z][a-zA-Z\d+.-]*:/i.test(t)) {
        return '';
    }
    return t;
}

/**
 * Feed 列表：拆出图片 URL（九宫格等），正文去掉图片后再做有限长度 Markdown。
 *
 * @param {string} content
 * @param {{ maxTextLen?: number, maxImages?: number }} [options]
 * @returns {{ textMd: string, images: string[], overflowImageCount: number }}
 */
export function prepareFeedMarkdownPreview(content, options = {}) {
    const maxTextLen = options.maxTextLen ?? 720;
    const maxImages = options.maxImages ?? 9;
    if (!content || typeof content !== 'string') {
        return {textMd: '', images: [], overflowImageCount: 0};
    }

    const collected = [];
    const pushUrl = (raw) => {
        const safe = sanitizeImgSrc(typeof raw === 'string' ? raw.trim() : '');
        if (!safe || collected.includes(safe)) return;
        collected.push(safe);
    };

    let s = content;

    s = s.replace(/!\[[^\]]*\]\(\s*([^)\s]+)(?:\s+["'][^"']*["'])?\s*\)/g, (_, url) => {
        pushUrl(url);
        return '';
    });

    s = s.replace(/<img\b[^>]*>/gi, (tag) => {
        let m = /\bsrc\s*=\s*(["'])([^"']*)\1/i.exec(tag);
        if (m) pushUrl(m[2]);
        else {
            m = /\bsrc\s*=\s*([^\s>]+)/i.exec(tag);
            if (m) pushUrl(m[1].replace(/^["']|["']$/g, ''));
        }
        return '';
    });

    s = s.replace(/\n{3,}/g, '\n\n').trim();

    const overflowImageCount = Math.max(0, collected.length - maxImages);
    const images = collected.slice(0, maxImages);

    let textMd = s;
    if (textMd.length > maxTextLen) {
        textMd = `${textMd.slice(0, maxTextLen)}…`;
    }

    return {textMd, images, overflowImageCount};
}

export function renderMarkdown(src) {
    if (!src || typeof src !== 'string') {
        return document.createElement('div');
    }
    const raw = marked.parse(src, {async: false});
    const wrap = document.createElement('div');
    wrap.innerHTML = DOMPurify.sanitize(raw, {
        ...PURIFY,
        ALLOWED_URI_REGEXP: /^(?:(?:https?|mailto):|[^a-z]|[a-z+.-]+(?:[^a-z+.\-:]|$))/i,
    });
    wrap.querySelectorAll('a[href]').forEach((a) => {
        const safe = sanitizeHref(a.getAttribute('href'));
        if (!safe) {
            a.removeAttribute('href');
            return;
        }
        a.setAttribute('href', safe);
        a.setAttribute('rel', 'noopener noreferrer');
        if (/^https?:/i.test(safe)) {
            a.setAttribute('target', '_blank');
        }
    });
    wrap.querySelectorAll('img').forEach((img) => {
        const safe = sanitizeImgSrc(img.getAttribute('src') || '');
        if (!safe) {
            img.remove();
            return;
        }
        img.setAttribute('src', safe);
        if (!img.hasAttribute('loading')) {
            img.setAttribute('loading', 'lazy');
        }
        if (!img.hasAttribute('decoding')) {
            img.setAttribute('decoding', 'async');
        }
        /** 外链图常因 Referer 被拒绝；默认不传 Referer（作者可在 Markdown 里写 referrerpolicy 覆盖） */
        if (!img.hasAttribute('referrerpolicy')) {
            img.setAttribute('referrerpolicy', 'no-referrer');
        }
    });
    wrap.classList.add('ts-md');
    return wrap;
}

export function escapeHtml(s) {
    const d = document.createElement('div');
    d.textContent = s;
    return d.innerHTML;
}
