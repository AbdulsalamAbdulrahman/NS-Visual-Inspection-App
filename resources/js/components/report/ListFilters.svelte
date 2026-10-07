<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { Popover } from 'bits-ui';
    import ActionSheet from '@/components/ActionSheet.svelte';
    import Button from '@/components/Button.svelte';
    import ChoiceGroup from '@/components/form/ChoiceGroup.svelte';
    import SearchField from '@/components/SearchField.svelte';
    import type { FilterOptions, ListFilters } from '@/lib/inspection/report';
    import Check from '~icons/ms/check';
    import CheckBox from '~icons/ms/check-box';
    import CheckBoxOutlineBlank from '~icons/ms/check-box-outline-blank';
    import Close from '~icons/ms/close';
    import DateRange from '~icons/ms/date-range';
    import ExpandMore from '~icons/ms/expand-more';
    import Tune from '~icons/ms/tune';

    type Props = {
        filters: ListFilters;
        options: FilterOptions;
        /** Page the filters reload (current list URL without query). */
        url: string;
        /** Props to reload. */
        only: string[];
        /** Reps filter areas with chips on the page instead. */
        showAreas?: boolean;
        /** "Show 64 results" in the phone sheet. */
        resultCount: number;
    };

    let { filters, options, url, only, showAreas = true, resultCount }: Props = $props();

    // Phone sheet works on a draft and applies on "Show results".
    let sheetOpen = $state(false);
    let draft = $state({ areas: [] as number[], purpose: null as string | null, connection: 'any' as string, from: '', to: '' });

    function apply(next: Partial<ListFilters>): void {
        const merged = { ...filters, ...next };
        const query: Record<string, string> = {};

        if (merged.search) query.search = merged.search;
        if (merged.areas.length) query.areas = merged.areas.join(',');
        if (merged.purpose) query.purpose = merged.purpose;
        if (merged.connection) query.connection = merged.connection;
        if (merged.from) query.from = merged.from;
        if (merged.to) query.to = merged.to;
        // Review chips live outside this toolbar; keep their choice.
        if (merged.review) query.review = merged.review;

        router.get(url, query, { preserveState: true, preserveScroll: true, replace: true, only });
    }

    function openSheet(): void {
        draft = {
            areas: [...filters.areas],
            purpose: filters.purpose,
            connection: filters.connection ?? 'any',
            from: filters.from ?? '',
            to: filters.to ?? '',
        };
        sheetOpen = true;
    }

    function applySheet(): void {
        sheetOpen = false;
        apply({
            areas: draft.areas,
            purpose: draft.purpose,
            connection: draft.connection === 'any' ? null : draft.connection,
            from: draft.from || null,
            to: draft.to || null,
        });
    }

    function toggleArea(list: number[], id: number): number[] {
        return list.includes(id) ? list.filter((x) => x !== id) : [...list, id];
    }

    const areaName = (id: number): string => options.areas.find((a) => a.id === id)?.name ?? '';
    const label = (list: { value: string; label: string }[], v: string | null): string => list.find((o) => o.value === v)?.label ?? '';
    const shortDate = (iso: string | null): string =>
        iso ? new Date(`${iso}T00:00:00`).toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' }) : '…';

    const pill = 'flex h-11 items-center gap-1.5 rounded-xl border-[1.5px] px-3 text-sm';
    const idle = 'border-line bg-sf font-semibold';
    const active = 'border-pri bg-soft font-bold text-brand';
    const panel = 'z-50 flex min-w-[220px] flex-col rounded-xl border border-line bg-sf p-1.5 text-sm shadow-[0_10px_30px_rgba(0,0,0,.15)] outline-none';
    const option = 'flex min-h-10 items-center gap-2.5 rounded-lg px-2.5 text-left hover:bg-sf2';
</script>

<!-- Desktop toolbar (AD-02 / SR-D1) -->
<div class="hidden flex-wrap items-center gap-2.5 lg:flex">
    <SearchField class="w-[340px]" value={filters.search} placeholder="Ticket, owner or Form 74 no." {only} />

    {#if showAreas}
        <Popover.Root>
            <Popover.Trigger class={[pill, filters.areas.length ? active : idle]}>
                {filters.areas.length ? `Area: ${filters.areas.map(areaName).join(', ')}` : 'Area'}
                <ExpandMore class="size-[18px]" />
            </Popover.Trigger>
            <Popover.Content sideOffset={6} align="start" class={[panel, 'max-h-[360px] overflow-y-auto']}>
                {#each options.areas as area (area.id)}
                    {@const on = filters.areas.includes(area.id)}
                    <button type="button" role="checkbox" aria-checked={on} class={option} onclick={() => apply({ areas: toggleArea(filters.areas, area.id) })}>
                        {#if on}<CheckBox class="size-5 text-mid" />{:else}<CheckBoxOutlineBlank class="size-5 text-line" />{/if}
                        {area.name}
                    </button>
                {/each}
            </Popover.Content>
        </Popover.Root>
    {/if}

    {#each [{ key: 'purpose', name: 'Purpose', list: options.purpose }, { key: 'connection', name: 'Connection', list: options.connection }] as f (f.key)}
        {@const value = filters[f.key as 'purpose' | 'connection']}
        <Popover.Root>
            <Popover.Trigger class={[pill, value ? active : idle]}>
                {value ? label(f.list, value) : f.name}
                <ExpandMore class="size-[18px]" />
            </Popover.Trigger>
            <Popover.Content sideOffset={6} align="start" class={panel}>
                <button type="button" class={option} onclick={() => apply({ [f.key]: null })}>Any</button>
                {#each f.list as o (o.value)}
                    <button type="button" class={option} onclick={() => apply({ [f.key]: o.value })}>
                        {#if value === o.value}<Check class="size-5 text-mid" />{:else}<span class="size-5"></span>{/if}{o.label}
                    </button>
                {/each}
            </Popover.Content>
        </Popover.Root>
    {/each}

    <Popover.Root>
        <Popover.Trigger class={[pill, filters.from || filters.to ? active : idle]}>
            <DateRange class="size-[18px]" />
            {#if filters.from || filters.to}
                <span class="font-mono font-medium">{shortDate(filters.from)} – {shortDate(filters.to)}</span>
            {:else}
                Any date
            {/if}
        </Popover.Trigger>
        <Popover.Content sideOffset={6} align="start" class={[panel, 'gap-2 p-3']}>
            <label class="flex flex-col gap-1 font-semibold">From
                <input type="date" value={filters.from ?? ''} onchange={(e) => apply({ from: e.currentTarget.value || null })} class="h-11 rounded-[10px] border-[1.5px] border-line bg-sf px-2.5 font-mono font-medium" />
            </label>
            <label class="flex flex-col gap-1 font-semibold">To
                <input type="date" value={filters.to ?? ''} onchange={(e) => apply({ to: e.currentTarget.value || null })} class="h-11 rounded-[10px] border-[1.5px] border-line bg-sf px-2.5 font-mono font-medium" />
            </label>
        </Popover.Content>
    </Popover.Root>

    {#if filters.count > 0 || filters.search}
        <button type="button" class="px-1.5 text-sm font-bold text-brand hover:underline" onclick={() => apply({ search: '', areas: [], purpose: null, connection: null, from: null, to: null })}>
            Clear all
        </button>
    {/if}
</div>

<!-- Phone: search + Filters with count (AM-02) -->
<div class="flex gap-2 lg:hidden">
    <SearchField class="flex-1" tone="filled" value={filters.search} placeholder="Ticket, owner, Form 74" {only} />
    <button type="button" class={['flex h-12 items-center gap-1.5 rounded-xl px-3 text-[15px] font-bold', filters.count ? 'border-[1.5px] border-pri bg-soft text-brand' : 'bg-sf2 text-ink']} onclick={openSheet}>
        <Tune class="size-5" />Filters
        {#if filters.count}
            <span class="flex h-[22px] min-w-[22px] items-center justify-center rounded-full bg-pri px-1 font-mono text-xs font-semibold text-on-pri">{filters.count}</span>
        {/if}
    </button>
</div>

<!-- AM-03 -->
<ActionSheet bind:open={sheetOpen} title="Filters" bare>
    <div class="flex items-center px-5 pt-1 pb-2">
        <b class="flex-1 text-[20px]">Filters</b>
        <button type="button" class="py-2.5 text-[15px] font-bold text-brand" onclick={() => (draft = { areas: [], purpose: null, connection: 'any', from: '', to: '' })}>Reset</button>
    </div>
    <div class="flex flex-col gap-[18px] overflow-y-auto px-5 py-1">
        {#if showAreas}
            <fieldset class="flex flex-col gap-2">
                <legend class="mb-2 text-sm font-bold">Service area</legend>
                <div class="flex flex-wrap gap-1.5 text-sm font-semibold">
                    {#each options.areas as area (area.id)}
                        {@const on = draft.areas.includes(area.id)}
                        <button type="button" aria-pressed={on} class={['flex h-10 items-center gap-1 rounded-[10px] px-3', on ? 'border-2 border-pri bg-soft font-bold text-brand' : 'border-[1.5px] border-line']} onclick={() => (draft.areas = toggleArea(draft.areas, area.id))}>
                            {#if on}<Check class="size-[17px]" />{/if}{area.name}
                        </button>
                    {/each}
                </div>
            </fieldset>
        {/if}
        <fieldset class="flex flex-col gap-2">
            <legend class="mb-2 text-sm font-bold">Purpose of property</legend>
            <div class="grid grid-cols-2 gap-1.5 text-sm font-semibold">
                {#each options.purpose as o (o.value)}
                    {@const on = draft.purpose === o.value}
                    <button type="button" aria-pressed={on} class={['flex h-11 items-center justify-center gap-1 rounded-[10px]', on ? 'border-2 border-pri bg-soft font-bold text-brand' : 'border-[1.5px] border-line']} onclick={() => (draft.purpose = on ? null : o.value)}>
                        {#if on}<Check class="size-[17px]" />{/if}{o.label}
                    </button>
                {/each}
            </div>
        </fieldset>
        <fieldset class="flex flex-col gap-2">
            <legend class="mb-2 text-sm font-bold">Connection type</legend>
            <ChoiceGroup label="Connection type" variant="segmented" compact options={[{ value: 'any', label: 'Any' }, ...options.connection.map((o) => ({ value: o.value, label: o.label.replace(' phase', '') }))]} bind:value={draft.connection} />
        </fieldset>
        <fieldset class="flex flex-col gap-2">
            <legend class="mb-2 text-sm font-bold">Date range</legend>
            <div class="grid grid-cols-2 gap-2">
                <input type="date" aria-label="From" bind:value={draft.from} class="h-12 rounded-[10px] border-2 border-line bg-sf px-3 font-mono text-[15px] font-medium focus:border-pri" />
                <input type="date" aria-label="To" bind:value={draft.to} class="h-12 rounded-[10px] border-2 border-line bg-sf px-3 font-mono text-[15px] font-medium focus:border-pri" />
            </div>
        </fieldset>
    </div>
    <div class="mt-3 border-t border-line px-4 pt-3">
        <Button block onclick={applySheet}>{filters.count ? 'Show results' : `Show ${resultCount} result${resultCount === 1 ? '' : 's'}`}</Button>
    </div>
</ActionSheet>

{#if filters.count > 0}
    <!-- Applied filters as removable pills on phones -->
    <div class="flex flex-wrap gap-1.5 lg:hidden">
        {#each filters.areas as id (id)}
            <button type="button" class="flex h-8 items-center gap-1 rounded-lg bg-soft pr-1.5 pl-2.5 text-[13px] font-bold text-brand" onclick={() => apply({ areas: filters.areas.filter((x) => x !== id) })}>
                {areaName(id)}<Close class="size-4" />
            </button>
        {/each}
        {#if filters.purpose}
            <button type="button" class="flex h-8 items-center gap-1 rounded-lg bg-soft pr-1.5 pl-2.5 text-[13px] font-bold text-brand" onclick={() => apply({ purpose: null })}>
                {label(options.purpose, filters.purpose)}<Close class="size-4" />
            </button>
        {/if}
        {#if filters.connection}
            <button type="button" class="flex h-8 items-center gap-1 rounded-lg bg-soft pr-1.5 pl-2.5 text-[13px] font-bold text-brand" onclick={() => apply({ connection: null })}>
                {label(options.connection, filters.connection)}<Close class="size-4" />
            </button>
        {/if}
        {#if filters.from || filters.to}
            <button type="button" class="flex h-8 items-center gap-1 rounded-lg bg-soft pr-1.5 pl-2.5 font-mono text-[13px] font-bold text-brand" onclick={() => apply({ from: null, to: null })}>
                {shortDate(filters.from)} – {shortDate(filters.to)}<Close class="size-4" />
            </button>
        {/if}
    </div>
{/if}
