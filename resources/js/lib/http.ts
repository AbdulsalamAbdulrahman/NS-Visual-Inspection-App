/**
 * Small JSON client for the form's background saves and uploads (outside
 * Inertia visits). Sends Laravel's XSRF token from its cookie.
 */
export class HttpError extends Error {
    constructor(
        public readonly status: number,
        public readonly body: unknown,
    ) {
        super(`Request failed with status ${status}`);
    }

    /** First validation message per field, if this was a 422. */
    get errors(): Record<string, string> {
        const errors = (this.body as { errors?: Record<string, string[]> } | null)?.errors ?? {};

        return Object.fromEntries(Object.entries(errors).map(([k, v]) => [k, v[0]]));
    }
}

export function xsrfToken(): string {
    const match = document.cookie.match(/(?:^|;\s*)XSRF-TOKEN=([^;]+)/);

    return match ? decodeURIComponent(match[1]) : '';
}

export async function sendJson<T>(method: 'PUT' | 'POST' | 'DELETE', url: string, body?: unknown): Promise<T> {
    const response = await fetch(url, {
        method,
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-Requested-With': 'XMLHttpRequest',
            'X-XSRF-TOKEN': xsrfToken(),
        },
        body: body === undefined ? undefined : JSON.stringify(body),
    });

    const data = response.status === 204 ? null : await response.json().catch(() => null);

    if (!response.ok) {
        throw new HttpError(response.status, data);
    }

    return data as T;
}

/** Multipart upload with progress (fetch can't report upload progress). */
export function upload<T>(url: string, form: FormData, onProgress: (fraction: number) => void): Promise<T> {
    return new Promise((resolve, reject) => {
        const xhr = new XMLHttpRequest();
        xhr.open('POST', url);
        xhr.withCredentials = true;
        xhr.setRequestHeader('Accept', 'application/json');
        xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
        xhr.setRequestHeader('X-XSRF-TOKEN', xsrfToken());
        xhr.upload.onprogress = (e) => e.lengthComputable && onProgress(e.loaded / e.total);
        xhr.onload = () => {
            const data = (() => {
                try {
                    return JSON.parse(xhr.responseText);
                } catch {
                    return null;
                }
            })();

            if (xhr.status >= 200 && xhr.status < 300) {
                resolve(data as T);
            } else {
                reject(new HttpError(xhr.status, data));
            }
        };
        xhr.onerror = () => reject(new HttpError(0, null));
        xhr.send(form);
    });
}
