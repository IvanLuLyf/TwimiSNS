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

export async function ajaxGet(path) {
    const p = path.startsWith('/') ? path : `/${path}`;
    return jsonGet(`/ajax${p}`);
}

export async function ajaxPost(path, data) {
    const p = path.startsWith('/') ? path : `/${path}`;
    return jsonPost(`/ajax${p}`, data);
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

export async function formPost(path, formData) {
    return (await fetch(path, {method: 'POST', credentials: 'include', body: formData})).json();
}
