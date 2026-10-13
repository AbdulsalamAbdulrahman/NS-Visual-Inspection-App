<script lang="ts" generics="T extends { line: number; errors: string[] }">
    import { router, useForm } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import Button from '@/components/Button.svelte';
    import StatusPill from '@/components/StatusPill.svelte';
    import Download from '~icons/ms/download';
    import ErrorIcon from '~icons/ms/error';
    import UploadFile from '~icons/ms/upload-file';

    /**
     * Upload a CSV made from a template, preview every row with its problems,
     * then confirm. The server keeps the checked rows in the session between
     * the upload and the confirm.
     */
    type Props = {
        fileName: string | null;
        rows: T[];
        columns: string[];
        maxRows: number;
        urls: { template: string; upload: string; confirm: string; discard: string };
        /** e.g. ['job', 'jobs'] */
        noun: [string, string];
        /** "Create" / "Register" */
        verb: string;
        /** What one row is (customer, contractor). */
        rowMeaning: string;
        /** Step 2: how to fill the columns. */
        guide: Snippet;
        /** Shown under the summary once a file is checked (e.g. who gets emailed). */
        note?: Snippet;
        headers: string[];
        /** Grid columns for the table (the first is the row number, the last the check). */
        cols: string;
        cells: Snippet<[T]>;
    };

    let { fileName, rows, columns, maxRows, urls, noun, verb, rowMeaning, guide, note, headers, cols, cells }: Props = $props();

    const form = useForm({ file: null as File | null });
    let confirming = $state(false);

    const valid = $derived(rows.filter((r) => r.errors.length === 0).length);
    const invalid = $derived(rows.length - valid);

    function pickFile(event: Event & { currentTarget: HTMLInputElement }): void {
        const input = event.currentTarget;
        form.file = input.files?.[0] ?? null;

        if (form.file) {
            // Clear the input so picking the same (fixed) file again uploads it.
            form.post(urls.upload, { forceFormData: true, preserveScroll: true, onFinish: () => (input.value = '') });
        }
    }

    function confirm(): void {
        confirming = true;
        router.post(urls.confirm, {}, { onFinish: () => (confirming = false) });
    }

    function startOver(): void {
        router.delete(urls.discard, { preserveScroll: true });
    }

    const step = 'flex size-7 flex-none items-center justify-center rounded-full bg-sf2 font-mono text-[13px] font-semibold';
</script>

{#if rows.length === 0}
    <section class="flex max-w-[720px] flex-col gap-4 rounded-[20px] border border-line bg-sf p-4 lg:p-6">
        <ol class="flex flex-col gap-3 text-[15px]">
            <li class="flex gap-2.5">
                <span class={step}>1</span>
                <span>Download the template and fill one row per {rowMeaning} (up to {maxRows}). Save it as <b>CSV</b>.</span>
            </li>
            <li class="flex gap-2.5">
                <span class={step}>2</span>
                <span>{@render guide()}</span>
            </li>
            <li class="flex gap-2.5">
                <span class={step}>3</span>
                <span>Upload it. You’ll see every row checked before anything is saved.</span>
            </li>
        </ol>
        <p class="rounded-xl bg-sf2 px-3.5 py-2.5 font-mono text-[13px] break-words text-mut">{columns.join(', ')}</p>
        <div class="flex flex-col gap-2.5 sm:flex-row">
            <Button href={urls.template} external variant="outline" size="md"><Download />Download template</Button>
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
        <Button size="sm" onclick={confirm} disabled={valid === 0 || confirming}>
            {verb} {valid} {valid === 1 ? noun[0] : noun[1]}
        </Button>
    </section>

    {#if invalid > 0}
        <p class="text-sm text-mut">Rows with problems are skipped. Fix them in the spreadsheet and upload just those rows again.</p>
    {/if}
    {@render note?.()}

    <div class="overflow-hidden rounded-2xl border border-line bg-sf" role="table" aria-label="Rows in the file">
        <div role="row" class="hidden gap-3 border-b border-line bg-sf2 px-5 py-3 text-xs font-bold tracking-[0.06em] text-mut lg:grid {cols}">
            <span role="columnheader">ROW</span>
            {#each headers as header (header)}
                <span role="columnheader">{header}</span>
            {/each}
            <span role="columnheader">CHECK</span>
        </div>
        {#each rows as row (row.line)}
            <div role="row" class={['flex flex-col gap-1 border-b border-line px-4 py-3 text-sm last:border-b-0 lg:grid lg:items-start lg:gap-3 lg:px-5', cols, row.errors.length && 'bg-bad-bg/40']}>
                <span role="cell" class="font-mono text-mut"><span class="lg:hidden">Row </span>{row.line}</span>
                {@render cells(row)}
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
