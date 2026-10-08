import { HttpError, sendJson, upload } from '@/lib/http';
import { draftPayload } from '@/lib/inspection/autosave.svelte';
import type { Attachment } from '@/lib/inspection/types';
import {
    listLocalDrafts,
    listUploads,
    markDraftSynced,
    offlineSupported,
    queueUpload,
    removeUpload,
    updateUpload,
    type QueuedUpload,
} from './db';

/** Fired on window when a queued file has uploaded, so an open form can show it. */
export const ATTACHMENT_UPLOADED = 'kens:attachment-uploaded';

export type AttachmentUploadedDetail = {
    inspectionUuid: string;
    attachment: Attachment;
};

/**
 * Sends work done offline once the network is back: unsynced drafts first
 * (the PUT upsert creates drafts started on the phone), then queued files in
 * the order they were added. One run at a time; stops at the first sign of
 * no network or an expired session and tries again on the next `online`.
 */
class OfflineSync {
    /** Files waiting to upload for the signed-in user (drives the "Waiting" tiles). */
    queued = $state<QueuedUpload[]>([]);
    running = $state(false);
    /** The session expired mid-sync; shown until they sign in again. */
    needsSignIn = $state(false);

    #userId: string | null = null;
    /** The draft open in the form saves itself (Autosave); skip it here. */
    #openDraft: string | null = null;

    init(userId: string): void {
        if (!offlineSupported() || this.#userId === userId) {
            return;
        }

        this.#userId = userId;
        window.addEventListener('online', () => void this.run());
        void this.#reload().then(() => this.run());
    }

    setOpenDraft(uuid: string | null): void {
        this.#openDraft = uuid;
    }

    queuedFor(
        inspectionUuid: string,
        type: QueuedUpload['type'],
    ): QueuedUpload[] {
        return this.queued.filter(
            (q) => q.inspectionUuid === inspectionUuid && q.type === type,
        );
    }

    async enqueue(
        item: Omit<QueuedUpload, 'userId' | 'createdAt' | 'error'>,
    ): Promise<void> {
        if (!this.#userId) {
            return;
        }

        await queueUpload({
            ...item,
            userId: this.#userId,
            createdAt: Date.now(),
            error: null,
        });
        await this.#reload();
        void this.run();
    }

    async discard(id: string): Promise<void> {
        await removeUpload(id);
        await this.#reload();
    }

    async run(): Promise<void> {
        if (!this.#userId || this.running || !navigator.onLine) {
            return;
        }

        this.running = true;

        try {
            if ((await this.#syncDrafts()) && (await this.#syncUploads())) {
                this.needsSignIn = false;
            }
        } finally {
            this.running = false;
            await this.#reload();
        }
    }

    /** @returns false when it had to stop (no network, signed out). */
    async #syncDrafts(): Promise<boolean> {
        const drafts = (await listLocalDrafts(this.#userId!)).filter(
            (d) => d.dirty && d.uuid !== this.#openDraft,
        );

        for (const local of drafts) {
            const payload: Record<string, unknown> = {
                ...draftPayload(local.draft),
                current_step: local.step,
            };

            if (local.signature !== undefined) {
                payload.signature = local.signature;
            }

            const sentAt = Date.now();

            try {
                await sendJson(
                    'PUT',
                    `/inspections/${local.uuid}/draft`,
                    payload,
                );
                await markDraftSynced(local.uuid, sentAt);
            } catch (error) {
                if (!this.#keepGoing(error)) {
                    return false;
                }
            }
        }

        return true;
    }

    async #syncUploads(): Promise<boolean> {
        const local = await listLocalDrafts(this.#userId!);
        const notOnServer = new Set(
            local.filter((d) => d.localOnly).map((d) => d.uuid),
        );

        for (const item of await listUploads(this.#userId!)) {
            // The draft must exist on the server first; it syncs before uploads.
            if (notOnServer.has(item.inspectionUuid) || item.error) {
                continue;
            }

            const form = new FormData();
            form.append('type', item.type);
            form.append('file', item.file, item.name);
            Object.entries(item.meta).forEach(
                ([k, v]) => v !== null && form.append(k, String(v)),
            );

            try {
                const attachment = await upload<Attachment>(
                    `/inspections/${item.inspectionUuid}/attachments`,
                    form,
                    () => {},
                );
                await removeUpload(item.id);
                window.dispatchEvent(
                    new CustomEvent<AttachmentUploadedDetail>(
                        ATTACHMENT_UPLOADED,
                        {
                            detail: {
                                inspectionUuid: item.inspectionUuid,
                                attachment,
                            },
                        },
                    ),
                );
            } catch (error) {
                if (error instanceof HttpError && error.status === 422) {
                    await updateUpload({
                        ...item,
                        error:
                            Object.values(error.errors)[0] ??
                            'Upload rejected.',
                    });
                } else if (
                    error instanceof HttpError &&
                    (error.status === 403 || error.status === 404)
                ) {
                    await updateUpload({
                        ...item,
                        error: 'This report can no longer take files.',
                    });
                } else if (!this.#keepGoing(error)) {
                    return false;
                }
            }
        }

        return true;
    }

    /** Validation problems are per item; no network or no session stops the run. */
    #keepGoing(error: unknown): boolean {
        if (
            error instanceof HttpError &&
            (error.status === 401 || error.status === 419)
        ) {
            this.needsSignIn = true;

            return false;
        }

        return (
            error instanceof HttpError &&
            error.status >= 400 &&
            error.status < 500
        );
    }

    async #reload(): Promise<void> {
        this.queued = this.#userId ? await listUploads(this.#userId) : [];
    }
}

export const offlineSync = new OfflineSync();
