import { HttpError, sendJson } from '@/lib/http';
import type { Draft } from './types';

/** `signin`: the session expired; the draft stays on the phone until they sign in again. */
export type SaveStatus =
    | 'saved'
    | 'pending'
    | 'saving'
    | 'offline'
    | 'error'
    | 'signin';

type SaveResponse = {
    uuid: string;
    saved_at: string;
    signature_url: string | null;
};

/** Fields sent on every save; attachments and display-only keys are excluded. */
const SKIP = new Set([
    'uuid',
    'status',
    'attachments',
    'signature_url',
    'inspection_date',
    'updated_at',
]);

export function draftPayload(draft: Draft): Record<string, unknown> {
    return Object.fromEntries(
        Object.entries(draft).filter(([key]) => !SKIP.has(key)),
    );
}

/**
 * Debounced background save of the draft (PUT /inspections/{uuid}/draft).
 * Never blocks typing; queues one follow-up save if edits land mid-request;
 * reports "On device" while offline and retries when the network returns.
 */
export class Autosave {
    status = $state<SaveStatus>('saved');
    savedAt = $state<Date | null>(null);
    /** Validation messages from the last rejected save, by field. */
    errors = $state<Record<string, string>>({});

    #uuid: string;
    #delay: number;
    #timer: ReturnType<typeof setTimeout> | undefined;
    #inflight: Promise<void> | null = null;
    #next: Record<string, unknown> | null = null;
    #lastSent = '';
    #onSaved: (response: SaveResponse, sentAt: number) => void;

    constructor(
        uuid: string,
        savedAt: string | null,
        onSaved: (response: SaveResponse, sentAt: number) => void,
        delay = 2000,
    ) {
        this.#uuid = uuid;
        this.#delay = delay;
        this.#onSaved = onSaved;
        this.savedAt = savedAt ? new Date(savedAt) : null;

        if (typeof window !== 'undefined') {
            window.addEventListener('online', this.#retry);
        }
    }

    /** Remember the current state without saving (initial load). */
    prime(payload: Record<string, unknown>): void {
        this.#lastSent = this.#key(payload);
    }

    /** Call on every change; saves after a pause in editing. Returns false when nothing changed. */
    schedule(payload: Record<string, unknown>): boolean {
        // A new signature is sent once; it doesn't count towards "changed" afterwards.
        if (
            this.#key(payload) === this.#lastSent &&
            !('signature' in payload) &&
            !this.#next
        ) {
            return false;
        }

        this.#next = payload;
        this.status = navigator.onLine ? 'pending' : 'offline';
        clearTimeout(this.#timer);
        this.#timer = setTimeout(() => void this.flush(), this.#delay);

        return true;
    }

    /** Save now (Save draft button, leaving the form, paying). */
    async flush(): Promise<boolean> {
        clearTimeout(this.#timer);

        if (this.#inflight) {
            await this.#inflight;
        }

        if (!this.#next) {
            return this.status === 'saved';
        }

        const payload = this.#next;
        this.#next = null;

        if (!navigator.onLine) {
            this.#next = payload;
            this.status = 'offline';

            return false;
        }

        this.status = 'saving';
        const sentAt = Date.now();
        this.#inflight = (async () => {
            try {
                const response = await sendJson<SaveResponse>(
                    'PUT',
                    `/inspections/${this.#uuid}/draft`,
                    payload,
                );
                this.#lastSent = this.#key(payload);
                this.savedAt = new Date(response.saved_at);
                this.errors = {};
                this.status = this.#next ? 'pending' : 'saved';
                this.#onSaved(response, sentAt);
            } catch (error) {
                if (error instanceof HttpError && error.status === 422) {
                    this.errors = error.errors;
                    this.status = 'error';
                } else if (
                    error instanceof HttpError &&
                    (error.status === 401 || error.status === 419)
                ) {
                    // Session expired: keep it (it's on the phone) and ask them to sign in.
                    this.#next ??= payload;
                    this.status = 'signin';
                } else {
                    // Network trouble: keep it and try again when back online.
                    this.#next ??= payload;
                    this.status =
                        error instanceof HttpError && error.status === 0
                            ? 'offline'
                            : 'error';
                }
            } finally {
                this.#inflight = null;
            }
        })();

        await this.#inflight;

        // Re-read: the request above has moved the status on.
        const after = this.status as SaveStatus;

        if (this.#next && after === 'pending') {
            return this.flush();
        }

        return after === 'saved';
    }

    #key(payload: Record<string, unknown>): string {
        const { signature: _signature, ...rest } = payload;

        return JSON.stringify(rest);
    }

    #retry = (): void => {
        if (this.#next) {
            void this.flush();
        }
    };

    destroy(): void {
        clearTimeout(this.#timer);
        window.removeEventListener('online', this.#retry);
    }
}
