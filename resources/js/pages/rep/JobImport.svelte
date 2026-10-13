<script lang="ts" module>
    export { default as layout } from '@/layouts/RepLayout.svelte';
</script>

<script lang="ts">
    import { Link, router, useForm } from '@inertiajs/svelte';
    import Button from '@/components/Button.svelte';
    import StatusPill from '@/components/StatusPill.svelte';
    import { index, template } from '@/routes/rep/jobs';
    import { confirm, discard, upload } from '@/routes/rep/jobs/import';
    import ArrowBack from '~icons/ms/arrow-back';
    import Download from '~icons/ms/download';
    import ErrorIcon from '~icons/ms/error';
    import UploadFile from '~icons/ms/upload-file';

    type Row = {
        line: number;
        owner: string | null;
        address: string | null;
        phone: string | null;
        area: string | null;
        building: string | null;
        form74: string | null;
        contractor: string | null;
        errors: string[];
    };

    type Props = { fileName: string | null; rows: Row[]; columns: string[]; maxRows: number };

    let { fileName, rows, columns, maxRows }: Props = $props();

    const form = useForm({ file: null as File | null });
    let confirming = $state(false);

    const valid = $derived(rows.filter((r) => r.errors.length === 0).length);
    const invalid = $derived(rows.length - valid);

    function pickFile(event: Event & { currentTarget: HTMLInputElement }): void {
        const input = event.currentTarget;
        form.file = input.files?.[0] ?? null;

        if (form.file) {
            // Clear the input so picking the same (fixed) file again uploads it.
            form.post(upload.url(), { forceFormData: true, preserveScroll: true, onFinish: () => (input.value = '') });
        }
    }

    function create(): void {
        confirming = true;
        router.post(confirm.url(), {}, { onFinish: () => (confirming = false) });
    }

    function startOver(): void {
        router.delete(discard.url(), { preserveScroll: true });
    }

    const cols = 'grid-cols-[56px_minmax(0,1fr)_minmax(0,1.2fr)_100px_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.6fr)]';
</script>

<svelte:head>
    <title>Upload jobs · KENS</title>
</svelte:head>

<header class="sticky top-0 z-20 flex items-center gap-1 border-b border-line bg-sf px-2 pt-[calc(4px+env(safe-area-inset-top))] pb-1 md:hidden">
    <Link href={index.url()} class="flex size-12 items-center justify-center rounded-xl text-ink" aria-label="Back to jobs">
        <ArrowBack class="size-[22px]" />
    </Link>
    <h1 class="flex-1 text-[18px] font-bold">Upload jobs</h1>
</header>

<div class="mx-auto flex w-full max-w-[1200px] flex-col gap-5 px-4 py-4 md:px-6 lg:px-10 lg:py-8">
    <div class="hidden flex-col gap-1 md:flex">
        <Link href={index.url()} class="flex items-center gap-1 text-sm font-semibold text-mut no-underline"><ArrowBack class="size-4" />Jobs</Link>
        <h1 class="text-[28px] font-extrabold">Upload jobs from a spreadsheet</h1>
    </div>

    {#if rows.length === 0}
        <section class="flex max-w-[720px] flex-col gap-4 rounded-[20px] border border-line bg-sf p-4 lg:p-6">
            <ol class="flex flex-col gap-3 text-[15px]">
                <li class="flex gap-2.5">
                    <span class="flex size-7 flex-none items-center justify-center rounded-full bg-sf2 font-mono text-[13px] font-semibold">1</span>
                    <span>Download the template and fill one row per customer (up to {maxRows}). Save it as <b>CSV</b>.</span>
                </li>
                <li class="flex gap-2.5">
                    <span class="flex size-7 flex-none items-center justify-center rounded-full bg-sf2 font-mono text-[13px] font-semibold">2</span>
                    <span>
                        <b>area_office</b> is one of your area offices; <b>purpose</b> and <b>building_type</b> as in the form (e.g. Residential, Duplex);
                        <b>contractor_email</b> is the contractor’s sign-in email. Leave <b>form74_last</b> empty for a single building.
                    </span>
                </li>
                <li class="flex gap-2.5">
                    <span class="flex size-7 flex-none items-center justify-center rounded-full bg-sf2 font-mono text-[13px] font-semibold">3</span>
                    <span>Upload it. You’ll see every row checked before anything is saved.</span>
                </li>
            </ol>
            <p class="rounded-xl bg-sf2 px-3.5 py-2.5 font-mono text-[13px] break-words text-mut">{columns.join(', ')}</p>
            <div class="flex flex-col gap-2.5 sm:flex-row">
                <Button href={template.url()} external variant="outline" size="md"><Download />Download template</Button>
                <label class={['flex h-[52px] cursor-pointer items-center justify-center gap-2 rounded-xl bg-pri px-5 font-bold text-on-pri', form.processing && 'opacity-60']}>
                    <UploadFile class="size-[22px]" />{form.processing ? 'Checking…' : 'Upload CSV'}
                    <input type="file" accept=".csv,text/csv" class="sr-only" onchange={pickFile} disabled={form.processing} />
                </label>
            </div>
            {#if form.errors.file}
                <p class="flex items-start gap-1.5 text-sm font-semibold text-bad" role="alert"><ErrorIcon class="size-[18px] flex-none" />{form.errors.file}</p>
            {/if}
        </section>
    {:else}
        <section class="flex flex-wrap items-center gap-3 rounded-[20px] border border-line bg-sf p-4 lg:p-5" aria-live="polite">
            <div class="flex min-w-0 flex-1 flex-col gap-0.5">
                <b class="truncate text-[15px]">{fileName}</b>
                <span class="text-sm text-mut">
                    <span class="font-mono">{valid}</span> ready{#if invalid > 0} · <span class="font-mono text-bad">{invalid}</span> with problems (skipped){/if}
                </span>
            </div>
            <Button variant="ghost" size="sm" onclick={startOver} disabled={confirming}>Upload another file</Button>
            <Button size="sm" onclick={create} disabled={valid === 0 || confirming}>
                Create {valid} job{valid === 1 ? '' : 's'}
            </Button>
        </section>

        {#if invalid > 0}
            <p class="text-sm text-mut">Rows with problems are skipped. Fix them in the spreadsheet and upload just those rows again.</p>
        {/if}

        <div class="overflow-hidden rounded-2xl border border-line bg-sf" role="table" aria-label="Rows in the file">
            <div role="row" class="hidden gap-3 border-b border-line bg-sf2 px-5 py-3 text-xs font-bold tracking-[0.06em] text-mut lg:grid {cols}">
                <span role="columnheader">ROW</span>
                <span role="columnheader">OWNER</span>
                <span role="columnheader">ADDRESS</span>
                <span role="columnheader">AREA</span>
                <span role="columnheader">BUILDING</span>
                <span role="columnheader">CONTRACTOR</span>
                <span role="columnheader">CHECK</span>
            </div>
            {#each rows as row (row.line)}
                <div role="row" class={['flex flex-col gap-1 border-b border-line px-4 py-3 text-sm last:border-b-0 lg:grid lg:items-start lg:gap-3 lg:px-5', cols, row.errors.length && 'bg-bad-bg/40']}>
                    <span role="cell" class="font-mono text-mut"><span class="lg:hidden">Row </span>{row.line}</span>
                    <span role="cell" class="flex min-w-0 flex-col">
                        <b class="truncate">{row.owner ?? '—'}</b>
                        <span class="truncate font-mono text-xs text-mut">{row.form74 ?? ''}</span>
                    </span>
                    <span role="cell" class="truncate text-mut">{row.address ?? '—'}</span>
                    <span role="cell">{row.area ?? '—'}</span>
                    <span role="cell" class="truncate">{row.building ?? '—'}</span>
                    <span role="cell" class="truncate">{row.contractor ?? '—'}</span>
                    <span role="cell" class="flex flex-col gap-1">
                        {#if row.errors.length === 0}
                            <StatusPill tone="ok" label="Ready" class="self-start" />
                        {:else}
                            {#each row.errors as message, i (i)}
                                <span class="flex items-start gap-1 font-semibold text-bad"><ErrorIcon class="mt-px size-4 flex-none" />{message}</span>
                            {/each}
                        {/if}
                    </span>
                </div>
            {/each}
        </div>
    {/if}
</div>
