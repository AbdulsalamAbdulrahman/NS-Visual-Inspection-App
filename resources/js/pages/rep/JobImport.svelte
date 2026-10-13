<script lang="ts" module>
    export { default as layout } from '@/layouts/RepLayout.svelte';
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import CsvImport from '@/components/import/CsvImport.svelte';
    import { index, template } from '@/routes/rep/jobs';
    import { confirm, discard, upload } from '@/routes/rep/jobs/import';
    import ArrowBack from '~icons/ms/arrow-back';

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

    const urls = { template: template.url(), upload: upload.url(), confirm: confirm.url(), discard: discard.url() };
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

    <CsvImport
        {fileName}
        {rows}
        {columns}
        {maxRows}
        {urls}
        noun={['job', 'jobs']}
        verb="Create"
        rowMeaning="customer"
        headers={['OWNER', 'ADDRESS', 'AREA', 'BUILDING', 'CONTRACTOR']}
        cols="grid-cols-[56px_minmax(0,1fr)_minmax(0,1.2fr)_100px_minmax(0,1fr)_minmax(0,1fr)_minmax(0,1.6fr)]"
    >
        {#snippet guide()}
            <b>area_office</b> is one of your area offices; <b>purpose</b> and <b>building_type</b> as in the form (e.g. Residential, Duplex);
            <b>contractor_email</b> is the contractor’s sign-in email. Leave <b>form74_last</b> empty for a single building.
        {/snippet}
        {#snippet cells(row: Row)}
            <span role="cell" class="flex min-w-0 flex-col">
                <b class="truncate">{row.owner ?? '—'}</b>
                <span class="truncate font-mono text-xs text-mut">{row.form74 ?? ''}</span>
            </span>
            <span role="cell" class="truncate text-mut">{row.address ?? '—'}</span>
            <span role="cell">{row.area ?? '—'}</span>
            <span role="cell" class="truncate">{row.building ?? '—'}</span>
            <span role="cell" class="truncate">{row.contractor ?? '—'}</span>
        {/snippet}
    </CsvImport>
</div>
