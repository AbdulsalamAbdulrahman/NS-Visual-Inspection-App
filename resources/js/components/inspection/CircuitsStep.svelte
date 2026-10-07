<script lang="ts">
    import Button from '@/components/Button.svelte';
    import NumberInput from '@/components/form/NumberInput.svelte';
    import Select from '@/components/form/Select.svelte';
    import { circuitComplete } from '@/lib/inspection/checklist';
    import { CONDITIONS, CONDITION_ORDER } from '@/lib/inspection/conditions';
    import type { Circuit, Option } from '@/lib/inspection/types';
    import { num } from '@/lib/format';
    import CircuitSheet from './CircuitSheet.svelte';
    import ConditionPill from './ConditionPill.svelte';
    import Add from '~icons/ms/add';
    import Check from '~icons/ms/check';
    import Close from '~icons/ms/close';
    import Delete from '~icons/ms/delete';
    import Edit from '~icons/ms/edit';
    import ErrorIcon from '~icons/ms/error';
    import MoreVert from '~icons/ms/more-vert';

    type Props = {
        circuits: Circuit[];
        descriptions: Option[];
        showErrors: boolean;
    };

    let { circuits = $bindable(), descriptions, showErrors }: Props = $props();

    let sheetOpen = $state(false);
    /** Working copy; committed on save so Cancel leaves the list untouched. */
    let working = $state<Circuit | null>(null);
    let workingIndex = $state(0);
    let isNew = $state(false);
    /** Desktop inline editor row. */
    let inlineUuid = $state<string | null>(null);

    const descriptionLabel = (c: Circuit): string => {
        const base = descriptions.find((d) => d.value === c.description)?.label ?? 'Not set';

        return c.description === 'other' && c.description_other ? `Other · ${c.description_other}` : base;
    };

    const spec = (c: Circuit): string => `${num(c.rating_a)} A · ${num(c.conductor_mm2)} mm²`;

    function blank(): Circuit {
        return {
            uuid: crypto.randomUUID(),
            description: null,
            description_other: null,
            rating_a: null,
            conductor_mm2: null,
            condition: null,
            observation: null,
        };
    }

    function openSheet(index: number | null): void {
        isNew = index === null;
        workingIndex = index ?? circuits.length;
        working = index === null ? blank() : { ...circuits[index] };
        sheetOpen = true;
    }

    function commit(c: Circuit): void {
        const i = circuits.findIndex((x) => x.uuid === c.uuid);

        if (i === -1) {
            circuits.push({ ...c });
        } else {
            circuits[i] = { ...c };
        }
    }

    function remove(uuid: string): void {
        circuits = circuits.filter((c) => c.uuid !== uuid);

        if (inlineUuid === uuid) {
            inlineUuid = null;
        }
    }

    function addInline(): void {
        const c = blank();
        circuits.push(c);
        working = { ...c };
        inlineUuid = c.uuid;
        isNew = true;
    }

    function editInline(c: Circuit): void {
        working = { ...c };
        inlineUuid = c.uuid;
        isNew = false;
    }

    function saveInline(): void {
        if (working) {
            commit(working);
        }

        inlineUuid = null;
        working = null;
    }

    function cancelInline(): void {
        // A brand-new row that was never saved disappears on cancel.
        if (isNew && inlineUuid) {
            const c = circuits.find((x) => x.uuid === inlineUuid);

            if (c && !c.description && c.rating_a === null && !c.condition) {
                remove(inlineUuid);
            }
        }

        inlineUuid = null;
        working = null;
    }

    const conditionOptions = CONDITION_ORDER.map((k) => ({ value: k, label: CONDITIONS[k].short }));
    const cols = 'grid-cols-[48px_minmax(0,1.4fr)_96px_110px_210px_minmax(0,1.3fr)_88px]';
</script>

<!-- Phone: cards + bottom sheet (CF-08 / CF-09) -->
<div class="flex flex-col gap-2.5 lg:hidden">
    <div class="flex items-baseline justify-between px-1">
        <b class="text-base">{circuits.length} circuit{circuits.length === 1 ? '' : 's'}</b>
        {#if circuits.length > 0}<span class="text-[13px] text-mut">Tap a card to edit</span>{/if}
    </div>

    {#each circuits as c, i (c.uuid)}
        {@const incomplete = showErrors && !circuitComplete(c)}
        <button
            type="button"
            class={[
                'flex flex-col gap-2 rounded-2xl border bg-sf py-3 pr-1.5 pl-3.5 text-left',
                incomplete ? 'border-2 border-bad' : 'border-line',
            ]}
            onclick={() => openSheet(i)}
        >
            <span class="flex w-full items-center gap-2.5">
                <span class="flex size-8 flex-none items-center justify-center rounded-lg bg-sf2 font-mono text-[13px] font-semibold">C{i + 1}</span>
                <span class="flex min-w-0 flex-1 flex-col gap-0.5">
                    <b class="truncate text-base">{descriptionLabel(c)}</b>
                    <span class="font-mono text-sm font-medium text-mut">{spec(c)}</span>
                </span>
                <MoreVert class="size-6 flex-none text-mut" aria-hidden="true" />
            </span>
            <span class="flex flex-col gap-1.5 pr-2">
                {#if c.condition}
                    <ConditionPill condition={c.condition} />
                {:else}
                    <span class="flex items-center gap-1 text-[13px] font-semibold text-bad"><ErrorIcon class="size-4" />Condition required</span>
                {/if}
                {#if c.observation}<span class="text-sm leading-[1.4] text-mut">{c.observation}</span>{/if}
            </span>
        </button>
    {/each}

    <button
        type="button"
        class="flex h-14 flex-none items-center justify-center gap-2 rounded-[14px] border-2 border-dashed border-mid text-base font-bold text-brand"
        onclick={() => openSheet(null)}
    >
        <Add class="size-6" />Add circuit
    </button>
</div>

<!-- Desktop: table with an inline editor row (CK-02) -->
<div class="hidden flex-col gap-3 lg:flex">
    <div class="flex justify-end">
        <Button variant="soft" size="sm" class="bg-soft text-brand" onclick={addInline} disabled={inlineUuid !== null}>
            <Add />Add circuit
        </Button>
    </div>
    <div class="overflow-hidden rounded-2xl border border-line bg-sf" role="table" aria-label="Circuits">
        <div role="row" class="grid {cols} gap-3 border-b border-line bg-sf2 px-[18px] py-3 text-xs font-bold tracking-[0.06em] text-mut">
            <span role="columnheader">#</span>
            <span role="columnheader">CIRCUIT</span>
            <span role="columnheader">RATING</span>
            <span role="columnheader">CONDUCTOR</span>
            <span role="columnheader">CONDITION</span>
            <span role="columnheader">OBSERVATION</span>
            <span role="columnheader"><span class="sr-only">Actions</span></span>
        </div>
        {#each circuits as c, i (c.uuid)}
            {#if inlineUuid === c.uuid && working}
                <div role="row" class="grid {cols} items-center gap-3 border-b border-line bg-soft px-[18px] py-3 text-sm last:border-b-0">
                    <span role="cell" class="font-mono text-[13px] font-semibold text-brand">C{i + 1}</span>
                    <div role="cell" class="flex flex-col gap-1.5">
                        <Select id="ci-desc" size="md" class="h-[42px] rounded-[10px] text-sm" options={descriptions} placeholder="Circuit" bind:value={working.description} />
                        {#if working.description === 'other'}
                            <input
                                aria-label="Describe the circuit"
                                bind:value={working.description_other}
                                placeholder="Describe"
                                class="h-[42px] rounded-[10px] border-[1.5px] border-line bg-sf px-2.5 outline-none focus:border-2 focus:border-pri"
                            />
                        {/if}
                    </div>
                    <span role="cell"><NumberInput id="ci-rating" size="md" unit="A" class="h-[42px] rounded-[10px]" inputClass="px-2.5 text-[15px]" bind:value={working.rating_a} /></span>
                    <span role="cell"><NumberInput id="ci-cond" size="md" unit="mm²" class="h-[42px] rounded-[10px]" inputClass="px-2.5 text-[15px]" bind:value={working.conductor_mm2} /></span>
                    <span role="cell"><Select id="ci-condition" size="md" class="h-[42px] rounded-[10px] text-sm" options={conditionOptions} placeholder="Select condition" bind:value={working.condition} /></span>
                    <input
                        role="cell"
                        aria-label="Observation"
                        bind:value={working.observation}
                        placeholder="Optional"
                        class="h-[42px] rounded-[10px] border-[1.5px] border-line bg-sf px-2.5 outline-none focus:border-2 focus:border-pri"
                    />
                    <span role="cell" class="flex justify-end gap-1">
                        <button type="button" class="flex size-10 items-center justify-center rounded-[10px] bg-pri text-on-pri" aria-label="Save circuit" onclick={saveInline}>
                            <Check class="size-5" />
                        </button>
                        <button type="button" class="flex size-10 items-center justify-center rounded-[10px] text-mut hover:bg-sf2" aria-label="Cancel" onclick={cancelInline}>
                            <Close class="size-5" />
                        </button>
                    </span>
                </div>
            {:else}
                {@const incomplete = showErrors && !circuitComplete(c)}
                <div role="row" class={['grid min-h-[60px] items-center gap-3 border-b px-[18px] text-sm last:border-b-0', cols, incomplete ? 'border-bad bg-bad-bg/40' : 'border-line']}>
                    <span role="cell" class="font-mono text-[13px] font-semibold text-mut">C{i + 1}</span>
                    <b role="cell" class="truncate">{descriptionLabel(c)}</b>
                    <span role="cell" class="font-mono">{num(c.rating_a)} A</span>
                    <span role="cell" class="font-mono">{num(c.conductor_mm2)} mm²</span>
                    <span role="cell">
                        {#if c.condition}
                            <ConditionPill condition={c.condition} length="short" />
                        {:else}
                            <span class="flex items-center gap-1 text-[13px] font-semibold text-bad"><ErrorIcon class="size-4" />Required</span>
                        {/if}
                    </span>
                    <span role="cell" class="truncate text-mut">{c.observation || '—'}</span>
                    <span role="cell" class="flex justify-end gap-0.5 text-mut">
                        <button type="button" class="flex size-10 items-center justify-center rounded-[10px] hover:bg-sf2 disabled:opacity-40" aria-label="Edit C{i + 1}" disabled={inlineUuid !== null} onclick={() => editInline(c)}>
                            <Edit class="size-5" />
                        </button>
                        <button type="button" class="flex size-10 items-center justify-center rounded-[10px] hover:bg-sf2 hover:text-bad disabled:opacity-40" aria-label="Remove C{i + 1}" disabled={inlineUuid !== null} onclick={() => remove(c.uuid)}>
                            <Delete class="size-5" />
                        </button>
                    </span>
                </div>
            {/if}
        {:else}
            <p class="px-[18px] py-6 text-sm text-mut">No circuits yet. Add one row per circuit; condition is required for each.</p>
        {/each}
    </div>
</div>

<CircuitSheet
    bind:open={sheetOpen}
    circuit={working}
    label="C{workingIndex + 1}"
    {isNew}
    {descriptions}
    onsave={commit}
    onremove={remove}
/>
