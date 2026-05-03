/** 24×24 stroke icons（currentColor），用于 Tab / 抽屉 / 侧栏 */
const NS = 'http://www.w3.org/2000/svg';

/** @type {Record<string, { tag: string; attr: Record<string, string> }[]>} */
const ICON_PARTS = {
    home: [
        {tag: 'path', attr: {d: 'm3 9 9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z'}},
        {tag: 'polyline', attr: {points: '9 22 9 12 15 12 15 22'}},
    ],
    search: [
        {tag: 'circle', attr: {cx: '11', cy: '11', r: '8'}},
        {tag: 'path', attr: {d: 'm21 21-4.3-4.3'}},
    ],
    users: [
        {tag: 'path', attr: {d: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2'}},
        {tag: 'circle', attr: {cx: '9', cy: '7', r: '4'}},
        {tag: 'path', attr: {d: 'M22 21v-2a4 4 0 0 0-3-3.87'}},
        {tag: 'path', attr: {d: 'M16 3.13a4 4 0 0 1 0 7.75'}},
    ],
    user: [
        {tag: 'path', attr: {d: 'M19 21v-2a4 4 0 0 0-4-4H9a4 4 0 0 0-4 4v2'}},
        {tag: 'circle', attr: {cx: '12', cy: '7', r: '4'}},
    ],
    bell: [
        {tag: 'path', attr: {d: 'M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9'}},
        {tag: 'path', attr: {d: 'M10.3 21a1.94 1.94 0 0 0 3.4 0'}},
    ],
    edit: [
        {tag: 'path', attr: {d: 'M12 20h9'}},
        {tag: 'path', attr: {d: 'M16.5 3.5a2.121 2.121 0 0 1 3 3L7 19l-4 1 1-4L16.5 3.5z'}},
    ],
    sliders: [
        {tag: 'line', attr: {x1: '21', y1: '4', x2: '14', y2: '4'}},
        {tag: 'line', attr: {x1: '10', y1: '4', x2: '3', y2: '4'}},
        {tag: 'line', attr: {x1: '21', y1: '12', x2: '12', y2: '12'}},
        {tag: 'line', attr: {x1: '8', y1: '12', x2: '3', y2: '12'}},
        {tag: 'line', attr: {x1: '21', y1: '20', x2: '16', y2: '20'}},
        {tag: 'line', attr: {x1: '12', y1: '20', x2: '3', y2: '20'}},
        {tag: 'line', attr: {x1: '14', y1: '2', x2: '14', y2: '6'}},
        {tag: 'line', attr: {x1: '8', y1: '10', x2: '8', y2: '14'}},
        {tag: 'line', attr: {x1: '16', y1: '18', x2: '16', y2: '22'}},
    ],
    logOut: [
        {tag: 'path', attr: {d: 'M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4'}},
        {tag: 'polyline', attr: {points: '16 17 21 12 16 7'}},
        {tag: 'line', attr: {x1: '21', y1: '12', x2: '9', y2: '12'}},
    ],
    logIn: [
        {tag: 'path', attr: {d: 'M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4'}},
        {tag: 'polyline', attr: {points: '10 17 15 12 10 7'}},
        {tag: 'line', attr: {x1: '15', y1: '12', x2: '3', y2: '12'}},
    ],
    userPlus: [
        {tag: 'path', attr: {d: 'M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2'}},
        {tag: 'circle', attr: {cx: '9', cy: '7', r: '4'}},
        {tag: 'line', attr: {x1: '19', y1: '8', x2: '19', y2: '14'}},
        {tag: 'line', attr: {x1: '22', y1: '11', x2: '16', y2: '11'}},
    ],
    menu: [
        {tag: 'line', attr: {x1: '4', y1: '6', x2: '20', y2: '6'}},
        {tag: 'line', attr: {x1: '4', y1: '12', x2: '20', y2: '12'}},
        {tag: 'line', attr: {x1: '4', y1: '18', x2: '20', y2: '18'}},
    ],
    x: [
        {tag: 'line', attr: {x1: '18', y1: '6', x2: '6', y2: '18'}},
        {tag: 'line', attr: {x1: '6', y1: '6', x2: '18', y2: '18'}},
    ],
    globe: [
        {tag: 'circle', attr: {cx: '12', cy: '12', r: '10'}},
        {tag: 'path', attr: {d: 'M2 12h20'}},
        {
            tag: 'path',
            attr: {
                d: 'M12 2a14.5 14.5 0 0 1 0 20 14.5 14.5 0 0 1 0-20',
            },
        },
    ],
    wallet: [
        {
            tag: 'path',
            attr: {d: 'M19 7V4a1 1 0 0 0-1-1H5a2 2 0 0 0 0 4h15a1 1 0 0 1 1 1v4h-3a2 2 0 0 0 0 4h3a1 1 0 0 0 1-1v-2a1 1 0 0 0-1-1'}
        },
        {tag: 'path', attr: {d: 'M3 5v14a2 2 0 0 0 2 2h15a1 1 0 0 0 1-1v-4'}},
    ],
};

/**
 * @param {keyof typeof ICON_PARTS} name
 * @param {number} [size]
 */
export function createIcon(name, size = 22) {
    const parts = ICON_PARTS[name];
    const svg = document.createElementNS(NS, 'svg');
    svg.setAttribute('viewBox', '0 0 24 24');
    svg.setAttribute('width', String(size));
    svg.setAttribute('height', String(size));
    svg.setAttribute('fill', 'none');
    svg.setAttribute('stroke', 'currentColor');
    svg.setAttribute('stroke-width', '2');
    svg.setAttribute('stroke-linecap', 'round');
    svg.setAttribute('stroke-linejoin', 'round');
    svg.setAttribute('aria-hidden', 'true');
    if (!parts) return svg;
    for (const spec of parts) {
        const node = document.createElementNS(NS, spec.tag);
        for (const [k, v] of Object.entries(spec.attr)) {
            node.setAttribute(k, v);
        }
        svg.appendChild(node);
    }
    return svg;
}
