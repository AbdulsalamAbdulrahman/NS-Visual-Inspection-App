<script lang="ts" module>
    export { default as layout } from '@/layouts/AdminLayout.svelte';
</script>

<script lang="ts">
    import { Link, useForm } from '@inertiajs/svelte';
    import { onDestroy } from 'svelte';
    import Button from '@/components/Button.svelte';
    import Field from '@/components/form/Field.svelte';
    import { fieldBox } from '@/components/form/inputClasses';
    import admin from '@/routes/admin';
    import { update } from '@/routes/admin/certificate';
    import ArrowBack from '~icons/ms/arrow-back';
    import Info from '~icons/ms/info';
    import Upload from '~icons/ms/upload';

    type Props = {
        signatory: { name: string; title: string; signatureUrl: string | null };
        complete: boolean;
        pendingReview: number;
    };

    let { signatory, complete, pendingReview }: Props = $props();

    // svelte-ignore state_referenced_locally
    const form = useForm<{ name: string; title: string; signature: File | null }>({
        name: signatory.name,
        title: signatory.title,
        signature: null,
    });

    let preview = $state<string | null>(null);

    function pick(event: Event & { currentTarget: HTMLInputElement }): void {
        const file = event.currentTarget.files?.[0] ?? null;
        form.signature = file;

        if (preview) {
            URL.revokeObjectURL(preview);
        }

        preview = file ? URL.createObjectURL(file) : null;
    }

    onDestroy(() => {
        if (preview) {
            URL.revokeObjectURL(preview);
        }
    });

    function submit(event: SubmitEvent): void {
        event.preventDefault();
        form.post(update.url(), {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: () => {
                form.signature = null;
            },
        });
    }

    const shown = $derived(preview ?? signatory.signatureUrl);
</script>

<svelte:head>
    <title>Certificate signatory · KENS</title>
</svelte:head>

<header class="sticky top-0 z-20 flex items-center gap-1 border-b border-line bg-sf px-2 pt-[calc(4px+env(safe-area-inset-top))] pb-1 lg:hidden">
    <Link href={admin.more()} class="flex size-12 items-center justify-center rounded-xl text-ink" aria-label="Back to More">
        <ArrowBack class="size-[22px]" />
    </Link>
    <h1 class="flex-1 text-[18px] font-bold">Certificate signatory</h1>
</header>

<div class="flex flex-col gap-5 px-4 py-4 lg:px-10 lg:py-8">
    <div class="hidden flex-col gap-1 lg:flex">
        <h1 class="text-[28px] font-extrabold">Certificate signatory</h1>
        <p class="text-[15px] text-mut">Printed on the right of every certificate, next to the inspecting contractor's signature.</p>
    </div>

    {#if !complete}
        <div class="flex max-w-[1040px] gap-2.5 rounded-xl border border-imp bg-imp-bg px-4 py-3 text-sm text-imp" role="status">
            <Info class="size-5 flex-none" />
            <span class="text-ink">
                <b>Set this up before approving reports.</b>
                {pendingReview > 0 ? `${pendingReview} report${pendingReview === 1 ? ' is' : 's are'} waiting for review.` : ''}
            </span>
        </div>
    {/if}

    <div class="grid max-w-[1040px] gap-5 lg:grid-cols-2">
        <form class="flex flex-col gap-4 rounded-[20px] border border-line bg-sf p-5 lg:p-6" onsubmit={submit} novalidate>
            <Field id="sig-name" label="Full name" error={form.errors.name}>
                <input
                    id="sig-name"
                    bind:value={form.name}
                    maxlength="120"
                    autocomplete="off"
                    placeholder="e.g. Engr. Hauwa Abdullahi"
                    aria-invalid={!!form.errors.name || undefined}
                    class={fieldBox('md', !!form.errors.name, 'h-12 rounded-[10px] px-3 text-[15px] outline-none')}
                />
            </Field>
            <Field id="sig-title" label="Title" error={form.errors.title}>
                <input
                    id="sig-title"
                    bind:value={form.title}
                    maxlength="120"
                    autocomplete="off"
                    aria-invalid={!!form.errors.title || undefined}
                    class={fieldBox('md', !!form.errors.title, 'h-12 rounded-[10px] px-3 text-[15px] outline-none')}
                />
            </Field>
            <Field id="sig-file" label="Signature image" error={form.errors.signature} hint="A clear scan or photo of the signature on white paper. PNG or JPG, up to 2 MB.">
                <label
                    for="sig-file"
                    class={fieldBox('md', !!form.errors.signature, 'flex h-12 cursor-pointer items-center gap-2 rounded-[10px] px-3 text-[15px] font-semibold')}
                >
                    <Upload class="size-5 text-brand" />
                    <span class="truncate">{form.signature?.name ?? (signatory.signatureUrl ? 'Replace signature…' : 'Choose an image…')}</span>
                </label>
                <input id="sig-file" type="file" accept="image/png,image/jpeg,image/webp" class="sr-only" onchange={pick} />
            </Field>
            <p class="text-[13px] leading-[1.45] text-mut">
                Changes apply to certificates approved from now on. Certificates already issued keep the signatory they were approved with.
            </p>
            <Button type="submit" size="md" block class="h-12 rounded-xl" disabled={form.processing}>Save signatory</Button>
        </form>

        <!-- How it prints -->
        <section class="flex flex-col gap-3 rounded-[20px] border border-line bg-sf p-5 lg:p-6" aria-labelledby="sig-preview">
            <h2 id="sig-preview" class="eyebrow">On the certificate</h2>
            <div class="paper flex flex-1 flex-col items-center justify-end rounded-xl border border-[#B9D59A] px-6 py-6 text-center">
                {#if shown}
                    <img src={shown} alt="Signature preview" class="h-16 w-auto max-w-[240px] object-contain" />
                {:else}
                    <span class="flex h-16 items-center text-sm text-mut">No signature yet</span>
                {/if}
                <span class="w-full max-w-[260px] border-t border-dashed border-ink/60"></span>
                <b class="mt-1 text-[15px] text-[#09502E]">{form.name || 'Full name'}</b>
                <span class="text-[13px] font-semibold text-mut">{form.title || 'Title'} · Kaduna Electric</span>
            </div>
        </section>
    </div>
</div>
