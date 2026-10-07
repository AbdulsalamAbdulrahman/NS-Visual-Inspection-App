<script lang="ts" module>
    export { default as layout } from '@/layouts/AdminLayout.svelte';
</script>

<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import Button from '@/components/Button.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import MobileHeader from '@/components/MobileHeader.svelte';
    import Pagination, { type PageLinks, type PageMeta } from '@/components/Pagination.svelte';
    import SearchField from '@/components/SearchField.svelte';
    import StatusPill from '@/components/StatusPill.svelte';
    import { paymentTone, type PaymentStatus } from '@/lib/status';
    import { show } from '@/routes/admin/inspections';
    import { exportMethod as exportCsv } from '@/routes/admin/payments';
    import { more } from '@/routes/admin';
    import Download from '~icons/ms/download';
    import PaymentsIcon from '~icons/ms/payments';

    type Row = {
        id: number;
        reference: string;
        ticketNo: string | null;
        inspectionUuid: string | null;
        contractor: string | null;
        amount: string;
        channel: string | null;
        status: PaymentStatus;
        statusLabel: string;
        date: string | null;
    };

    type Props = {
        payments: { data: Row[]; meta: PageMeta; links: PageLinks };
        filters: { status: PaymentStatus | null; search: string };
        summary: { month: string; collected: string; successful: number; failed: number; abandoned: number };
    };

    let { payments, filters, summary }: Props = $props();

    const statuses: { value: PaymentStatus | null; label: string }[] = [
        { value: null, label: 'All' },
        { value: 'paid', label: 'Successful' },
        { value: 'failed', label: 'Failed' },
        { value: 'abandoned', label: 'Abandoned' },
    ];

    const exportUrl = $derived(exportCsv.url() + (page.url.includes('?') ? page.url.slice(page.url.indexOf('?')) : ''));
    const only = ['payments', 'filters'];

    function pickStatus(status: PaymentStatus | null): void {
        const url = new URL(window.location.href);

        if (status) {
            url.searchParams.set('status', status);
        } else {
            url.searchParams.delete('status');
        }

        url.searchParams.delete('page');
        router.get(url.pathname + url.search, {}, { preserveState: true, preserveScroll: true, replace: true, only });
    }

    const cols = 'grid-cols-[minmax(0,230px)_minmax(0,200px)_minmax(0,1.3fr)_100px_110px_120px_130px]';
</script>

<svelte:head>
    <title>Payments · KENS</title>
</svelte:head>

{#snippet chips()}
    <div class="flex gap-2 overflow-x-auto [scrollbar-width:none]" role="group" aria-label="Filter by status">
        {#each statuses as s (s.label)}
            {@const active = filters.status === s.value}
            <button
                type="button"
                aria-pressed={active}
                class={['flex h-10 flex-none items-center rounded-full px-3.5 text-sm', active ? 'bg-pri font-bold text-on-pri' : 'border border-line bg-sf font-semibold']}
                onclick={() => pickStatus(s.value)}
            >
                {s.label}
            </button>
        {/each}
    </div>
{/snippet}

<MobileHeader title="Payments" backHref={more.url()} class="lg:hidden">
    {#snippet trailing()}
        <a href={exportUrl} class="flex size-12 items-center justify-center rounded-xl text-ink" aria-label="Export CSV"><Download class="size-6" /></a>
    {/snippet}
    <SearchField value={filters.search} placeholder="Reference, ticket or contractor" tone="filled" {only} />
    {@render chips()}
</MobileHeader>

<div class="flex flex-col gap-[18px] px-4 py-3.5 lg:px-10 lg:py-8">
    <div class="hidden items-center gap-3 lg:flex">
        <h1 class="text-[28px] font-extrabold">Payments</h1>
        <Button variant="outline" size="sm" href={exportUrl} external class="ml-auto h-11 border-[1.5px] bg-sf"><Download />Export</Button>
    </div>

    <dl class="grid grid-cols-3 gap-x-4 gap-y-3 rounded-2xl border border-line bg-sf px-4 py-3.5 lg:flex lg:gap-7 lg:px-5 lg:py-4">
        <div class="col-span-3 flex flex-col gap-0.5 lg:col-span-1">
            <dt class="text-[13px] font-semibold text-mut">Collected · {summary.month}</dt>
            <dd class="font-mono text-[22px] font-semibold">{summary.collected.replace(/\.00$/, '')}</dd>
        </div>
        <div class="hidden w-px bg-line lg:block" aria-hidden="true"></div>
        <div class="flex flex-col gap-0.5">
            <dt class="text-[13px] font-semibold text-mut">Successful</dt>
            <dd class="font-mono text-[22px] font-semibold text-ok">{summary.successful}</dd>
        </div>
        <div class="flex flex-col gap-0.5">
            <dt class="text-[13px] font-semibold text-mut">Failed</dt>
            <dd class="font-mono text-[22px] font-semibold text-bad">{summary.failed}</dd>
        </div>
        <div class="flex flex-col gap-0.5">
            <dt class="text-[13px] font-semibold text-mut">Abandoned</dt>
            <dd class="font-mono text-[22px] font-semibold text-imp">{summary.abandoned}</dd>
        </div>
    </dl>

    <div class="hidden items-center gap-2 lg:flex">
        {@render chips()}
        <SearchField value={filters.search} placeholder="Reference, ticket or contractor" {only} class="ml-auto w-80" />
    </div>

    {#if payments.data.length === 0}
        <EmptyState
            icon={PaymentsIcon}
            tone="muted"
            title={filters.status || filters.search ? 'No payments match' : 'No payments yet'}
            body={filters.status || filters.search ? 'Try another status or search term.' : 'Monnify payment attempts appear here as contractors pay.'}
        />
    {:else}
        <div class="hidden overflow-hidden rounded-2xl border border-line bg-sf lg:block" role="table" aria-label="Payments">
            <div role="row" class="grid {cols} gap-3 border-b border-line bg-sf2 px-5 py-3 text-xs font-bold tracking-[0.06em] text-mut">
                <span role="columnheader">MONNIFY REF.</span>
                <span role="columnheader">TICKET</span>
                <span role="columnheader">CONTRACTOR</span>
                <span role="columnheader" class="text-right">AMOUNT</span>
                <span role="columnheader">CHANNEL</span>
                <span role="columnheader">STATUS</span>
                <span role="columnheader">DATE</span>
            </div>
            {#each payments.data as p (p.id)}
                <div role="row" class="grid h-[52px] items-center gap-3 border-b border-line px-5 text-sm last:border-b-0 {cols}">
                    <span role="cell" class="truncate font-mono text-[13px] font-medium">{p.reference}</span>
                    <span role="cell" class="truncate font-mono text-[13px] font-medium">
                        {#if p.inspectionUuid}
                            <Link href={show.url(p.inspectionUuid)} class="text-brand">{p.ticketNo}</Link>
                        {:else}
                            <span class="text-mut">—</span>
                        {/if}
                    </span>
                    <span role="cell" class="truncate">{p.contractor ?? '—'}</span>
                    <span role="cell" class="text-right font-mono">{p.amount.replace(/\.00$/, '')}</span>
                    <span role="cell">{p.channel ?? '—'}</span>
                    <span role="cell"><StatusPill tone={paymentTone[p.status]} label={p.statusLabel} /></span>
                    <span role="cell" class="font-mono text-[13px]">{p.date}</span>
                </div>
            {/each}
        </div>

        <ul class="flex flex-col gap-2.5 lg:hidden">
            {#each payments.data as p (p.id)}
                <li class="flex flex-col gap-1.5 rounded-2xl border border-line bg-sf px-3.5 py-3">
                    <div class="flex items-center gap-2">
                        <span class="min-w-0 flex-1 truncate font-mono text-[13px] font-medium">{p.reference}</span>
                        <StatusPill tone={paymentTone[p.status]} label={p.statusLabel} />
                    </div>
                    <div class="flex items-baseline gap-2">
                        <b class="min-w-0 flex-1 truncate text-base">{p.contractor ?? '—'}</b>
                        <span class="font-mono text-[15px] font-semibold">{p.amount.replace(/\.00$/, '')}</span>
                    </div>
                    <div class="flex items-center gap-2 text-[13px] text-mut">
                        {#if p.inspectionUuid}
                            <Link href={show.url(p.inspectionUuid)} class="font-mono font-medium text-brand">{p.ticketNo}</Link>
                        {:else}
                            <span>No ticket</span>
                        {/if}
                        <span class="ml-auto font-mono">{[p.channel, p.date].filter(Boolean).join(' · ')}</span>
                    </div>
                </li>
            {/each}
        </ul>

        <Pagination meta={payments.meta} links={payments.links} noun="payments" />
    {/if}
</div>
