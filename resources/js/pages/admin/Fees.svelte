<script lang="ts" module>
    export { default as layout } from '@/layouts/AdminLayout.svelte';
</script>

<script lang="ts">
    import { Link, router, useForm } from '@inertiajs/svelte';
    import { tick } from 'svelte';
    import Button from '@/components/Button.svelte';
    import Field from '@/components/form/Field.svelte';
    import { fieldBox } from '@/components/form/inputClasses';
    import StatusPill from '@/components/StatusPill.svelte';
    import admin from '@/routes/admin';
    import { destroy, store } from '@/routes/admin/fees';
    import ArrowBack from '~icons/ms/arrow-back';
    import Schedule from '~icons/ms/schedule';

    type Fee = {
        id: number;
        buildingType: string | null;
        /** "Duplex · Three phase · Stand-alone", or "Default fee". */
        buildingTypeLabel: string;
        amountKobo: number;
        notOffered: boolean;
        amount: string;
        effectiveFrom: string;
        changedOn: string | null;
        changedBy: string;
        reason: string | null;
        isScheduled: boolean;
    };

    type Cell = {
        key: string;
        buildingType: string | null;
        connectionType: string | null;
        premises: string | null;
        label: string;
        current: Fee | null;
        scheduled: Fee | null;
    };

    type Row = {
        key: string;
        label: string;
        purpose: string;
        cells: Cell[];
    };

    type Props = {
        default: Cell;
        columns: { connectionType: string; premises: string }[];
        rows: Row[];
        history: Fee[];
    };

    let { default: defaultCell, columns, rows, history }: Props = $props();

    const today = new Date().toLocaleDateString('en-CA'); // YYYY-MM-DD in local time

    const form = useForm({
        building_type: null as string | null,
        connection_type: null as string | null,
        premises: null as string | null,
        not_offered: false,
        amount: '',
        effective_from: today,
        reason: '',
    });

    /** Key of the fee being changed ("default" or "type|supply|premises"). */
    let selectedKey = $state('default');

    const allCells = $derived([defaultCell, ...rows.flatMap((r) => r.cells)]);
    const selected = $derived(allCells.find((c) => c.key === selectedKey) ?? defaultCell);
    const isCell = $derived(selected.connectionType !== null);
    const isToday = $derived(form.effective_from === today);
    const groups = $derived(
        rows.reduce<{ purpose: string; rows: Row[] }[]>((all, row) => {
            const group = all.find((g) => g.purpose === row.purpose);

            if (group) {
                group.rows.push(row);
            } else {
                all.push({ purpose: row.purpose, rows: [row] });
            }

            return all;
        }, []),
    );

    let formEl: HTMLFormElement | undefined = $state();

    async function change(cell: Cell): Promise<void> {
        selectedKey = cell.key;
        form.building_type = cell.buildingType;
        form.connection_type = cell.connectionType;
        form.premises = cell.premises;
        form.not_offered = false;
        form.amount = '';
        form.clearErrors();
        await tick();
        formEl?.scrollIntoView({ behavior: 'smooth', block: 'start' });
        document.getElementById('fee-amount')?.focus();
    }

    function submit(event: SubmitEvent): void {
        event.preventDefault();
        form.post(store.url(), {
            preserveScroll: true,
            onSuccess: () => {
                form.amount = '';
                form.reason = '';
                form.not_offered = false;
                form.effective_from = today;
            },
        });
    }

    function cancelScheduled(fee: Fee): void {
        router.delete(destroy.url(fee.id), { preserveScroll: true });
    }

    const matrix = 'lg:grid-cols-[minmax(0,1.2fr)_repeat(4,minmax(0,1fr))]';
    const cols = 'grid-cols-[minmax(0,1.6fr)_120px_120px_110px_minmax(0,1fr)_minmax(0,1.1fr)]';
</script>

<svelte:head>
    <title>Inspection fees · KENS</title>
</svelte:head>

<header class="sticky top-0 z-20 flex items-center gap-1 border-b border-line bg-sf px-2 pt-[calc(4px+env(safe-area-inset-top))] pb-1 lg:hidden">
    <Link href={admin.more()} class="flex size-12 items-center justify-center rounded-xl text-ink" aria-label="Back to More">
        <ArrowBack class="size-[22px]" />
    </Link>
    <h1 class="flex-1 text-[18px] font-bold">Inspection fees</h1>
</header>

<div class="flex flex-col gap-5 px-4 py-4 lg:px-10 lg:py-8">
    <div class="hidden flex-col gap-1 lg:flex">
        <h1 class="text-[28px] font-extrabold">Inspection fees</h1>
        <p class="text-[15px] text-mut">Per structure, by building type, supply and premises (LECAN fee sheet). Multiple premises pay the fee × the number of structures.</p>
    </div>

    <div class="grid max-w-[1320px] gap-5 xl:grid-cols-[1fr_380px] xl:items-start">
        <!-- Fee sheet -->
        <section class="flex flex-col overflow-hidden rounded-[20px] border border-line bg-sf" aria-labelledby="fee-table">
            <h2 id="fee-table" class="sr-only">Fees by building type, supply and premises</h2>
            <div class="hidden gap-2 border-b border-line bg-sf2 px-5 py-2.5 text-xs font-bold tracking-[0.04em] text-mut lg:grid {matrix}">
                <span>BUILDING TYPE</span>
                {#each columns as column (column.premises + column.connectionType)}
                    <span class="flex flex-col"><span>{column.premises.toUpperCase()}</span><span class="font-semibold">{column.connectionType}</span></span>
                {/each}
            </div>
            {#each groups as group (group.purpose)}
                <div class="border-b border-line bg-sf2 px-4 py-2 text-xs font-bold tracking-[0.06em] text-mut uppercase lg:px-5">{group.purpose}</div>
                {#each group.rows as row (row.key)}
                    <div class="flex flex-col gap-2 border-b border-line px-4 py-3 last:border-b-0 lg:grid lg:items-center lg:gap-2 lg:px-5 lg:py-2 {matrix}">
                        <b class="text-[15px]">{row.label}</b>
                        <div class="grid grid-cols-2 gap-2 lg:contents">
                            {#each row.cells as cell, i (cell.key)}
                                {@render cellButton(cell, columns[i])}
                            {/each}
                        </div>
                    </div>
                {/each}
            {/each}
            <div class="flex items-center gap-3 bg-sf2 px-4 py-3 lg:px-5">
                <div class="flex min-w-0 flex-1 flex-col">
                    <b class="text-[15px]">Default fee</b>
                    <span class="text-[13px] text-mut">Used only if a building has no fee above.</span>
                </div>
                <div class="w-40">{@render cellButton(defaultCell, null)}</div>
            </div>
        </section>

        <!-- Set a fee -->
        <form bind:this={formEl} class="flex scroll-mt-20 flex-col gap-3.5 rounded-[20px] border border-line bg-sf p-5 xl:sticky xl:top-6 xl:p-6" onsubmit={submit} novalidate>
            <div class="flex flex-col gap-0.5">
                <h2 class="text-[17px] font-bold">Change a fee</h2>
                <span class="text-sm text-mut">{selectedKey === 'default' ? 'Default fee · choose a building fee in the table to change it' : selected.label}</span>
                {#if form.errors.building_type || form.errors.connection_type || form.errors.premises}
                    <span class="text-sm font-semibold text-bad" role="alert">{form.errors.building_type ?? form.errors.connection_type ?? form.errors.premises}</span>
                {/if}
            </div>
            <div class="flex flex-col gap-1 rounded-xl bg-sf2 px-3.5 py-2.5 text-sm">
                <span>Now: <b class="font-mono">{selected.current ? (selected.current.notOffered ? 'Not offered' : selected.current.amount) : 'Not set'}</b></span>
                {#if selected.scheduled}
                    <span class="flex flex-wrap items-center gap-1 text-info">
                        <Schedule class="size-4" />{selected.scheduled.notOffered ? 'Not offered' : selected.scheduled.amount} from {selected.scheduled.effectiveFrom}
                        <button type="button" class="ml-1 font-bold underline" onclick={() => selected.scheduled && cancelScheduled(selected.scheduled)}>Cancel</button>
                    </span>
                {/if}
            </div>
            {#if isCell}
                <label class="flex min-h-12 items-center gap-3 rounded-xl border-[1.5px] border-line px-3.5 text-[15px] font-semibold">
                    <input type="checkbox" class="size-5 accent-pri" bind:checked={form.not_offered} />
                    Not offered for this building and supply
                </label>
            {/if}
            <div class="grid gap-3 sm:grid-cols-2 xl:grid-cols-1">
                {#if !form.not_offered}
                    <Field id="fee-amount" label="Amount per structure" error={form.errors.amount}>
                        <div class={fieldBox('md', !!form.errors.amount, 'h-12 gap-1.5 rounded-[10px] pl-3')}>
                            <span class="font-mono text-[17px] text-mut" aria-hidden="true">₦</span>
                            <input
                                id="fee-amount"
                                bind:value={form.amount}
                                inputmode="decimal"
                                placeholder="15,000"
                                autocomplete="off"
                                aria-invalid={!!form.errors.amount || undefined}
                                class="h-full min-w-0 flex-1 bg-transparent pr-3 font-mono text-[17px] font-medium outline-none focus-visible:outline-none"
                            />
                        </div>
                    </Field>
                {/if}
                <Field id="fee-date" label="Effective date" error={form.errors.effective_from}>
                    <input
                        id="fee-date"
                        type="date"
                        min={today}
                        bind:value={form.effective_from}
                        aria-invalid={!!form.errors.effective_from || undefined}
                        class={fieldBox('md', !!form.errors.effective_from, 'h-12 rounded-[10px] px-3 font-mono text-[15px] font-medium outline-none')}
                    />
                </Field>
            </div>
            <Field id="fee-reason" error={form.errors.reason}>
                {#snippet labelAside()}
                    <label for="fee-reason" class="text-sm font-semibold">Reason <span class="font-normal text-mut">optional</span></label>
                {/snippet}
                <input
                    id="fee-reason"
                    bind:value={form.reason}
                    maxlength="255"
                    placeholder="e.g. 2027 tariff review — NSD memo 14/2026"
                    class={fieldBox('md', false, 'h-12 rounded-[10px] px-3 text-[15px] outline-none')}
                />
            </Field>
            <p class="text-[13px] leading-[1.45] text-mut">
                Contractors pay the fee in effect when they pay, per structure. Drafts show the new amount from the effective date.
            </p>
            <Button type="submit" size="md" block class="h-12 rounded-xl" disabled={form.processing || (!form.not_offered && !form.amount.trim())}>
                {isToday ? 'Change fee from today' : 'Schedule fee change'}
            </Button>
        </form>
    </div>

    <section class="flex max-w-[1320px] flex-col gap-2.5" aria-labelledby="fee-history">
        <h2 id="fee-history" class="eyebrow">History</h2>
        <div class="overflow-hidden rounded-2xl border border-line bg-sf" role="table" aria-label="Fee history">
            <div role="row" class="hidden gap-3 border-b border-line bg-sf2 px-5 py-3 text-xs font-bold tracking-[0.06em] text-mut lg:grid {cols}">
                <span role="columnheader">FEE FOR</span>
                <span role="columnheader">FEE</span>
                <span role="columnheader">EFFECTIVE</span>
                <span role="columnheader">CHANGED ON</span>
                <span role="columnheader">CHANGED BY</span>
                <span role="columnheader">REASON</span>
            </div>
            {#each history as fee (fee.id)}
                <div role="row" class="flex flex-col gap-1 border-b border-line px-4 py-3 text-sm last:border-b-0 lg:grid lg:h-[50px] lg:items-center lg:gap-3 lg:px-5 lg:py-0 {cols}">
                    <span role="cell" class="font-semibold lg:truncate">{fee.buildingTypeLabel}</span>
                    <span role="cell" class="flex items-center gap-2 font-mono font-semibold">
                        {fee.notOffered ? 'Not offered' : fee.amount}
                        {#if fee.isScheduled}<StatusPill tone="info" label="Scheduled" class="font-sans" />{/if}
                    </span>
                    <span role="cell" class="font-mono"><span class="text-mut lg:hidden">From </span>{fee.effectiveFrom}</span>
                    <span role="cell" class="hidden font-mono lg:block">{fee.changedOn ?? '—'}</span>
                    <span role="cell" class="text-mut lg:text-ink"><span class="lg:hidden">Set by </span>{fee.changedBy}</span>
                    <span role="cell" class="truncate text-mut">{fee.reason ?? ''}</span>
                </div>
            {:else}
                <p class="px-5 py-4 text-sm text-mut">No fee changes yet.</p>
            {/each}
        </div>
    </section>
</div>

{#snippet cellButton(cell: Cell, column: { connectionType: string; premises: string } | null)}
    {@const on = selectedKey === cell.key}
    <button
        type="button"
        aria-pressed={on}
        aria-label="{cell.label}: {cell.current ? (cell.current.notOffered ? 'not offered' : cell.current.amount) : 'not set'}. Change"
        class={[
            'flex min-h-12 flex-col items-start justify-center rounded-[10px] border px-2.5 py-1.5 text-left',
            on ? 'border-2 border-pri bg-soft' : 'border-line hover:bg-sf2',
        ]}
        onclick={() => change(cell)}
    >
        {#if column}<span class="text-[11px] font-semibold text-mut lg:hidden">{column.premises} · {column.connectionType}</span>{/if}
        {#if cell.current && !cell.current.notOffered}
            <span class="font-mono text-[15px] font-semibold">{cell.current.amount.replace(/\.00$/, '')}</span>
        {:else}
            <span class="text-[13px] font-semibold text-mut">{cell.current ? 'Not offered' : 'Not set'}</span>
        {/if}
        {#if cell.scheduled}
            <span class="flex items-center gap-0.5 text-[11px] font-semibold text-info"><Schedule class="size-3.5" aria-hidden="true" />{cell.scheduled.notOffered ? 'Not offered' : cell.scheduled.amount.replace(/\.00$/, '')} · {cell.scheduled.effectiveFrom}</span>
        {/if}
    </button>
{/snippet}
