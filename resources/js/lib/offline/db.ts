import { openDB, type DBSchema, type IDBPDatabase } from 'idb';
import type { Draft, FormOptions, Inspector } from '@/lib/inspection/types';

/**
 * Everything kept on the phone so contractors can work without network:
 * draft copies, attachments waiting to upload, and what the form needs to
 * open (areas, options, inspector). Rows carry the user id; sign-out clears
 * the whole database.
 */

/** A draft as last edited on this phone. */
export type LocalDraft = {
    uuid: string;
    userId: string;
    /** Same shape as the form's Draft (snake_case, as the server sends it). */
    draft: Draft;
    step: number;
    /** When it was last changed here (ms). */
    updatedAt: number;
    /** Changed here and not yet saved on the server. */
    dirty: boolean;
    /** A new signature PNG still to send (undefined = nothing pending). */
    signature?: string | null;
    /** Created on this phone and never seen by the server yet. */
    localOnly: boolean;
};

/** A file waiting for network. */
export type QueuedUpload = {
    id: string;
    userId: string;
    inspectionUuid: string;
    type: 'layout' | 'photo' | 'calibration';
    name: string;
    /** Already compressed; EXIF was read before compression. */
    file: Blob;
    meta: Record<string, string | number | null>;
    createdAt: number;
    /** Last error that isn't "no network" (e.g. a validation message). */
    error: string | null;
};

/** Props the form needs that are the same for every draft. */
export type FormBootstrap = {
    areas: { id: number; name: string }[];
    options: FormOptions;
    inspector: Inspector;
    fee: { amount: string; effectiveFrom: string } | null;
    /** Empty draft in the server's shape, for starting new inspections offline. */
    blankDraft: Draft;
    savedAt: number;
};

interface OfflineSchema extends DBSchema {
    drafts: { key: string; value: LocalDraft; indexes: { byUser: string } };
    uploads: {
        key: string;
        value: QueuedUpload;
        indexes: { byInspection: string };
    };
    meta: { key: string; value: { key: string; value: unknown } };
}

let connection: Promise<IDBPDatabase<OfflineSchema>> | null = null;

export function offlineSupported(): boolean {
    return typeof indexedDB !== 'undefined';
}

function db(): Promise<IDBPDatabase<OfflineSchema>> {
    connection ??= openDB<OfflineSchema>('kens-offline', 1, {
        upgrade(database) {
            database
                .createObjectStore('drafts', { keyPath: 'uuid' })
                .createIndex('byUser', 'userId');
            database
                .createObjectStore('uploads', { keyPath: 'id' })
                .createIndex('byInspection', 'inspectionUuid');
            database.createObjectStore('meta', { keyPath: 'key' });
        },
    });

    return connection;
}

// ── Drafts ──────────────────────────────────────────────────────────────────

export async function getLocalDraft(
    uuid: string,
    userId: string,
): Promise<LocalDraft | undefined> {
    const row = await (await db()).get('drafts', uuid);

    return row?.userId === userId ? row : undefined;
}

export async function putLocalDraft(row: LocalDraft): Promise<void> {
    await (await db()).put('drafts', row);
}

export async function listLocalDrafts(userId: string): Promise<LocalDraft[]> {
    return (await db()).getAllFromIndex('drafts', 'byUser', userId);
}

/** After a successful server save: keep the copy for offline use, but clean. */
export async function markDraftSynced(
    uuid: string,
    sentAt: number,
): Promise<void> {
    const database = await db();
    const row = await database.get('drafts', uuid);

    // Edited again while the request was in flight: still dirty.
    if (row && row.updatedAt <= sentAt) {
        await database.put('drafts', {
            ...row,
            dirty: false,
            localOnly: false,
            signature: undefined,
        });
    } else if (row) {
        await database.put('drafts', { ...row, localOnly: false });
    }
}

export async function deleteLocalDraft(uuid: string): Promise<void> {
    await (await db()).delete('drafts', uuid);
}

// ── Upload queue ────────────────────────────────────────────────────────────

export async function queueUpload(item: QueuedUpload): Promise<void> {
    await (await db()).put('uploads', item);
}

export async function listUploads(
    userId: string,
    inspectionUuid?: string,
): Promise<QueuedUpload[]> {
    const database = await db();
    const rows = inspectionUuid
        ? await database.getAllFromIndex(
              'uploads',
              'byInspection',
              inspectionUuid,
          )
        : await database.getAll('uploads');

    return rows
        .filter((r) => r.userId === userId)
        .sort((a, b) => a.createdAt - b.createdAt);
}

export async function updateUpload(item: QueuedUpload): Promise<void> {
    await (await db()).put('uploads', item);
}

export async function removeUpload(id: string): Promise<void> {
    await (await db()).delete('uploads', id);
}

// ── Form bootstrap ──────────────────────────────────────────────────────────

export async function saveBootstrap(
    userId: string,
    bootstrap: Omit<FormBootstrap, 'savedAt'>,
): Promise<void> {
    await (
        await db()
    ).put('meta', {
        key: `bootstrap:${userId}`,
        value: { ...bootstrap, savedAt: Date.now() },
    });
}

export async function getBootstrap(
    userId: string,
): Promise<FormBootstrap | null> {
    const row = await (await db()).get('meta', `bootstrap:${userId}`);

    return (row?.value as FormBootstrap | undefined) ?? null;
}

// ── Sign-out ────────────────────────────────────────────────────────────────

/** Drafts and files on this phone that the server hasn't got yet. */
export async function unsyncedCount(userId: string): Promise<number> {
    if (!offlineSupported()) {
        return 0;
    }

    const drafts = (await listLocalDrafts(userId)).filter(
        (d) => d.dirty,
    ).length;
    const uploads = (await listUploads(userId)).length;

    return drafts + uploads;
}

/** Remove everything this app stored on the device (IndexedDB and the page caches). */
export async function clearOfflineData(): Promise<void> {
    if (offlineSupported()) {
        const database = await db();
        await Promise.all([
            database.clear('drafts'),
            database.clear('uploads'),
            database.clear('meta'),
        ]);
    }

    if ('caches' in window) {
        const names = await caches.keys();
        await Promise.all(
            names
                .filter((n) => n.startsWith('kens-pages'))
                .map((n) => caches.delete(n)),
        );
    }
}
