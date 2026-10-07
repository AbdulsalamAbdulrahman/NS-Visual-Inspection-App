<script lang="ts" module>
    export { default as layout } from '@/layouts/AdminLayout.svelte';
</script>

<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import AccountActions from '@/components/admin/AccountActions.svelte';
    import ContractorDrawer, { type ContractorRow } from '@/components/admin/ContractorDrawer.svelte';
    import DeleteConfirmRow from '@/components/admin/DeleteConfirmRow.svelte';
    import Button from '@/components/Button.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Pagination, { type PageLinks, type PageMeta } from '@/components/Pagination.svelte';
    import SearchField from '@/components/SearchField.svelte';
    import StatusPill from '@/components/StatusPill.svelte';
    import { accountTone } from '@/lib/status';
    import Engineering from '~icons/ms/engineering';
    import PersonAdd from '~icons/ms/person-add';

    type Props = {
        contractors: { data: ContractorRow[]; meta: PageMeta; links: PageLinks };
        filters: { search: string };
        summary: { total: number; active: number; invited: number; suspended: number };
        categories: { value: string; label: string }[];
    };

    let { contractors, filters, summary, categories }: Props = $props();

    let drawer: ReturnType<typeof ContractorDrawer> | undefined = $state();
    let drawerOpen = $state(false);
    let editing = $state<ContractorRow | null>(null);
    let confirmingDelete = $state<string | null>(null);

    // Newly created contractor is highlighted (AD-06).
    const highlight = $derived(page.flash?.highlight);

    const summaryLine = $derived(
        [
            `${summary.total}`,
            `${summary.active} active`,
            summary.invited ? `${summary.invited} invited` : null,
            `${summary.suspended} suspended`,
        ]
            .filter(Boolean)
            .join(' · '),
    );

    function openDrawer(row: ContractorRow | null): void {
        editing = row;
        drawer?.reset(row);
        drawerOpen = true;
    }

    const cols = 'grid-cols-[minmax(0,1.4fr)_100px_170px_140px_70px_110px_120px_40px]';
</script>

<svelte:head>
    <title>Contractors · KENS</title>
</svelte:head>

<!-- Phone header (AM-05) -->
<header class="sticky top-0 z-20 flex flex-col gap-3 border-b border-line bg-sf px-4 pt-[calc(12px+env(safe-area-inset-top))] pb-3.5 lg:hidden">
    <div class="flex items-center gap-3">
        <div class="flex flex-1 flex-col">
            <h1 class="text-[20px] font-bold">Contractors</h1>
            <span class="text-[13px] text-mut">{summaryLine}</span>
        </div>
        <button
            type="button"
            class="flex size-11 items-center justify-center rounded-xl bg-pri text-on-pri"
            aria-label="Add contractor"
            onclick={() => openDrawer(null)}
        >
            <PersonAdd class="size-6" />
        </button>
    </div>
    <SearchField tone="filled" value={filters.search} placeholder="Name, NEMSA or phone" only={['contractors', 'filters']} />
</header>

<div class="flex flex-col gap-[18px] px-4 py-3 lg:px-10 lg:py-8">
    <!-- Desktop header (AD-05) -->
    <div class="hidden items-center gap-3 lg:flex">
        <div class="flex flex-col gap-0.5">
            <h1 class="text-[28px] font-extrabold">Contractors</h1>
            <span class="text-sm text-mut">{summaryLine}</span>
        </div>
        <SearchField class="ml-auto w-[300px]" value={filters.search} placeholder="Name, NEMSA or phone" only={['contractors', 'filters']} />
        <Button size="sm" class="h-11 rounded-xl" onclick={() => openDrawer(null)}>
            <PersonAdd />Add contractor
        </Button>
    </div>

    {#if contractors.data.length === 0}
        <EmptyState
            icon={Engineering}
            tone="muted"
            title={filters.search ? `No contractors match “${filters.search}”` : 'No contractors yet'}
            body={filters.search ? 'Try a name, NEMSA registration number or phone.' : 'Add the first licensed contractor to send their login details.'}
        />
    {:else}
        <!-- Desktop table -->
        <div class="hidden overflow-visible rounded-2xl border border-line bg-sf lg:block" role="table" aria-label="Contractors">
            <div
                role="row"
                class="grid {cols} gap-3 rounded-t-2xl border-b border-line bg-sf2 px-5 py-3 text-xs font-bold tracking-[0.06em] text-mut"
            >
                <span role="columnheader">NAME</span>
                <span role="columnheader">CATEGORY</span>
                <span role="columnheader">NEMSA REG. NO.</span>
                <span role="columnheader">PHONE</span>
                <span role="columnheader" class="text-right">INSP.</span>
                <span role="columnheader">STATUS</span>
                <span role="columnheader">LAST ACTIVE</span>
                <span role="columnheader"><span class="sr-only">Actions</span></span>
            </div>
            {#each contractors.data as c (c.uuid)}
                <div
                    role="row"
                    class={[
                        'grid h-14 items-center gap-3 border-b border-line px-5 text-sm last:border-b-0',
                        cols,
                        highlight === c.uuid && 'bg-soft',
                    ]}
                >
                    <b role="cell" class="truncate">{c.name}</b>
                    <span role="cell">{c.categoryLabel ?? '—'}</span>
                    <span role="cell" class="truncate font-mono text-[13px]">{c.regNo ?? '—'}</span>
                    <span role="cell" class="truncate font-mono text-[13px]">{c.phone ?? '—'}</span>
                    <span role="cell" class="text-right font-mono">{c.inspectionsCount}</span>
                    <span role="cell"><StatusPill tone={accountTone[c.status]} label={c.statusLabel} /></span>
                    <span role="cell" class="text-mut">{c.lastActive}</span>
                    <span role="cell" class="flex justify-end">
                        <AccountActions
                            account={{ ...c, subtitle: [c.categoryLabel, c.regNo].filter(Boolean).join(' · ') }}
                            noun="contractor"
                            inspectionsCount={c.inspectionsCount}
                            onEdit={() => openDrawer(c)}
                            onRequestDelete={() => (confirmingDelete = c.uuid)}
                        />
                    </span>
                </div>
                {#if confirmingDelete === c.uuid}
                    <DeleteConfirmRow
                        uuid={c.uuid}
                        name={c.name}
                        noun="contractor"
                        inspectionsCount={c.inspectionsCount}
                        suspended={c.status === 'suspended'}
                        onCancel={() => (confirmingDelete = null)}
                    />
                {/if}
            {/each}
        </div>

        <!-- Phone cards (AM-05) -->
        <ul class="flex flex-col gap-2.5 lg:hidden">
            {#each contractors.data as c (c.uuid)}
                <li
                    class={[
                        'flex items-center gap-2 rounded-2xl border border-line py-3 pr-1 pl-3.5',
                        highlight === c.uuid ? 'bg-soft' : 'bg-sf',
                    ]}
                >
                    <div class="flex min-w-0 flex-1 flex-col gap-1">
                        <b class="truncate text-base">{c.name}</b>
                        <span class="truncate font-mono text-[13px] font-medium text-mut">
                            {[c.categoryLabel, c.regNo].filter(Boolean).join(' · ')}
                        </span>
                        <span class="flex items-center gap-2 text-[13px]">
                            <StatusPill tone={accountTone[c.status]} label={c.statusLabel} />
                            <span class="text-mut">{c.inspectionsCount} inspections</span>
                        </span>
                    </div>
                    <AccountActions
                        account={{ ...c, subtitle: [c.categoryLabel, c.regNo].filter(Boolean).join(' · ') }}
                        noun="contractor"
                        inspectionsCount={c.inspectionsCount}
                        onEdit={() => openDrawer(c)}
                        onRequestDelete={() => (confirmingDelete = c.uuid)}
                    />
                </li>
            {/each}
        </ul>

        <Pagination meta={contractors.meta} links={contractors.links} noun="contractors" />
    {/if}
</div>

<ContractorDrawer bind:this={drawer} bind:open={drawerOpen} contractor={editing} {categories} />
