<script lang="ts" module>
    export { default as layout } from '@/layouts/AdminLayout.svelte';
</script>

<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import Button from '@/components/Button.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import Pagination, { type PageLinks, type PageMeta } from '@/components/Pagination.svelte';
    import ListFilters from '@/components/report/ListFilters.svelte';
    import StatusPill from '@/components/StatusPill.svelte';
    import { REVIEW_TONE, type FilterOptions, type InspectionRow, type ListFilters as Filters, type ReviewStatus } from '@/lib/inspection/report';
    import { exportMethod as exportCsv, index, show } from '@/routes/admin/inspections';
    import Assignment from '~icons/ms/assignment';
    import ChevronRight from '~icons/ms/chevron-right';
    import Download from '~icons/ms/download';

    type Props = {
        inspections: { data: InspectionRow[]; meta: PageMeta; links: PageLinks };
        totalSubmitted: number;
        reviewCounts: Record<ReviewStatus, number>;
        filters: Filters;
        filterOptions: FilterOptions;
    };

    let { inspections, totalSubmitted, reviewCounts, filters, filterOptions }: Props = $props();

    const reviewChips: { value: ReviewStatus | null; label: string }[] = [
        { value: null, label: 'All' },
        { value: 'pending', label: 'Under review' },
        { value: 'changes_requested', label: 'Changes requested' },
        { value: 'approved', label: 'Approved' },
    ];

    function pickReview(review: ReviewStatus | null): void {
        const url = new URL(window.location.href);

        if (review) {
            url.searchParams.set('review', review);
        } else {
            url.searchParams.delete('review');
        }

        url.searchParams.delete('page');
        router.get(url.pathname + url.search, {}, { preserveState: true, preserveScroll: true, replace: true, only: ['inspections', 'filters', 'reviewCounts'] });
    }

    // Export uses the same query string as the list.
    const exportUrl = $derived(exportCsv.url() + (page.url.includes('?') ? page.url.slice(page.url.indexOf('?')) : ''));
    const filtered = $derived(filters.count > 0 || !!filters.search || !!filters.review);
    const cols = 'grid-cols-[180px_minmax(0,1.1fr)_minmax(0,1.3fr)_100px_minmax(0,1fr)_100px_150px_100px]';
</script>

<svelte:head>
    <title>Inspections · KENS</title>
</svelte:head>

{#snippet chips()}
    <div class="flex gap-2 overflow-x-auto [scrollbar-width:none]" role="group" aria-label="Filter by review status">
        {#each reviewChips as chip (chip.label)}
            {@const active = (filters.review ?? null) === chip.value}
            {@const count = chip.value ? reviewCounts[chip.value] : totalSubmitted}
            <button
                type="button"
                aria-pressed={active}
                class={['flex h-10 flex-none items-center gap-1.5 rounded-full px-3.5 text-sm', active ? 'bg-pri font-bold text-on-pri' : 'border border-line bg-sf font-semibold']}
                onclick={() => pickReview(chip.value)}
            >
                {chip.label}<span class={['font-mono text-xs font-medium', active ? 'opacity-80' : 'text-mut']}>{count}</span>
            </button>
        {/each}
    </div>
{/snippet}

<!-- Phone header (AM-02) -->
<header class="sticky top-0 z-20 flex flex-col gap-3 border-b border-line bg-sf px-4 pt-[calc(4px+env(safe-area-inset-top))] pb-3.5 lg:hidden">
    <div class="flex items-center gap-3 pt-2">
        <div class="flex flex-1 flex-col">
            <h1 class="text-[20px] font-bold">Inspections</h1>
            <span class="text-[13px] text-mut">{inspections.meta.total} result{inspections.meta.total === 1 ? '' : 's'}</span>
        </div>
        <a href={exportUrl} class="flex size-11 items-center justify-center rounded-xl bg-sf2 text-ink" aria-label="Export CSV"><Download class="size-6" /></a>
    </div>
    <ListFilters {filters} options={filterOptions} url={index.url()} only={['inspections', 'filters']} resultCount={inspections.meta.total} />
    {@render chips()}
</header>

<div class="flex flex-col gap-[18px] px-4 py-3 lg:px-10 lg:py-8">
    <div class="hidden items-center gap-3 lg:flex">
        <div class="flex flex-col gap-0.5">
            <h1 class="text-[28px] font-extrabold">Inspections</h1>
            <span class="text-sm text-mut">{totalSubmitted} submitted{filtered ? ` · ${inspections.meta.total} match filters` : ''}</span>
        </div>
        <Button size="sm" href={exportUrl} external class="ml-auto h-11"><Download />Export CSV</Button>
    </div>
    <div class="hidden flex-col gap-3 lg:flex">
        {@render chips()}
        <ListFilters {filters} options={filterOptions} url={index.url()} only={['inspections', 'filters']} resultCount={inspections.meta.total} />
    </div>

    {#if inspections.data.length === 0}
        <EmptyState
            icon={Assignment}
            tone="muted"
            title={filtered ? 'No inspections match these filters' : 'No submitted inspections yet'}
            body={filtered ? 'Try a wider date range or clear the filters.' : 'Paid inspections appear here as soon as contractors submit them.'}
        />
    {:else}
        <div class="hidden overflow-hidden rounded-2xl border border-line bg-sf lg:block" role="table" aria-label="Submitted inspections">
            <div role="row" class="grid {cols} gap-3 border-b border-line bg-sf2 px-5 py-3 text-xs font-bold tracking-[0.06em] text-mut">
                <span role="columnheader">TICKET</span>
                <span role="columnheader">OWNER</span>
                <span role="columnheader">ADDRESS</span>
                <span role="columnheader">AREA</span>
                <span role="columnheader">CONTRACTOR</span>
                <span role="columnheader" aria-sort="descending">DATE ↓</span>
                <span role="columnheader">REVIEW</span>
                <span role="columnheader" class="text-right">AMOUNT</span>
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
                    <span role="cell" class="text-right font-mono">{r.amount ?? '—'}</span>
                </Link>
            {/each}
        </div>

        <ul class="flex flex-col gap-2.5 lg:hidden">
            {#each inspections.data as r (r.uuid)}
                <li>
                    <Link href={show.url(r.uuid)} class="flex items-center gap-2 rounded-2xl border border-line bg-sf py-3 pr-2 pl-3.5 text-ink no-underline">
                        <span class="flex min-w-0 flex-1 flex-col gap-[3px]">
                            <span class="flex items-center gap-2">
                                <span class="font-mono text-sm font-medium text-brand">{r.ticketNo}</span>
                                {#if r.review}<StatusPill tone={REVIEW_TONE[r.review]} label={r.reviewLabel ?? ''} />{/if}
                            </span>
                            <b class="truncate text-base">{r.ownerName}</b>
                            <span class="truncate text-[13px] text-mut">{r.area} · {r.contractor}</span>
                            <span class="font-mono text-[13px] font-medium text-mut">{r.submittedAt}{r.amount ? ` · ${r.amount.replace(/\.00$/, '')}` : ''}</span>
                        </span>
                        <ChevronRight class="size-6 text-mut" />
                    </Link>
                </li>
            {/each}
        </ul>

        <Pagination meta={inspections.meta} links={inspections.links} noun="inspections" />
    {/if}
</div>
