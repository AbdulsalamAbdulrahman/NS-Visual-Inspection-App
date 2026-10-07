<script lang="ts" module>
    export { default as layout } from '@/layouts/RepLayout.svelte';
</script>

<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import Avatar from '@/components/Avatar.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import MobileHeader from '@/components/MobileHeader.svelte';
    import Pagination, { type PageLinks, type PageMeta } from '@/components/Pagination.svelte';
    import ListFilters from '@/components/report/ListFilters.svelte';
    import StatusPill from '@/components/StatusPill.svelte';
    import UserMenu from '@/components/UserMenu.svelte';
    import { REVIEW_TONE, type FilterOptions, type InspectionRow, type ListFilters as Filters } from '@/lib/inspection/report';
    import { index, show } from '@/routes/rep/inspections';
    import ChevronRight from '~icons/ms/chevron-right';
    import Inbox from '~icons/ms/inbox';
    import Print from '~icons/ms/print';

    type Props = {
        inspections: { data: InspectionRow[]; meta: PageMeta; links: PageLinks };
        areas: { id: number; name: string; count: number }[];
        total: number;
        filters: Filters;
        filterOptions: FilterOptions;
    };

    let { inspections, areas, total, filters, filterOptions }: Props = $props();

    const user = $derived(page.props.auth.user!);
    const activeArea = $derived(filters.areas.length === 1 ? filters.areas[0] : null);
    const filtered = $derived(filters.count > 0 || !!filters.search);

    const areaList = $derived(
        areas.length > 1 ? `${areas.slice(0, -1).map((a) => a.name).join(', ')} or ${areas.at(-1)?.name}` : (areas[0]?.name ?? 'your areas'),
    );

    function pickArea(id: number | null): void {
        const url = new URL(window.location.href);

        if (id === null) {
            url.searchParams.delete('areas');
        } else {
            url.searchParams.set('areas', String(id));
        }

        url.searchParams.delete('page');
        router.get(url.pathname + url.search, {}, { preserveState: true, preserveScroll: true, replace: true, only: ['inspections', 'filters'] });
    }

    const chip = 'flex h-10 flex-none items-center gap-1.5 rounded-full px-3.5 text-sm md:h-11 md:px-4 md:text-[15px] lg:h-10 lg:text-sm';
    const cols = 'grid-cols-[180px_minmax(0,1.1fr)_minmax(0,1.3fr)_110px_minmax(0,1fr)_110px_150px_40px]';
</script>

<svelte:head>
    <title>Inspections · KENS</title>
</svelte:head>

{#snippet chips()}
    <div class="flex gap-2 overflow-x-auto [scrollbar-width:none]" role="group" aria-label="Filter by area">
        <button type="button" aria-pressed={activeArea === null} class={[chip, activeArea === null ? 'bg-pri font-bold text-on-pri' : 'border border-line bg-sf font-semibold']} onclick={() => pickArea(null)}>
            All my areas<span class="hidden lg:inline"> · {total}</span>
        </button>
        {#each areas as a (a.id)}
            <button type="button" aria-pressed={activeArea === a.id} class={[chip, activeArea === a.id ? 'bg-pri font-bold text-on-pri' : 'border border-line bg-sf font-semibold']} onclick={() => pickArea(a.id)}>
                {a.name}<span class={['hidden font-mono text-xs font-medium lg:inline', activeArea === a.id ? 'opacity-80' : 'text-mut']}>{a.count}</span>
            </button>
        {/each}
    </div>
{/snippet}

<!-- Phone header (SR-M1) -->
<MobileHeader title="Inspections" subtitle={total ? `${total} in my areas` : user.name} class="md:hidden">
    {#snippet trailing()}
        <UserMenu {user} triggerClass="rounded-full">
            {#snippet trigger()}<Avatar initials={user.initials} />{/snippet}
        </UserMenu>
    {/snippet}
    {#if total > 0}
        <ListFilters {filters} options={filterOptions} url={index.url()} only={['inspections', 'filters']} showAreas={false} resultCount={inspections.meta.total} />
    {/if}
    {@render chips()}
</MobileHeader>

<div class="flex flex-col gap-[18px] px-4 py-3 md:px-6 md:py-5 lg:px-12 lg:py-8">
    <div class="hidden items-end gap-3 md:flex">
        <div class="flex flex-col gap-0.5">
            <h1 class="text-[20px] font-bold lg:text-[28px] lg:font-extrabold">Inspections in my areas</h1>
            <span class="hidden text-sm text-mut lg:inline">{total} submitted · view and print</span>
        </div>
    </div>
    {#if total > 0}
        <div class="hidden md:block lg:hidden">
            <ListFilters {filters} options={filterOptions} url={index.url()} only={['inspections', 'filters']} showAreas={false} resultCount={inspections.meta.total} />
        </div>
        <div class="hidden md:flex md:flex-wrap md:items-center md:gap-2">
            {@render chips()}
            <span class="mx-1.5 hidden h-7 w-px bg-line lg:block"></span>
            <div class="hidden lg:block"><ListFilters {filters} options={filterOptions} url={index.url()} only={['inspections', 'filters']} showAreas={false} resultCount={inspections.meta.total} /></div>
        </div>
    {/if}

    {#if inspections.data.length === 0}
        <!-- SR-M2 -->
        <EmptyState
            icon={Inbox}
            tone="muted"
            title={filtered ? 'Nothing matches these filters' : `No submissions in ${areaList} yet`}
            body={filtered ? 'Try another area or clear the filters.' : 'Paid inspections for your areas will appear here as soon as contractors submit them.'}
        >
            {#if !filtered}<span class="mt-1.5 text-sm text-mut">Wrong areas? Ask the NSD admin to update your assignment.</span>{/if}
        </EmptyState>
    {:else}
        <!-- Desktop table (SR-D1) — no amount column: reps never see payment data -->
        <div class="hidden overflow-hidden rounded-2xl border border-line bg-sf lg:block" role="table" aria-label="Inspections in my areas">
            <div role="row" class="grid {cols} gap-3 border-b border-line bg-sf2 px-5 py-3 text-xs font-bold tracking-[0.06em] text-mut">
                <span role="columnheader">TICKET</span>
                <span role="columnheader">OWNER</span>
                <span role="columnheader">ADDRESS</span>
                <span role="columnheader">AREA</span>
                <span role="columnheader">CONTRACTOR</span>
                <span role="columnheader" aria-sort="descending">DATE ↓</span>
                <span role="columnheader">REVIEW</span>
                <span role="columnheader"><span class="sr-only">Open</span></span>
            </div>
            {#each inspections.data as r (r.uuid)}
                <Link href={show.url(r.uuid)} role="row" class="grid h-[54px] items-center gap-3 border-b border-line px-5 text-sm text-ink no-underline last:border-b-0 hover:bg-sf2 {cols}">
                    <span role="cell" class="font-mono font-medium text-brand">{r.ticketNo}</span>
                    <b role="cell" class="truncate">{r.ownerName}</b>
                    <span role="cell" class="truncate text-mut">{r.address}</span>
                    <span role="cell">{r.area}</span>
                    <span role="cell" class="truncate">{r.contractor}</span>
                    <span role="cell" class="font-mono">{r.submittedAt}</span>
                    <span role="cell">{#if r.review}<StatusPill tone={REVIEW_TONE[r.review]} label={r.reviewLabel ?? ''} />{/if}</span>
                    <span role="cell" class="flex justify-end text-mut"><Print class="size-[22px]" aria-hidden="true" /></span>
                </Link>
            {/each}
        </div>

        <!-- Tablet (SR-T1) and phone (SR-M1) -->
        <ul class="flex flex-col gap-2.5 md:gap-0 md:overflow-hidden md:rounded-2xl md:border md:border-line md:bg-sf lg:hidden">
            {#each inspections.data as r (r.uuid)}
                <li class="md:border-b md:border-line md:last:border-b-0">
                    <Link href={show.url(r.uuid)} class="flex items-center gap-2 rounded-2xl border border-line bg-sf py-3 pr-2 pl-3.5 text-ink no-underline md:rounded-none md:border-0 md:py-3.5 md:pl-5">
                        <span class="flex min-w-0 flex-1 flex-col gap-[3px]">
                            <span class="flex items-center gap-2">
                                <span class="font-mono text-sm font-medium text-brand">{r.ticketNo}</span>
                                {#if r.review}<StatusPill tone={REVIEW_TONE[r.review]} label={r.reviewLabel ?? ''} />{/if}
                            </span>
                            <b class="truncate text-base">{r.ownerName}</b>
                            <span class="truncate text-[13px] text-mut md:hidden">{r.area} · {r.submittedAt}</span>
                            <span class="hidden truncate text-sm text-mut md:inline">{r.address} · {r.contractor}</span>
                        </span>
                        <span class="hidden flex-col items-end gap-1 md:flex">
                            <span class="rounded-full bg-soft px-2.5 py-[3px] text-xs font-bold text-brand">{r.area}</span>
                            <span class="font-mono text-[13px] font-medium text-mut">{r.submittedAt}</span>
                        </span>
                        <ChevronRight class="size-6 text-mut" />
                    </Link>
                </li>
            {/each}
        </ul>

        <Pagination meta={inspections.meta} links={inspections.links} noun="inspections" />
    {/if}
</div>
