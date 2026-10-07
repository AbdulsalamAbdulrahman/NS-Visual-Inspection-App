<script lang="ts">
    import ChoiceGroup, { YES_NO } from '@/components/form/ChoiceGroup.svelte';
    import Field from '@/components/form/Field.svelte';
    import NumberInput from '@/components/form/NumberInput.svelte';
    import { LIMITS, warnings } from '@/lib/inspection/checklist';
    import type { Draft } from '@/lib/inspection/types';
    import Straighten from '~icons/ms/straighten';
    import Warning from '~icons/ms/warning';

    type Props = { draft: Draft; errors: Record<string, string> };

    let { draft = $bindable(), errors }: Props = $props();

    const warn = $derived(warnings(draft));
</script>

{#snippet minimum(id: string, text: string)}
    <span id={id} class="flex items-center gap-1 text-sm text-mut"><Straighten class="size-[17px]" />{text}</span>
{/snippet}

{#snippet softWarning(id: string, title: string)}
    <div id={id} class="flex items-start gap-2 rounded-xl bg-imp-bg px-3.5 py-3" role="status">
        <Warning class="size-5 flex-none text-imp" />
        <span class="text-sm leading-[1.45]"><b class="text-imp">{title}</b> You can continue — this reading will be highlighted on the report.</span>
    </div>
{/snippet}

<Field id="earth_electrode_ft" label="Earth electrode size" error={errors.earth_electrode_ft}>
    <NumberInput id="earth_electrode_ft" unit="ft" bind:value={draft.earth_electrode_ft} warn={!!warn.electrode} invalid={!!errors.earth_electrode_ft} describedBy="electrode-hint" />
    {#if warn.electrode}
        {@render softWarning('electrode-hint', warn.electrode)}
    {:else}
        {@render minimum('electrode-hint', `Minimum ${LIMITS.minElectrodeFt} ft`)}
    {/if}
</Field>

<Field id="earth_conductor_mm2" label="Earth conductor size" error={errors.earth_conductor_mm2}>
    <NumberInput id="earth_conductor_mm2" unit="mm²" bind:value={draft.earth_conductor_mm2} warn={!!warn.conductor} invalid={!!errors.earth_conductor_mm2} describedBy="conductor-hint" />
    {#if warn.conductor}
        {@render softWarning('conductor-hint', warn.conductor)}
    {:else}
        {@render minimum('conductor-hint', `Minimum ${LIMITS.minEarthConductorMm2} mm²`)}
    {/if}
</Field>

<Field id="earth_resistance_ohm" label="Earth resistance value" error={errors.earth_resistance_ohm}>
    <NumberInput id="earth_resistance_ohm" unit="Ω" bind:value={draft.earth_resistance_ohm} warn={!!warn.resistance} invalid={!!errors.earth_resistance_ohm} describedBy="resistance-hint" />
    {#if warn.resistance}
        {@render softWarning('resistance-hint', warn.resistance)}
    {:else}
        {@render minimum('resistance-hint', `${LIMITS.maxEarthResistanceOhm} Ω or less`)}
    {/if}
</Field>

<Field id="earth_pit" label="Earth inspection pit available?" error={errors.earth_pit}>
    <ChoiceGroup label="Earth inspection pit available?" variant="segmented" options={YES_NO} bind:value={draft.earth_pit} invalid={!!errors.earth_pit} />
</Field>
