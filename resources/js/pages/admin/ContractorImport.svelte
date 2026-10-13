<script lang="ts" module>
    export { default as layout } from '@/layouts/AdminLayout.svelte';
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import CsvImport from '@/components/import/CsvImport.svelte';
    import { index, template } from '@/routes/admin/contractors';
    import { confirm, discard, upload } from '@/routes/admin/contractors/import';
    import ArrowBack from '~icons/ms/arrow-back';
    import Mail from '~icons/ms/mail';

    type Row = {
        line: number;
        name: string | null;
        email: string | null;
        state: string | null;
        category: string | null;
        regNo: string | null;
        firm: string | null;
        errors: string[];
    };

    type Props = { fileName: string | null; rows: Row[]; columns: string[]; maxRows: number };

    let { fileName, rows, columns, maxRows }: Props = $props();

    const urls = { template: template.url(), upload: upload.url(), confirm: confirm.url(), discard: discard.url() };
</script>

<svelte:head>
    <title>Register contractors · KENS</title>
</svelte:head>

<header class="sticky top-0 z-20 flex items-center gap-1 border-b border-line bg-sf px-2 pt-[calc(4px+env(safe-area-inset-top))] pb-1 lg:hidden">
    <Link href={index.url()} class="flex size-12 items-center justify-center rounded-xl text-ink" aria-label="Back to contractors">
        <ArrowBack class="size-[22px]" />
    </Link>
    <h1 class="flex-1 text-[18px] font-bold">Register contractors</h1>
</header>

<div class="flex max-w-[1200px] flex-col gap-5 px-4 py-4 lg:px-10 lg:py-8">
    <div class="hidden flex-col gap-1 lg:flex">
        <Link href={index.url()} class="flex items-center gap-1 text-sm font-semibold text-mut no-underline"><ArrowBack class="size-4" />Contractors</Link>
        <h1 class="text-[28px] font-extrabold">Register contractors from a spreadsheet</h1>
    </div>

    <CsvImport
        {fileName}
        {rows}
        {columns}
        {maxRows}
        {urls}
        noun={['contractor', 'contractors']}
        verb="Register"
        rowMeaning="contractor"
        headers={['NAME', 'STATE', 'CATEGORY', 'NEMSA REG. NO.']}
        cols="grid-cols-[56px_minmax(0,1.4fr)_100px_110px_minmax(0,1fr)_minmax(0,1.6fr)]"
    >
        {#snippet guide()}
            <b>state</b> is Kaduna, Kebbi, Sokoto or Zamfara (where they work; reps assign them jobs there); <b>nemsa_category</b> is Cat A, Cat B, Cat C or
            Corporate. Corporate rows need <b>firm_name</b> and <b>engineer_name</b>. <b>phone</b>, <b>coren_no</b> and <b>engineer_reg_no</b> are optional.
        {/snippet}
        {#snippet note()}
            <p class="flex items-start gap-2 rounded-xl bg-info-bg px-3.5 py-3 text-sm">
                <Mail class="size-5 flex-none text-info" />Each contractor is emailed a temporary password and sets their own at first sign-in.
            </p>
        {/snippet}
        {#snippet cells(row: Row)}
            <span role="cell" class="flex min-w-0 flex-col">
                <b class="truncate">{row.name ?? '—'}</b>
                <span class="truncate text-xs text-mut">{[row.email, row.firm].filter(Boolean).join(' · ')}</span>
            </span>
            <span role="cell">{row.state ?? '—'}</span>
            <span role="cell">{row.category ?? '—'}</span>
            <span role="cell" class="truncate font-mono">{row.regNo ?? '—'}</span>
        {/snippet}
    </CsvImport>
</div>
