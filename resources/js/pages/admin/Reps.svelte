<script lang="ts" module>
    export { default as layout } from '@/layouts/AdminLayout.svelte';
</script>

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import AccountActions from '@/components/admin/AccountActions.svelte';
    import DeleteConfirmRow from '@/components/admin/DeleteConfirmRow.svelte';
    import RepDrawer, { type AreaOption, type RepRow } from '@/components/admin/RepDrawer.svelte';
    import Button from '@/components/Button.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Pagination, { type PageLinks, type PageMeta } from '@/components/Pagination.svelte';
    import SearchField from '@/components/SearchField.svelte';
    import StatusPill from '@/components/StatusPill.svelte';
    import { accountTone } from '@/lib/status';
    import admin from '@/routes/admin';
    import ArrowBack from '~icons/ms/arrow-back';
    import BadgeIcon from '~icons/ms/badge';
    import PersonAdd from '~icons/ms/person-add';

    type Props = {
        reps: { data: RepRow[]; meta: PageMeta; links: PageLinks };
        filters: { search: string };
        areas: AreaOption[];
    };

    let { reps, filters, areas }: Props = $props();

    let drawer: ReturnType<typeof RepDrawer> | undefined = $state();
    let drawerOpen = $state(false);
    let editing = $state<RepRow | null>(null);
    let confirmingDelete = $state<string | null>(null);

    const highlight = $derived(page.flash?.highlight);

    function openDrawer(row: RepRow | null): void {
        editing = row;
        drawer?.reset(row);
        drawerOpen = true;
    }

    const cols = 'grid-cols-[minmax(0,1fr)_minmax(0,1.2fr)_minmax(0,2fr)_100px_40px]';
</script>

<svelte:head>
    <title>Service reps · KENS</title>
</svelte:head>

<!-- Phone header: reached from the More tab -->
<header class="sticky top-0 z-20 flex flex-col gap-3 border-b border-line bg-sf px-2 pt-[calc(4px+env(safe-area-inset-top))] pb-3.5 lg:hidden">
    <div class="flex items-center gap-1">
        <Link href={admin.more()} class="flex size-12 items-center justify-center rounded-xl text-ink" aria-label="Back to More">
            <ArrowBack class="size-[22px]" />
        </Link>
        <h1 class="flex-1 text-[18px] font-bold">Service reps</h1>
        <button
            type="button"
            class="mr-2 flex size-11 items-center justify-center rounded-xl bg-pri text-on-pri"
            aria-label="Add service rep"
            onclick={() => openDrawer(null)}
        >
            <PersonAdd class="size-6" />
        </button>
    </div>
    <SearchField class="mx-2" tone="filled" value={filters.search} placeholder="Name, email or area" only={['reps', 'filters']} />
</header>

<div class="flex flex-col gap-[18px] px-4 py-3 lg:px-10 lg:py-8">
    <div class="hidden items-center gap-3 lg:flex">
        <div class="flex flex-col gap-0.5">
            <h1 class="text-[28px] font-extrabold">Service reps</h1>
            <span class="text-sm text-mut">Read-only access to submissions in their assigned areas</span>
        </div>
        <SearchField class="ml-auto w-[300px]" value={filters.search} placeholder="Name, email or area" only={['reps', 'filters']} />
        <Button size="sm" class="h-11 rounded-xl" onclick={() => openDrawer(null)}>
            <PersonAdd />Add service rep
        </Button>
    </div>

    {#if reps.data.length === 0}
        <EmptyState
            icon={BadgeIcon}
            tone="muted"
            title={filters.search ? `No reps match “${filters.search}”` : 'No service reps yet'}
            body={filters.search ? undefined : 'Add a rep and assign 1 to 3 service areas they can view and print.'}
        />
    {:else}
        <div class="hidden overflow-visible rounded-2xl border border-line bg-sf lg:block" role="table" aria-label="Service reps">
            <div role="row" class="grid {cols} gap-3 rounded-t-2xl border-b border-line bg-sf2 px-5 py-3 text-xs font-bold tracking-[0.06em] text-mut">
                <span role="columnheader">NAME</span>
                <span role="columnheader">EMAIL</span>
                <span role="columnheader">SERVICE AREAS</span>
                <span role="columnheader">STATUS</span>
                <span role="columnheader"><span class="sr-only">Actions</span></span>
            </div>
            {#each reps.data as r (r.uuid)}
                <div
                    role="row"
                    class={['grid min-h-[58px] items-center gap-3 border-b border-line px-5 py-2 text-sm last:border-b-0', cols, highlight === r.uuid && 'bg-soft']}
                >
                    <b role="cell" class="truncate">{r.name}</b>
                    <span role="cell" class="truncate text-mut">{r.email}</span>
                    <span role="cell" class="flex flex-wrap gap-1.5">
                        {#each r.areas as area (area.id)}
                            <span class="rounded-full bg-soft px-2.5 py-1 text-xs font-bold text-brand">{area.name}</span>
                        {/each}
                    </span>
                    <span role="cell"><StatusPill tone={accountTone[r.status]} label={r.statusLabel} /></span>
                    <span role="cell" class="flex justify-end">
                        <AccountActions
                            account={{ ...r, subtitle: r.areas.map((a) => a.name).join(' · ') }}
                            noun="service rep"
                            onEdit={() => openDrawer(r)}
                            onRequestDelete={() => (confirmingDelete = r.uuid)}
                        />
                    </span>
                </div>
                {#if confirmingDelete === r.uuid}
                    <DeleteConfirmRow
                        uuid={r.uuid}
                        name={r.name}
                        noun="service rep"
                        suspended={r.status === 'suspended'}
                        onCancel={() => (confirmingDelete = null)}
                    />
                {/if}
            {/each}
        </div>

        <ul class="flex flex-col gap-2.5 lg:hidden">
            {#each reps.data as r (r.uuid)}
                <li class={['flex items-center gap-2 rounded-2xl border border-line py-3 pr-1 pl-3.5', highlight === r.uuid ? 'bg-soft' : 'bg-sf']}>
                    <div class="flex min-w-0 flex-1 flex-col gap-1.5">
                        <b class="truncate text-base">{r.name}</b>
                        <span class="truncate text-[13px] text-mut">{r.email}</span>
                        <span class="flex flex-wrap items-center gap-1.5">
                            <StatusPill tone={accountTone[r.status]} label={r.statusLabel} />
                            {#each r.areas as area (area.id)}
                                <span class="rounded-full bg-soft px-2.5 py-1 text-xs font-bold text-brand">{area.name}</span>
                            {/each}
                        </span>
                    </div>
                    <AccountActions
                        account={{ ...r, subtitle: r.areas.map((a) => a.name).join(' · ') }}
                        noun="service rep"
                        onEdit={() => openDrawer(r)}
                        onRequestDelete={() => (confirmingDelete = r.uuid)}
                    />
                </li>
            {/each}
        </ul>

        <Pagination meta={reps.meta} links={reps.links} noun="reps" />
    {/if}
</div>

<RepDrawer bind:this={drawer} bind:open={drawerOpen} rep={editing} {areas} />
