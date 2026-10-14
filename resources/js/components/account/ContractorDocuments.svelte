<script lang="ts" module>
    export type LicenceStatus = {
        state: 'valid' | 'expiring' | 'expired' | 'missing';
        label: string;
        expiresAt: string | null;
        daysLeft: number | null;
        missing: string[];
    };

    export type ContractorDocumentsData = {
        status: LicenceStatus;
        /** Y-m-d, for the date input. */
        expiresOn: string | null;
        licenceUrl: string | null;
        licenceIsPdf: boolean;
        passportUrl: string | null;
        signatureUrl: string | null;
        storeUrl: string;
    };
</script>

<script lang="ts">
    import { page, router } from '@inertiajs/svelte';
    import Button from '@/components/Button.svelte';
    import SignatureField from '@/components/inspection/SignatureField.svelte';
    import { compressImage } from '@/lib/inspection/photos';
    import Badge from '~icons/ms/badge';
    import CheckCircle from '~icons/ms/check-circle';
    import ErrorIcon from '~icons/ms/error';
    import PictureAsPdf from '~icons/ms/picture-as-pdf';
    import UploadFile from '~icons/ms/upload-file';
    import Warning from '~icons/ms/warning';

    let { documents }: { documents: ContractorDocumentsData } = $props();

    const errors = $derived((page.props.errors ?? {}) as Record<string, string>);
    const status = $derived(documents.status);
    const today = new Date().toISOString().slice(0, 10);

    let busy = $state<string | null>(null);
    // svelte-ignore state_referenced_locally
    let expiresOn = $state(documents.expiresOn ?? '');
    let drawnSignature = $state<string | null>(null);
    let drawing = $state(false);

    function send(field: string, data: Record<string, File | string>): void {
        router.post(documents.storeUrl, data, {
            preserveScroll: true,
            forceFormData: true,
            onStart: () => (busy = field),
            onFinish: () => (busy = null),
            onSuccess: () => {
                if (field === 'signature') {
                    drawing = false;
                    drawnSignature = null;
                }
            },
        });
    }

    async function picked(field: 'licence' | 'passport' | 'signature', event: Event): Promise<void> {
        const input = event.currentTarget as HTMLInputElement;
        const file = input.files?.[0];
        input.value = '';

        if (file) {
            send(field, { [field]: field === 'signature' ? file : await compressImage(file) });
        }
    }

    const tone = { valid: 'bg-ok-bg text-ok', expiring: 'bg-imp-bg text-imp', expired: 'bg-bad-bg text-bad', missing: 'bg-imp-bg text-imp' } as const;
</script>

{#snippet uploadButton(field: 'licence' | 'passport' | 'signature', label: string, accept: string)}
    <label class={['flex h-11 cursor-pointer items-center justify-center gap-1.5 rounded-xl border-[1.5px] border-line bg-sf px-3.5 text-sm font-bold', busy === field && 'opacity-60']}>
        <UploadFile class="size-5" />{busy === field ? 'Uploading…' : label}
        <input type="file" class="sr-only" {accept} disabled={busy !== null} onchange={(e) => picked(field, e)} />
    </label>
{/snippet}

<section class="flex flex-col rounded-2xl border border-line bg-sf" aria-labelledby="documents-heading">
    <h2 id="documents-heading" class="mono-caps flex items-center gap-1.5 border-b border-line px-4 py-3 tracking-[0.05em] text-mut">
        <Badge class="size-4" />Licence &amp; documents
    </h2>

    <!-- Status: icon + text, never colour alone -->
    <div class={['flex items-start gap-2.5 px-4 py-3', tone[status.state]]} role="status">
        {#if status.state === 'valid'}<CheckCircle class="size-5 flex-none" />{:else if status.state === 'expired'}<ErrorIcon class="size-5 flex-none" />{:else}<Warning class="size-5 flex-none" />{/if}
        <span class="flex flex-col gap-0.5 text-sm">
            <b>{status.label}</b>
            {#if status.state === 'expired'}
                <span class="text-ink">You can’t start, pay for or resubmit inspections until you upload the renewed licence and its new expiry date.</span>
            {:else if status.missing.length > 0}
                <span class="text-ink">Still needed: {status.missing.join(', ')}.</span>
            {/if}
        </span>
    </div>

    <!-- Licence copy + expiry -->
    <div class="flex flex-col gap-3 border-t border-line px-4 py-3.5">
        <div class="flex flex-wrap items-center gap-3">
            <span class="flex min-w-0 flex-1 flex-col">
                <b class="text-base">Licence copy</b>
                {#if documents.licenceUrl}
                    <a href={documents.licenceUrl} target="_blank" rel="noopener" class="flex items-center gap-1 text-sm font-semibold text-brand">
                        {#if documents.licenceIsPdf}<PictureAsPdf class="size-4" />{/if}View uploaded licence
                    </a>
                {:else}
                    <span class="text-sm text-mut">PDF or photo, up to 5 MB</span>
                {/if}
            </span>
            {@render uploadButton('licence', documents.licenceUrl ? 'Replace' : 'Upload', 'application/pdf,image/*')}
        </div>
        {#if errors.licence}<span class="text-sm font-semibold text-bad" role="alert">{errors.licence}</span>{/if}

        <form
            class="flex flex-wrap items-end gap-2.5"
            onsubmit={(e) => {
                e.preventDefault();
                send('expiry', { licence_expires_at: expiresOn });
            }}
        >
            <label class="flex min-w-[180px] flex-1 flex-col gap-1.5">
                <span class="text-sm font-semibold">Licence expiry date</span>
                <input
                    type="date"
                    min={today}
                    bind:value={expiresOn}
                    required
                    aria-invalid={!!errors.licence_expires_at || undefined}
                    class="h-12 rounded-xl border-[1.5px] border-line bg-sf px-3 font-mono text-base"
                />
            </label>
            <Button type="submit" size="sm" class="h-12" disabled={busy !== null || !expiresOn || expiresOn === documents.expiresOn}>
                {busy === 'expiry' ? 'Saving…' : 'Save date'}
            </Button>
        </form>
        {#if errors.licence_expires_at}<span class="text-sm font-semibold text-bad" role="alert">{errors.licence_expires_at}</span>{/if}
        <span class="text-[13px] text-mut">We email you a month before it expires. After the expiry date, new work is blocked until you upload the renewed licence.</span>
    </div>

    <!-- Passport photograph -->
    <div class="flex flex-col gap-2 border-t border-line px-4 py-3.5">
        <div class="flex items-center gap-3">
            {#if documents.passportUrl}
                <img src={documents.passportUrl} alt="Your passport photograph" class="size-16 flex-none rounded-lg border border-line object-cover" />
            {/if}
            <span class="flex min-w-0 flex-1 flex-col">
                <b class="text-base">Passport photograph</b>
                <span class="text-sm text-mut">{documents.passportUrl ? 'Uploaded' : 'A clear, recent photo of your face'}</span>
            </span>
            {@render uploadButton('passport', documents.passportUrl ? 'Replace' : 'Upload', 'image/*')}
        </div>
        {#if errors.passport}<span class="text-sm font-semibold text-bad" role="alert">{errors.passport}</span>{/if}
    </div>

    <!-- Signature -->
    <div class="flex flex-col gap-2.5 border-t border-line px-4 py-3.5">
        <div class="flex items-center gap-3">
            <span class="flex min-w-0 flex-1 flex-col">
                <b class="text-base">Signature</b>
                <span class="text-sm text-mut">Added to each report when you start a job; you can redraw it there.</span>
            </span>
        </div>
        {#if documents.signatureUrl && !drawing}
            <img src={documents.signatureUrl} alt="Your signature on file" class="h-20 self-start rounded-lg border border-line bg-white px-3" />
        {/if}
        {#if drawing}
            <SignatureField savedUrl={null} onchange={(url) => (drawnSignature = url)} error={errors.signature_drawn ?? null} />
            <div class="grid grid-cols-2 gap-2.5 sm:flex">
                <Button variant="outline" size="sm" class="h-11" onclick={() => ((drawing = false), (drawnSignature = null))}>Cancel</Button>
                <Button size="sm" class="h-11" disabled={!drawnSignature || busy !== null} onclick={() => drawnSignature && send('signature', { signature_drawn: drawnSignature })}>
                    {busy === 'signature' ? 'Saving…' : 'Save signature'}
                </Button>
            </div>
        {:else}
            <div class="grid grid-cols-2 gap-2.5 sm:flex">
                <Button variant="outline" size="sm" class="h-11 border-[1.5px]" onclick={() => (drawing = true)}>Draw</Button>
                {@render uploadButton('signature', 'Upload image', 'image/png,image/jpeg')}
            </div>
        {/if}
        {#if errors.signature}<span class="text-sm font-semibold text-bad" role="alert">{errors.signature}</span>{/if}
    </div>
</section>
