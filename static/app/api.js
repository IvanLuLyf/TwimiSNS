/** 同源 JSON（cookie 会话） */

function enc(data) {
    const p = new URLSearchParams();
    Object.keys(data).forEach((k) => {
        if (data[k] !== undefined && data[k] !== null) p.set(k, String(data[k]));
    });
    return p.toString();
}

export async function jsonGet(path) {
    return (await fetch(path, {credentials: 'include'})).json();
}

export async function jsonPost(path, data) {
    return (
        await fetch(path, {
            method: 'POST',
            credentials: 'include',
            headers: {'Content-Type': 'application/x-www-form-urlencoded; charset=UTF-8'},
            body: typeof data === 'string' ? data : enc(data),
        })
    ).json();
}

/** multipart（不传 Content-Type，由浏览器带 boundary） */
export async function formPost(path, formData) {
    return (await fetch(path, {method: 'POST', credentials: 'include', body: formData})).json();
}
