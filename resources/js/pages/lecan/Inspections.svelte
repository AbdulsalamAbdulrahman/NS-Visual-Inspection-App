<script lang="ts" module>
    export { default as layout } from '@/layouts/LecanLayout.svelte';
</script>

<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import Avatar from '@/components/Avatar.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import MobileHeader from '@/components/MobileHeader.svelte';
    import Pagination, { type PageLinks, type PageMeta } from '@/components/Pagination.svelte';
    import SearchField from '@/components/SearchField.svelte';
    import StatusPill from '@/components/StatusPill.svelte';
    import UserMenu from '@/components/UserMenu.svelte';
    import { REVIEW_TONE, type InspectionRow, type ReviewStatus } from '@/lib/inspection/report';
    import { index, show } from '@/routes/lecan/inspections';
    import ChevronRight from '~icons/ms/chevron-right';
    import Inbox from '~icons/ms/inbox';

    type Tab = ReviewStatus | 'all';

    type Props = {
        inspections: { data: InspectionRow[]; meta: PageMeta; links: PageLinks };
        chapter: string | null;
        counts: Record<Tab, number>;
        filters: { review: Tab; search: string };
    };

    let { inspections, chapter, counts, filters }: Props = $props();

    const user = $derived(page.props.auth.user!);
    const queue = $derived(filters.review === 'pending_lecan');

    const tabs: { key: Tab; label: string }[] = [
        { key: 'pending_lecan', label: 'Waiting for you' },
        { key: 'pending_nsd', label: 'With NSD' },
        { key: 'changes_requested', label: 'Sent back' },
        { key: 'approved', label: 'Approved' },
        { key: 'all', label: 'All' },
    ];

    function pick(key: Tab): void {
        router.get(index.url({ query: { review: key === 'pending_lecan' ? undefined : key, search: filters.search || undefined } }), {}, {
            preserveState: true,
            preserveScroll: true,
            replace: true,
            only: ['inspections', 'filters'],
        });
    }

    const chip = 'flex h-10 flex-none items-center gap-1.5 rounded-full px-3.5 text-sm';
    const cols = 'grid-cols-[180px_minmax(0,1.1fr)_minmax(0,1.3fr)_110px_minmax(0,1fr)_110px_140px_40px]';
</script>

<svelte:head>
    <title>Reports · LECAN · KENS</title>
</svelte:head>

{#snippet chips()}
    <div class="flex gap-2 overflow-x-auto [scrollbar-width:none]" role="group" aria-label="Filter by review">
        {#each tabs as tab (tab.key)}
            {@const active = filters.review === tab.key}
            <button type="button" aria-pressed={active} class={[chip, active ? 'bg-pri font-bold text-on-pri' : 'border border-line bg-sf font-semibold']} onclick={() => pick(tab.key)}>
                {tab.label}<span class={['font-mono text-xs font-medium', active ? 'opacity-80' : 'text-mut']}>{counts[tab.key]}</span>
            </button>
        {/each}
    </div>
{/snippet}

<MobileHeader title="Reports" subtitle={`${counts.pending_lecan} waiting · ${chapter ?? ''} chapter`} class="md:hidden">
    {#snippet trailing()}
        <UserMenu {user} triggerClass="rounded-full">
            {#snippet trigger()}<Avatar initials={user.initials} />{/snippet}
        </UserMenu>
    {/snippet}
    <SearchField tone="filled" value={filters.search} placeholder="Ticket, owner or Form 74" only={['inspections', 'filters']} />
    {@render chips()}
</MobileHeader>

<div class="flex flex-col gap-[18px] px-4 py-3 md:px-6 md:py-5 lg:px-12 lg:py-8">
    <div class="hidden flex-col gap-0.5 md:flex">
        <h1 class="text-[20px] font-bold lg:text-[28px] lg:font-extrabold">Reports · {chapter} State chapter</h1>
        <span class="text-sm text-mut">Review each paid report first; approved ones go to the New Service Department for final approval.</span>
    </div>
    <div class="hidden flex-wrap items-center gap-2 md:flex">
        {@render chips()}
        <SearchField class="ml-auto w-[320px]" value={filters.search} placeholder="Ticket, owner or Form 74" only={['inspections', 'filters']} />
    </div>

    {#if inspections.data.length === 0}
        <EmptyState
            icon={Inbox}
            tone="muted"
            title={queue && !filters.search ? 'Nothing waiting for you' : 'No reports match'}
            body={queue && !filters.search ? `Paid reports from ${chapter} State appear here for LECAN review.` : 'Try another filter or clear the search.'}
        />
    {:else}
        <div class="hidden overflow-hidden rounded-2xl border border-line bg-sf lg:block" role="table" aria-label="Reports">
            <div role="row" class="grid {cols} gap-3 border-b border-line bg-sf2 px-5 py-3 text-xs font-bold tracking-[0.06em] text-mut">
                <span role="columnheader">TICKET</span>
                <span role="columnheader">OWNER</span>
                <span role="columnheader">ADDRESS</span>
                <span role="columnheader">AREA</span>
                <span role="columnheader">CONTRACTOR</span>
                <span role="columnheader" aria-sort={queue ? 'ascending' : 'descending'}>PAID {queue ? '↑' : '↓'}</span>
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
                    <span role="cell" class="flex justify-end text-mut"><ChevronRight class="size-6" aria-hidden="true" /></span>
                </Link>
            {/each}
        </div>

        <ul class="flex flex-col gap-2.5 md:gap-0 md:overflow-hidden md:rounded-2xl md:border md:border-line md:bg-sf lg:hidden">
            {#each inspections.data as r (r.uuid)}
                <li class="md:border-b md:border-line md:last:border-b-0">
                    <Link href={show.url(r.uuid)} class="flex min-h-[72px] items-center gap-2 rounded-2xl border border-line bg-sf py-3 pr-2 pl-3.5 text-ink no-underline md:rounded-none md:border-0 md:pl-5">
                        <span class="flex min-w-0 flex-1 flex-col gap-[3px]">
                            <span class="flex flex-wrap items-center gap-2">
                                <span class="font-mono text-sm font-medium text-brand">{r.ticketNo}</span>
                                {#if r.review}<StatusPill tone={REVIEW_TONE[r.review]} label={r.reviewLabel ?? ''} />{/if}
                            </span>
                            <b class="truncate text-base">{r.ownerName}</b>
                            <span class="truncate text-[13px] text-mut">{r.area} · {r.contractor} · {r.submittedAt}</span>
                        </span>
                        <ChevronRight class="size-6 flex-none text-mut" aria-hidden="true" />
                    </Link>
                </li>
            {/each}
        </ul>

        <Pagination meta={inspections.meta} links={inspections.links} noun="reports" />
    {/if}
</div>
