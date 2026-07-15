const csrfToken = document.querySelector('meta[name="csrf-token"]')?.content ?? '';

export class ApiError extends Error {
    constructor(message, status, errors = {}) {
        super(message);
        this.name = 'ApiError';
        this.status = status;
        this.errors = errors;
    }
}

export async function apiRequest(url, options = {}) {
    const headers = new Headers(options.headers ?? {});
    headers.set('Accept', 'application/json');
    headers.set('X-Requested-With', 'XMLHttpRequest');

    const body = options.body;
    if (body && !(body instanceof FormData) && typeof body !== 'string') {
        headers.set('Content-Type', 'application/json');
        options.body = JSON.stringify(body);
    }
    if (options.method && options.method !== 'GET') {
        headers.set('X-CSRF-TOKEN', csrfToken);
    }

    const response = await fetch(url, {
        credentials: 'same-origin',
        ...options,
        headers,
    });

    if (response.status === 204) return null;

    const contentType = response.headers.get('content-type') ?? '';
    const payload = contentType.includes('application/json')
        ? await response.json()
        : { message: await response.text() };

    if (!response.ok) {
        throw new ApiError(payload.message || 'ไม่สามารถดำเนินการได้', response.status, payload.errors ?? {});
    }

    return payload;
}

export function toFormData(values, method = null) {
    const form = new FormData();
    Object.entries(values).forEach(([key, value]) => {
        if (value === undefined || value === null || value === '') return;
        form.append(key, value instanceof FileList ? value[0] : value);
    });
    if (method) form.append('_method', method);
    return form;
}

export function queryString(params = {}) {
    const search = new URLSearchParams();
    Object.entries(params).forEach(([key, value]) => {
        if (value !== undefined && value !== null && value !== '') search.set(key, value);
    });
    const result = search.toString();
    return result ? `?${result}` : '';
}

export function firstError(error) {
    if (!error) return '';
    const validation = Object.values(error.errors ?? {}).flat()[0];
    return validation || error.message || 'เกิดข้อผิดพลาด กรุณาลองใหม่';
}
