<script lang="ts">
    import { fileSize } from '@/lib/format';
    import { HttpError, sendJson, upload } from '@/lib/http';
    import { compressImage, readPhotoMeta } from '@/lib/inspection/photos';
    import type { Attachment, AttachmentType } from '@/lib/inspection/types';
    import type { QueuedUpload } from '@/lib/offline/db';
    import { offlineSync } from '@/lib/offline/sync.svelte';
    import { onDestroy } from 'svelte';
    import CheckCircle from '~icons/ms/check-circle';
    import Close from '~icons/ms/close';
    import ErrorIcon from '~icons/ms/error';
    import PhotoCamera from '~icons/ms/photo-camera';
    import PhoneAndroid from '~icons/ms/phone-android';
    import PhotoLibrary from '~icons/ms/photo-library';
    import UploadFile from '~icons/ms/upload-file';

    type Props = {
        inspectionUuid: string;
        type: AttachmentType;
        title: string;
        hint?: string;
        required?: boolean;
        /** "photos": thumbnail grid with camera + gallery tiles (photos of DB & earthing). */
        layout: 'photos' | 'files';
        max: number;
        attachments: Attachment[];
        error?: string | null;
        onadded: (attachment: Attachment) => void;
        onremoved: (uuid: string) => void;
    };

    let { inspectionUuid, type, title, hint, required = false, layout, max, attachments, error = null, onadded, onremoved }: Props =
        $props();

    type Pending = { id: string; name: string; preview: string | null; progress: number; failed: string | null; file: File };

    let pending = $state<Pending[]>([]);
    let removing = $state<string | null>(null);
    let removeError = $state<string | null>(null);

    const mine = $derived(attachments.filter((a) => a.type === type));
    /** Files kept on the phone until there's network (uploaded by offlineSync). */
    const queued = $derived(offlineSync.queuedFor(inspectionUuid, type));
    const full = $derived(mine.length + queued.length + pending.filter((p) => !p.failed).length >= max);

    // Thumbnails for queued photos; created once per item, revoked on leave.
    const previews = new Map<string, string>();
    function previewOf(item: QueuedUpload): string | null {
        if (!item.file.type.startsWith('image/')) {
            return null;
        }

        if (!previews.has(item.id)) {
            previews.set(item.id, URL.createObjectURL(item.file));
        }

        return previews.get(item.id) ?? null;
    }
    onDestroy(() => previews.forEach((url) => URL.revokeObjectURL(url)));

    /** No network: keep the (compressed) file on the phone and upload it later. */
    async function keepForLater(item: Pending, file: File, meta: Record<string, string | number | null>): Promise<void> {
        await offlineSync.enqueue({ id: item.id, inspectionUuid, type, name: file.name, file, meta });
        discard(item.id);
    }
    const headingId = $derived(`att-${type}`);

    async function add(files: FileList | null): Promise<void> {
        for (const original of Array.from(files ?? [])) {
            if (full) {
                break;
            }

            const item: Pending = {
                id: crypto.randomUUID(),
                name: original.name,
                preview: original.type.startsWith('image/') ? URL.createObjectURL(original) : null,
                progress: 0,
                failed: null,
                file: original,
            };
            pending.push(item);
            void send(item);
        }
    }

    async function send(item: Pending): Promise<void> {
        const entry = (): Pending | undefined => pending.find((p) => p.id === item.id);

        try {
            // EXIF first: compression strips it.
            const meta = item.file.type.startsWith('image/')
                ? await readPhotoMeta(item.file)
                : { exif_lat: null, exif_lng: null, taken_at: null };
            const file = await compressImage(item.file);

            if (!navigator.onLine) {
                await keepForLater(item, file, meta);

                return;
            }

            const form = new FormData();
            form.append('type', type);
            form.append('file', file, file.name);
            Object.entries(meta).forEach(([k, v]) => v !== null && form.append(k, String(v)));

            const saved = await upload<Attachment>(`/inspections/${inspectionUuid}/attachments`, form, (f) => {
                const e = entry();

                if (e) {
                    e.progress = f;
                }
            });

            onadded(saved);
            discard(item.id);
        } catch (err) {
            const e = entry();

            // Lost the connection mid-upload: queue it instead of failing.
            if (err instanceof HttpError && err.status === 0 && e) {
                const meta = item.file.type.startsWith('image/') ? await readPhotoMeta(item.file) : { exif_lat: null, exif_lng: null, taken_at: null };
                await keepForLater(e, await compressImage(item.file), meta);

                return;
            }

            if (e) {
                e.failed =
                    err instanceof HttpError && err.status === 0
                        ? 'No network. Try again when you’re online.'
                        : err instanceof HttpError
                          ? (Object.values(err.errors)[0] ?? 'Upload failed.')
                          : 'Couldn’t read this file.';
            }
        }
    }

    function retry(item: Pending): void {
        item.failed = null;
        item.progress = 0;
        void send(item);
    }

    function discard(id: string): void {
        const item = pending.find((p) => p.id === id);

        if (item?.preview) {
            URL.revokeObjectURL(item.preview);
        }

        pending = pending.filter((p) => p.id !== id);
    }

    async function remove(attachment: Attachment): Promise<void> {
        removing = attachment.uuid;
        removeError = null;

        try {
            await sendJson('DELETE', `/inspections/${inspectionUuid}/attachments/${attachment.uuid}`);
            onremoved(attachment.uuid);
        } catch (err) {
            removeError = err instanceof HttpError && err.status === 0 ? 'Removing a file needs network. Try again when you’re online.' : 'Couldn’t remove the file.';
        } finally {
            removing = null;
        }
    }

    function pick(event: Event & { currentTarget: HTMLInputElement }): void {
        void add(event.currentTarget.files);
        event.currentTarget.value = '';
    }

    const tile = 'flex aspect-square flex-col items-center justify-center gap-1 rounded-xl border-2 border-dashed text-[13px] font-bold';
    const fileButton = 'flex h-[52px] items-center justify-center gap-1.5 rounded-xl border-[1.5px] border-line bg-sf text-[15px] font-bold';
</script>

<section class="flex flex-col gap-2" aria-labelledby={headingId}>
    <div class="flex flex-col gap-0.5 px-1">
        <h2 id={headingId} class="text-base font-bold">
            {title}
            {#if required}<span class="text-bad" aria-hidden="true">*</span>{/if}
        </h2>
        {#if hint}<span class="text-[13px] text-mut">{hint}</span>{/if}
    </div>

    {#if layout === 'photos'}
        <ul class="grid grid-cols-3 gap-2">
            {#each mine as a (a.uuid)}
                <li class="relative aspect-square overflow-hidden rounded-xl bg-sf2">
                    <img src={a.url} alt={a.name} loading="lazy" class="size-full object-cover" />
                    <button
                        type="button"
                        class="absolute top-1 right-1 flex size-8 items-center justify-center rounded-full bg-black/60 text-white disabled:opacity-50"
                        aria-label="Remove {a.name}"
                        disabled={removing === a.uuid}
                        onclick={() => remove(a)}
                    >
                        <Close class="size-4" />
                    </button>
                </li>
            {/each}
            {#each queued as q (q.id)}
                {@const preview = previewOf(q)}
                <li class="relative aspect-square overflow-hidden rounded-xl bg-sf2">
                    {#if preview}<img src={preview} alt={q.name} class="size-full object-cover" />{/if}
                    <div class="absolute inset-0 flex flex-col justify-end gap-1 bg-[rgba(9,17,13,.55)] p-1.5 text-white">
                        {#if q.error}
                            <span class="text-xs font-bold">{q.error}</span>
                        {:else}
                            <span class="flex items-center gap-1 text-xs font-bold"><PhoneAndroid class="size-4" />Waiting for network</span>
                        {/if}
                    </div>
                    <button
                        type="button"
                        class="absolute top-1 right-1 flex size-8 items-center justify-center rounded-full bg-black/60 text-white"
                        aria-label="Remove {q.name}"
                        onclick={() => offlineSync.discard(q.id)}
                    >
                        <Close class="size-4" />
                    </button>
                </li>
            {/each}
            {#each pending as p (p.id)}
                <li class="relative aspect-square overflow-hidden rounded-xl bg-sf2">
                    {#if p.preview}<img src={p.preview} alt="" class="size-full object-cover" />{/if}
                    <div class="absolute inset-0 flex flex-col justify-end gap-1 bg-[rgba(9,17,13,.45)] p-1.5">
                        {#if p.failed}
                            <button type="button" class="text-left text-xs font-bold text-white underline" onclick={() => retry(p)}>
                                Failed · retry
                            </button>
                        {:else}
                            <span class="font-mono text-xs font-semibold text-white">{Math.round(p.progress * 100)}%</span>
                            <span class="h-[5px] rounded-full bg-white/35" role="progressbar" aria-valuenow={Math.round(p.progress * 100)} aria-valuemin={0} aria-valuemax={100} aria-label="Uploading {p.name}">
                                <span class="block h-full rounded-full bg-lime" style:width="{p.progress * 100}%"></span>
                            </span>
                        {/if}
                    </div>
                </li>
            {/each}
            {#if !full}
                <li>
                    <label class={[tile, 'cursor-pointer border-mid text-brand']}>
                        <PhotoCamera class="size-7" />Take photo
                        <input type="file" accept="image/*" capture="environment" class="sr-only" onchange={pick} />
                    </label>
                </li>
                <li>
                    <label class={[tile, 'cursor-pointer border-line text-ink']}>
                        <PhotoLibrary class="size-7" />Gallery
                        <input type="file" accept="image/*" multiple class="sr-only" onchange={pick} />
                    </label>
                </li>
            {/if}
        </ul>
    {:else}
        {#each mine as a (a.uuid)}
            <div class="flex items-center gap-3 rounded-[14px] border border-line bg-sf py-2.5 pr-1.5 pl-3">
                {#if a.isImage}
                    <img src={a.url} alt="" class="h-[52px] w-11 flex-none rounded-lg object-cover" />
                {:else}
                    <span class="flex h-[52px] w-11 flex-none items-center justify-center rounded-lg bg-bad-bg font-mono text-[11px] font-bold text-bad">PDF</span>
                {/if}
                <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                    <b class="truncate text-[15px]">{a.name}</b>
                    <span class="flex items-center gap-1 text-[13px] font-semibold text-ok"><CheckCircle class="size-4" />Uploaded · {fileSize(a.size)}</span>
                </div>
                <button
                    type="button"
                    class="flex size-11 items-center justify-center rounded-xl text-mut hover:bg-sf2 disabled:opacity-50"
                    aria-label="Remove {a.name}"
                    disabled={removing === a.uuid}
                    onclick={() => remove(a)}
                >
                    <Close class="size-6" />
                </button>
            </div>
        {/each}
        {#each queued as q (q.id)}
            <div class="flex items-center gap-3 rounded-[14px] border border-line bg-sf py-2.5 pr-1.5 pl-3">
                <span class="flex h-[52px] w-11 flex-none items-center justify-center rounded-lg bg-info-bg text-info"><PhoneAndroid class="size-6" /></span>
                <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                    <b class="truncate text-[15px]">{q.name}</b>
                    <span class={['text-[13px] font-semibold', q.error ? 'text-bad' : 'text-info']}>{q.error ?? 'On this phone · uploads when you’re online'}</span>
                </div>
                <button type="button" class="flex size-11 items-center justify-center rounded-xl text-mut hover:bg-sf2" aria-label="Remove {q.name}" onclick={() => offlineSync.discard(q.id)}>
                    <Close class="size-6" />
                </button>
            </div>
        {/each}
        {#each pending as p (p.id)}
            <div class="flex items-center gap-3 rounded-[14px] border border-line bg-sf py-2.5 pr-3 pl-3">
                <span class="flex h-[52px] w-11 flex-none items-center justify-center rounded-lg bg-sf2 font-mono text-[11px] font-bold text-mut">
                    {Math.round(p.progress * 100)}%
                </span>
                <div class="flex min-w-0 flex-1 flex-col gap-1">
                    <b class="truncate text-[15px]">{p.name}</b>
                    {#if p.failed}
                        <span class="text-[13px] font-semibold text-bad">{p.failed}
                            <button type="button" class="ml-1 underline" onclick={() => retry(p)}>Retry</button>
                            <button type="button" class="ml-1 underline" onclick={() => discard(p.id)}>Remove</button>
                        </span>
                    {:else}
                        <span class="h-[5px] rounded-full bg-sf2"><span class="block h-full rounded-full bg-mid" style:width="{p.progress * 100}%"></span></span>
                    {/if}
                </div>
            </div>
        {/each}
        {#if !full}
            <div class="grid grid-cols-2 gap-2">
                <label class={[fileButton, 'cursor-pointer']}>
                    <PhotoCamera class="size-5" />Camera
                    <input type="file" accept="image/*" capture="environment" class="sr-only" onchange={pick} />
                </label>
                <label class={[fileButton, 'cursor-pointer']}>
                    <UploadFile class="size-5" />Upload file
                    <input type="file" accept="application/pdf,image/*" class="sr-only" onchange={pick} />
                </label>
            </div>
        {/if}
    {/if}

    {#if removeError}
        <span class="flex items-center gap-1 px-1 text-sm font-semibold text-bad" role="alert"><ErrorIcon class="size-[18px]" />{removeError}</span>
    {/if}
    {#if error}
        <span class="flex items-center gap-1 px-1 text-sm font-semibold text-bad"><ErrorIcon class="size-[18px]" />{error}</span>
    {/if}
</section>
