<script lang="ts" module>
    export { default as layout } from '@/layouts/ContractorLayout.svelte';
</script>

<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import Avatar from '@/components/Avatar.svelte';
    import BottomBar from '@/components/BottomBar.svelte';
    import Button from '@/components/Button.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import MobileHeader from '@/components/MobileHeader.svelte';
    import Pagination, { type PageLinks, type PageMeta } from '@/components/Pagination.svelte';
    import SearchField from '@/components/SearchField.svelte';
    import { STEP_COUNT } from '@/lib/inspection/steps';
    import { edit, show, store } from '@/routes/inspections';
    import { show as profile } from '@/routes/profile';
    import AddCircle from '~icons/ms/add-circle';
    import ArrowForward from '~icons/ms/arrow-forward';
    import AssignmentAdd from '~icons/ms/assignment-add';
    import ChevronRight from '~icons/ms/chevron-right';
    import Print from '~icons/ms/print';

    type Card = {
        uuid: string;
        status: 'draft' | 'submitted';
        ticketNo: string | null;
        ownerName: string | null;
        address: string | null;
        area: string | null;
        step: number;
        stepLabel: string;
        savedLabel: string | null;
        submittedAt: string | null;
        amount: string | null;
    };

    type Props = {
        /** Not paginated: a contractor has a handful of drafts at most. */
        drafts: Card[];
        submitted: { data: Card[]; meta: PageMeta; links: PageLinks };
        submittedTotal: number;
        filters: { search: string };
        fee: string | null;
    };

    let { drafts, submitted, submittedTotal, filters, fee }: Props = $props();

    const user = $derived(page.props.auth.user!);
    let starting = $state(false);

    const empty = $derived(drafts.length === 0 && submittedTotal === 0);
    const where = (c: Card): string => [c.address, c.area].filter(Boolean).join(' · ') || 'No address yet';

    function start(): void {
        starting = true;
        router.post(store.url(), { uuid: crypto.randomUUID() }, { onFinish: () => (starting = false) });
    }

    const steps = $derived(['Fill sections A–D on site', fee ? `Pay the ${fee} inspection fee` : 'Pay the inspection fee', 'Get your ticket number']);
    const cols = 'grid-cols-[200px_minmax(0,1.2fr)_minmax(0,1.6fr)_130px_130px_48px]';
</script>

<svelte:head>
    <title>My inspections · KENS</title>
</svelte:head>

<MobileHeader title="My inspections" subtitle={user.badge ? `${user.name} · ${user.badge}` : user.name} class="lg:hidden">
    {#snippet trailing()}
        <Link href={profile()} aria-label="Profile" class="rounded-full">
            <Avatar initials={user.initials} />
        </Link>
    {/snippet}
    {#if !empty}
        <SearchField tone="filled" value={filters.search} placeholder="Ticket, owner or Form 74" only={['submitted', 'filters']} />
    {/if}
</MobileHeader>

<div class="mx-auto flex w-full max-w-[1344px] flex-1 flex-col gap-3 px-4 py-4 lg:gap-7 lg:px-12 lg:py-8">
    <div class="hidden items-end gap-4 lg:flex">
        <div class="flex flex-col gap-1">
            <h1 class="text-[32px] font-extrabold tracking-[-0.02em]">My inspections</h1>
            <span class="text-[15px] text-mut">
                {empty ? 'No inspections yet' : `${submittedTotal} submitted · ${drafts.length} draft${drafts.length === 1 ? '' : 's'}`}
            </span>
        </div>
        <Button size="md" class="ml-auto font-extrabold" onclick={start} disabled={starting}>
            <AddCircle />Start new inspection
        </Button>
    </div>

    {#if empty}
        <EmptyState
            icon={AssignmentAdd}
            title="No inspections yet"
            body="Start your first inspection at the property. Your progress saves as you go — even without network."
        >
            <ol class="mt-2 flex w-full max-w-[300px] flex-col gap-2 text-left">
                {#each steps as text, i (i)}
                    <li class="flex items-center gap-2.5 text-[15px]">
                        <span class="flex size-7 flex-none items-center justify-center rounded-full bg-sf2 font-mono text-[13px] font-semibold">{i + 1}</span>
                        {text}
                    </li>
                {/each}
            </ol>
        </EmptyState>
    {:else}
        {#if drafts.length > 0}
            <section class="flex flex-col gap-3" aria-labelledby="drafts-heading">
                <h2 id="drafts-heading" class="eyebrow px-1">Drafts<span class="lg:hidden"> · {drafts.length}</span></h2>
                <ul class="grid gap-3 lg:grid-cols-3 lg:gap-4">
                    {#each drafts as d, i (d.uuid)}
                        <li class="flex flex-col gap-2.5 rounded-2xl border border-line bg-sf p-3.5 lg:gap-3 lg:p-[18px]">
                            <div class="flex justify-between gap-2">
                                <div class="flex min-w-0 flex-col gap-0.5">
                                    <b class="truncate text-base lg:text-[17px]">{d.ownerName || 'Not yet named'}</b>
                                    <span class="truncate text-sm text-mut">{where(d)}</span>
                                </div>
                                <span class="font-mono text-[13px] font-medium text-mut">{d.step}/{STEP_COUNT}</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-sf2" aria-hidden="true">
                                <div class="h-full rounded-full bg-mid" style:width="{Math.round((d.step / STEP_COUNT) * 100)}%"></div>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm text-mut">{d.stepLabel}{d.savedLabel ? ` · ${d.savedLabel}` : ''}</span>
                                <Button href={edit.url(d.uuid)} size="sm" variant={i === 0 ? 'primary' : 'soft'} class="lg:h-10 lg:rounded-[10px] lg:text-sm">
                                    Resume{#if i === 0}<ArrowForward />{/if}
                                </Button>
                            </div>
                        </li>
                    {/each}
                </ul>
            </section>
        {/if}

        <section class="flex flex-col gap-3 lg:flex-1" aria-labelledby="submitted-heading">
            <div class="flex items-center gap-3 px-1 pt-2 lg:p-0">
                <h2 id="submitted-heading" class="eyebrow">Submitted<span class="lg:hidden"> · {submittedTotal}</span></h2>
                <SearchField class="ml-auto hidden w-[360px] lg:flex" value={filters.search} placeholder="Ticket, owner or Form 74" only={['submitted', 'filters']} />
            </div>

            {#if submitted.data.length === 0}
                <p class="rounded-2xl border border-line bg-sf px-4 py-5 text-sm text-mut">
                    {filters.search ? `No submitted inspections match “${filters.search}”.` : 'Paid inspections and their tickets appear here.'}
                </p>
            {:else}
                <!-- Phone list -->
                <ul class="flex flex-col rounded-2xl border border-line bg-sf lg:hidden">
                    {#each submitted.data as s (s.uuid)}
                        <li class="border-b border-line last:border-b-0">
                            <Link href={show.url(s.uuid)} class="flex items-center gap-2.5 py-3 pr-2 pl-3.5 text-ink no-underline">
                                <span class="flex min-w-0 flex-1 flex-col gap-[3px]">
                                    <span class="font-mono text-sm font-medium text-brand">{s.ticketNo}</span>
                                    <b class="truncate text-[15px]">{s.ownerName}</b>
                                    <span class="text-[13px] text-mut">{s.area} · {s.submittedAt}</span>
                                </span>
                                <ChevronRight class="size-6 text-mut" />
                            </Link>
                        </li>
                    {/each}
                </ul>

                <!-- Desktop table (CK-01) -->
                <div class="hidden overflow-hidden rounded-2xl border border-line bg-sf lg:block" role="table" aria-label="Submitted inspections">
                    <div role="row" class="grid {cols} gap-3 border-b border-line bg-sf2 px-5 py-3 text-xs font-bold tracking-[0.06em] text-mut">
                        <span role="columnheader">TICKET</span>
                        <span role="columnheader">OWNER</span>
                        <span role="columnheader">ADDRESS</span>
                        <span role="columnheader">AREA</span>
                        <span role="columnheader">DATE</span>
                        <span role="columnheader"><span class="sr-only">Print</span></span>
                    </div>
                    {#each submitted.data as s (s.uuid)}
                        <Link href={show.url(s.uuid)} role="row" class="grid h-[52px] items-center gap-3 border-b border-line px-5 text-sm text-ink no-underline last:border-b-0 hover:bg-sf2 {cols}">
                            <span role="cell" class="font-mono text-sm font-medium text-brand">{s.ticketNo}</span>
                            <b role="cell" class="truncate">{s.ownerName}</b>
                            <span role="cell" class="truncate text-mut">{s.address}</span>
                            <span role="cell">{s.area}</span>
                            <span role="cell" class="font-mono">{s.submittedAt}</span>
                            <span role="cell" class="flex justify-end text-mut"><Print class="size-[22px]" aria-hidden="true" /></span>
                        </Link>
                    {/each}
                </div>

                <Pagination meta={submitted.meta} links={submitted.links} noun="submitted" />
            {/if}
        </section>
    {/if}
</div>

<BottomBar class="lg:hidden">
    <Button size="xl" block onclick={start} disabled={starting}><AddCircle />Start new inspection</Button>
</BottomBar>
