<script lang="ts" module>
    export { default as layout } from '@/layouts/AdminLayout.svelte';
</script>

<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import Avatar from '@/components/Avatar.svelte';
    import MobileHeader from '@/components/MobileHeader.svelte';
    import UserMenu from '@/components/UserMenu.svelte';
    import type { InspectionRow } from '@/lib/inspection/report';
    import { overview } from '@/routes/admin';
    import { index as inspections, show } from '@/routes/admin/inspections';
    import CalendarMonth from '~icons/ms/calendar-month';
    import ExpandMore from '~icons/ms/expand-more';

    type Props = {
        month: {
            value: string;
            label: string;
            short: string;
            isCurrent: boolean;
            previousShort: string;
            options: { value: string; label: string }[];
        };
        kpis: {
            inspections: number;
            inspectionsDelta: number;
            revenue: string;
            revenueShort: string;
            contractorsActive: number;
            contractorsTotal: number;
            contractorsSuspended: number;
            drafts: number;
        };
        areas: { name: string; count: number }[];
        recent: InspectionRow[];
    };

    let { month, kpis, areas, recent }: Props = $props();

    const user = $derived(page.props.auth.user!);
    const max = $derived(Math.max(1, ...areas.map((a) => a.count)));
    const empty = $derived(areas.every((a) => a.count === 0));
    const delta = $derived(
        kpis.inspectionsDelta === 0 ? 'Same as' : `${kpis.inspectionsDelta > 0 ? '+' : '−'}${Math.abs(kpis.inspectionsDelta)} vs`,
    );
    const deltaTone = $derived(kpis.inspectionsDelta > 0 ? 'text-ok' : kpis.inspectionsDelta < 0 ? 'text-bad' : 'text-mut');

    let asTable = $state(false);
    let showAllAreas = $state(false);
    const PHONE_AREAS = 9;
    const phoneAreas = $derived(showAllAreas ? areas : areas.slice(0, PHONE_AREAS));

    function pickMonth(event: Event & { currentTarget: HTMLSelectElement }): void {
        router.get(overview.url(), { month: event.currentTarget.value }, { preserveScroll: true, replace: true });
    }

    const pct = (count: number): string => `${(count / max) * 100}%`;
</script>

<svelte:head>
    <title>Overview · KENS</title>
</svelte:head>

{#snippet monthPicker(cls: string)}
    <label class={['relative flex items-center gap-2 rounded-[10px] border border-line bg-sf px-3.5 text-sm font-semibold focus-within:outline-3 focus-within:outline-mid', cls]}>
        <CalendarMonth class="size-5 flex-none" aria-hidden="true" />
        <span class="sr-only">Month</span>
        <select class="h-full flex-1 appearance-none bg-transparent pr-6 outline-none" value={month.value} onchange={pickMonth}>
            {#each month.options as o (o.value)}
                <option value={o.value}>{o.label}</option>
            {/each}
        </select>
        <ExpandMore class="pointer-events-none absolute right-3 size-5 text-mut" aria-hidden="true" />
    </label>
{/snippet}

{#snippet tile(label: string, value: string | number, note: string, noteClass = 'text-mut')}
    <div class="flex flex-col gap-1 rounded-2xl border border-line bg-sf p-3.5 lg:gap-2 lg:px-5 lg:py-[18px]">
        <span class="text-[13px] font-semibold text-mut lg:text-sm">{label}</span>
        <span class="font-mono text-[28px] leading-tight font-semibold lg:text-[34px]">{value}</span>
        <span class={['text-xs lg:text-[13px]', noteClass]}>{note}</span>
    </div>
{/snippet}

{#snippet areaTable()}
    <table class="w-full text-sm">
        <caption class="sr-only">Submissions by service area, {month.label}</caption>
        <thead>
            <tr class="border-b border-line text-left text-xs font-bold tracking-[0.06em] text-mut">
                <th scope="col" class="py-2 font-bold">AREA</th>
                <th scope="col" class="py-2 text-right font-bold">SUBMISSIONS</th>
            </tr>
        </thead>
        <tbody>
            {#each areas as a (a.name)}
                <tr class="border-b border-line last:border-b-0">
                    <th scope="row" class="py-2 text-left font-normal">{a.name}</th>
                    <td class="py-2 text-right font-mono tabular-nums">{a.count}</td>
                </tr>
            {/each}
        </tbody>
    </table>
{/snippet}

<MobileHeader title="Overview" subtitle="{month.label} · all areas" class="lg:hidden">
    {#snippet trailing()}
        <UserMenu {user} showProfile triggerClass="rounded-full">
            {#snippet trigger()}
                <Avatar initials={user.initials} />
            {/snippet}
        </UserMenu>
    {/snippet}
    {@render monthPicker('h-11')}
</MobileHeader>

<div class="flex flex-col gap-3.5 p-4 lg:gap-6 lg:px-10 lg:py-8">
    <div class="hidden items-center gap-3 lg:flex">
        <h1 class="text-[28px] font-extrabold">Overview</h1>
        {@render monthPicker('ml-auto h-[42px] w-52')}
    </div>

    <!-- KPI tiles (AD-01 / AM-01) -->
    <div class="grid grid-cols-2 gap-2.5 lg:grid-cols-4 lg:gap-4">
        <div class="contents lg:hidden">
            {@render tile('Inspections', kpis.inspections, `${delta} ${month.previousShort}`, `font-semibold ${deltaTone}`)}
            {@render tile('Revenue', kpis.revenueShort, month.isCurrent ? 'this month' : month.short)}
            {@render tile('Active contractors', kpis.contractorsActive, `of ${kpis.contractorsTotal}`)}
            {@render tile('Drafts', kpis.drafts, 'in progress')}
        </div>
        <div class="hidden lg:contents">
            {@render tile(`Inspections · ${month.short}`, kpis.inspections, `${delta} ${month.previousShort}`, `font-semibold ${deltaTone}`)}
            {@render tile(`Revenue · ${month.short}`, kpis.revenueShort, `${kpis.revenue.replace(/\.00$/, '')} collected`)}
            {@render tile('Active contractors', kpis.contractorsActive, `of ${kpis.contractorsTotal}${kpis.contractorsSuspended ? ` · ${kpis.contractorsSuspended} suspended` : ''}`)}
            {@render tile('Drafts in progress', kpis.drafts, 'Not yet paid')}
        </div>
    </div>

    <div class="grid gap-3.5 lg:grid-cols-[1.25fr_1fr] lg:gap-4">
        <!-- Submissions by service area -->
        <section class="flex flex-col gap-2 rounded-2xl border border-line bg-sf px-4 py-3.5 lg:gap-4 lg:p-5" aria-labelledby="by-area">
            <div class="flex items-center gap-3">
                <h2 id="by-area" class="text-[15px] font-bold lg:text-base">
                    <span class="lg:hidden">By service area</span><span class="hidden lg:inline">Submissions by service area</span>
                </h2>
                <span class="ml-auto text-[13px] text-mut">{month.short}</span>
                {#if !empty}
                    <button type="button" class="hidden rounded-lg px-2 py-1 text-[13px] font-bold text-brand hover:bg-sf2 lg:block" aria-pressed={asTable} onclick={() => (asTable = !asTable)}>
                        {asTable ? 'Chart' : 'Table'}
                    </button>
                {/if}
            </div>

            {#if empty}
                <p class="py-10 text-center text-sm text-mut">No submissions in {month.label}.</p>
            {:else}
                <!-- Phone: horizontal bars, sorted, values at the tip -->
                <ul class="flex flex-col gap-2 lg:hidden" aria-label="Submissions by service area">
                    {#each phoneAreas as a (a.name)}
                        <li class="grid grid-cols-[92px_1fr_28px] items-center gap-2 text-[13px]">
                            <span class="truncate">{a.name}</span>
                            <span class="h-3 rounded-md bg-sf2" aria-hidden="true">
                                <span class="block h-full rounded-md bg-bar" style:width={pct(a.count)}></span>
                            </span>
                            <span class="text-right font-mono tabular-nums">{a.count}</span>
                        </li>
                    {/each}
                </ul>
                {#if areas.length > PHONE_AREAS}
                    <button type="button" class="self-start pt-1 text-sm font-bold text-brand lg:hidden" aria-expanded={showAllAreas} onclick={() => (showAllAreas = !showAllAreas)}>
                        {showAllAreas ? 'Show fewer' : `All ${areas.length} areas`}
                    </button>
                {/if}

                <!-- Desktop: columns, value on the cap, per-bar hover/focus tooltip -->
                {#if asTable}
                    <div class="hidden lg:block">{@render areaTable()}</div>
                {:else}
                    <!-- Screen readers get the table; the columns are visual only. -->
                    <div class="sr-only">{@render areaTable()}</div>
                    <div class="hidden min-h-[260px] flex-1 flex-col lg:flex" aria-hidden="true">
                        <div class="flex flex-1 items-end gap-[2px] border-b border-line pt-2">
                            {#each areas as a (a.name)}
                                <div class="group relative flex h-full flex-1 flex-col items-center justify-end gap-1.5 rounded-t-md hover:bg-sf2">
                                    <span class="font-mono text-xs font-medium text-mut tabular-nums">{a.count}</span>
                                    <span class="w-full max-w-6 rounded-t-[4px] bg-bar group-hover:brightness-110" style:height={pct(a.count)}></span>
                                    <span class="pointer-events-none absolute bottom-full left-1/2 z-10 mb-1 hidden -translate-x-1/2 flex-col rounded-lg bg-ink px-2.5 py-1.5 text-center whitespace-nowrap text-sf shadow-lg group-hover:flex">
                                        <b class="font-mono text-sm">{a.count}</b>
                                        <span class="text-xs opacity-80">{a.name}</span>
                                    </span>
                                </div>
                            {/each}
                        </div>
                        <div class="flex gap-[2px] pt-1.5">
                            {#each areas as a (a.name)}
                                <span class="h-[72px] flex-1 rotate-180 truncate text-center text-[11px] text-mut [writing-mode:vertical-rl]" title={a.name}>{a.name}</span>
                            {/each}
                        </div>
                    </div>
                {/if}
            {/if}
        </section>

        <!-- Recent submissions -->
        <section class="flex flex-col overflow-hidden rounded-2xl border border-line bg-sf" aria-labelledby="recent">
            <div class="flex items-center justify-between px-4 pt-3.5 pb-2.5 lg:px-5 lg:pt-[18px] lg:pb-3">
                <h2 id="recent" class="text-[15px] font-bold lg:text-base">Recent submissions</h2>
                <Link href={inspections.url()} class="text-sm font-bold">View all</Link>
            </div>
            {#if recent.length === 0}
                <p class="border-t border-line px-5 py-10 text-center text-sm text-mut">Nothing submitted yet.</p>
            {:else}
                <ul>
                    {#each recent as r (r.uuid)}
                        <li class="border-t border-line">
                            <Link href={show.url(r.uuid)} class="grid grid-cols-[minmax(0,1fr)_auto] gap-x-3 gap-y-0.5 px-4 py-2.5 text-ink no-underline hover:bg-sf2 lg:px-5">
                                <span class="truncate font-mono text-[13px] font-medium text-brand">{r.ticketNo}</span>
                                <span class="text-right font-mono text-[13px] font-medium text-mut">{r.submittedAt}</span>
                                <b class="truncate text-sm">{r.ownerName}</b>
                                <span class="text-right text-[13px] text-mut">{r.area}</span>
                            </Link>
                        </li>
                    {/each}
                </ul>
            {/if}
        </section>
    </div>
</div>
