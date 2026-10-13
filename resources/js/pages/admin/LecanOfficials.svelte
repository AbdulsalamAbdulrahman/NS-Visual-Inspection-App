<script lang="ts" module>
    export { default as layout } from '@/layouts/AdminLayout.svelte';
</script>

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AccountActions from '@/components/admin/AccountActions.svelte';
    import DeleteConfirmRow from '@/components/admin/DeleteConfirmRow.svelte';
    import LecanDrawer, { type LecanRow } from '@/components/admin/LecanDrawer.svelte';
    import Button from '@/components/Button.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Pagination, { type PageLinks, type PageMeta } from '@/components/Pagination.svelte';
    import SearchField from '@/components/SearchField.svelte';
    import StatusPill from '@/components/StatusPill.svelte';
    import { accountTone } from '@/lib/status';
    import admin from '@/routes/admin';
    import ArrowBack from '~icons/ms/arrow-back';
    import Groups from '~icons/ms/groups';
    import PersonAdd from '~icons/ms/person-add';
    import Warning from '~icons/ms/warning';

    type State = { value: string; label: string; waiting: number; staffed: boolean; hasAreas: boolean };

    type Props = {
        officials: { data: LecanRow[]; meta: PageMeta; links: PageLinks };
        filters: { search: string };
        states: State[];
    };

    let { officials, filters, states }: Props = $props();

    let drawer: ReturnType<typeof LecanDrawer> | undefined = $state();
    let drawerOpen = $state(false);
    let editing = $state<LecanRow | null>(null);
    let confirmingDelete = $state<string | null>(null);

    const highlight = $derived(page.flash?.highlight);
    /** States with area offices but nobody to review their reports. */
    const unstaffed = $derived(states.filter((s) => s.hasAreas && !s.staffed));

    function openDrawer(row: LecanRow | null): void {
        editing = row;
        drawer?.reset(row);
        drawerOpen = true;
    }

    const cols = 'grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)_120px_100px_120px_40px]';
</script>

<svelte:head>
    <title>LECAN officials · KENS</title>
</svelte:head>

<header class="sticky top-0 z-20 flex flex-col gap-3 border-b border-line bg-sf px-2 pt-[calc(4px+env(safe-area-inset-top))] pb-3.5 lg:hidden">
    <div class="flex items-center gap-1">
        <Link href={admin.more()} class="flex size-12 items-center justify-center rounded-xl text-ink" aria-label="Back to More">
            <ArrowBack class="size-[22px]" />
        </Link>
        <h1 class="flex-1 text-[18px] font-bold">LECAN officials</h1>
        <button type="button" class="mr-2 flex size-11 items-center justify-center rounded-xl bg-pri text-on-pri" aria-label="Add LECAN official" onclick={() => openDrawer(null)}>
            <PersonAdd class="size-6" />
        </button>
    </div>
    <SearchField class="mx-2" tone="filled" value={filters.search} placeholder="Name or email" only={['officials', 'filters']} />
</header>

<div class="flex flex-col gap-[18px] px-4 py-3 lg:px-10 lg:py-8">
    <div class="hidden items-center gap-3 lg:flex">
        <div class="flex flex-col gap-0.5">
            <h1 class="text-[28px] font-extrabold">LECAN officials</h1>
            <span class="text-sm text-mut">First review of paid reports for their state chapter, before NSD’s final approval</span>
        </div>
        <SearchField class="ml-auto w-[300px]" value={filters.search} placeholder="Name or email" only={['officials', 'filters']} />
        <Button size="sm" class="h-11 rounded-xl" onclick={() => openDrawer(null)}><PersonAdd />Add LECAN official</Button>
    </div>

    <!-- Each chapter's queue -->
    <ul class="grid grid-cols-2 gap-2.5 lg:grid-cols-4">
        {#each states.filter((s) => s.hasAreas) as s (s.value)}
            <li class={['flex flex-col gap-0.5 rounded-2xl border bg-sf px-4 py-3', s.staffed ? 'border-line' : 'border-imp']}>
                <span class="text-sm font-semibold">{s.label} chapter</span>
                <span class="text-[13px] text-mut"><span class="font-mono font-semibold text-ink">{s.waiting}</span> waiting for LECAN</span>
                {#if !s.staffed}<span class="flex items-center gap-1 text-[13px] font-semibold text-imp"><Warning class="size-4" />No official yet</span>{/if}
            </li>
        {/each}
    </ul>
    {#if unstaffed.length > 0}
        <p class="text-sm text-mut">Reports from {unstaffed.map((s) => s.label).join(', ')} wait for LECAN until an official is added for that chapter.</p>
    {/if}

    {#if officials.data.length === 0}
        <EmptyState
            icon={Groups}
            tone="muted"
            title={filters.search ? `No officials match “${filters.search}”` : 'No LECAN officials yet'}
            body={filters.search ? undefined : 'Add an official for each state chapter. Paid reports wait for their review before NSD approves them.'}
        />
    {:else}
        <div class="hidden overflow-visible rounded-2xl border border-line bg-sf lg:block" role="table" aria-label="LECAN officials">
            <div role="row" class="grid {cols} gap-3 rounded-t-2xl border-b border-line bg-sf2 px-5 py-3 text-xs font-bold tracking-[0.06em] text-mut">
                <span role="columnheader">NAME</span>
                <span role="columnheader">EMAIL</span>
                <span role="columnheader">CHAPTER</span>
                <span role="columnheader">STATUS</span>
                <span role="columnheader">LAST ACTIVE</span>
                <span role="columnheader"><span class="sr-only">Actions</span></span>
            </div>
            {#each officials.data as o (o.uuid)}
                <div role="row" class={['grid min-h-[58px] items-center gap-3 border-b border-line px-5 py-2 text-sm last:border-b-0', cols, highlight === o.uuid && 'bg-soft']}>
                    <b role="cell" class="truncate">{o.name}</b>
                    <span role="cell" class="truncate text-mut">{o.email}</span>
                    <span role="cell"><span class="rounded-full bg-soft px-2.5 py-1 text-xs font-bold text-brand">{o.stateLabel}</span></span>
                    <span role="cell"><StatusPill tone={accountTone[o.status]} label={o.statusLabel} /></span>
                    <span role="cell" class="text-mut">{o.lastActive}</span>
                    <span role="cell" class="flex justify-end">
                        <AccountActions
                            account={{ ...o, subtitle: `${o.stateLabel} chapter` }}
                            noun="LECAN official"
                            onEdit={() => openDrawer(o)}
                            onRequestDelete={() => (confirmingDelete = o.uuid)}
                        />
                    </span>
                </div>
                {#if confirmingDelete === o.uuid}
                    <DeleteConfirmRow uuid={o.uuid} name={o.name} noun="LECAN official" suspended={o.status === 'suspended'} onCancel={() => (confirmingDelete = null)} />
                {/if}
            {/each}
        </div>

        <ul class="flex flex-col gap-2.5 lg:hidden">
            {#each officials.data as o (o.uuid)}
                <li class={['flex items-center gap-2 rounded-2xl border border-line py-3 pr-1 pl-3.5', highlight === o.uuid ? 'bg-soft' : 'bg-sf']}>
                    <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                        <b class="truncate text-base">{o.name}</b>
                        <span class="truncate text-[13px] text-mut">{o.email}</span>
                        <span class="flex flex-wrap items-center gap-1.5">
                            <StatusPill tone={accountTone[o.status]} label={o.statusLabel} />
                            <span class="rounded-full bg-soft px-2.5 py-1 text-xs font-bold text-brand">{o.stateLabel} chapter</span>
                        </span>
                    </div>
                    <AccountActions
                        account={{ ...o, subtitle: `${o.stateLabel} chapter` }}
                        noun="LECAN official"
                        onEdit={() => openDrawer(o)}
                        onRequestDelete={() => (confirmingDelete = o.uuid)}
                    />
                </li>
            {/each}
        </ul>

        <Pagination meta={officials.meta} links={officials.links} noun="officials" />
    {/if}
</div>

<LecanDrawer bind:this={drawer} bind:open={drawerOpen} official={editing} states={states.map((s) => ({ value: s.value, label: s.label }))} />
