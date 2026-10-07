<script lang="ts">
    import ChoiceGroup from '@/components/form/ChoiceGroup.svelte';
    import Field from '@/components/form/Field.svelte';
    import NumberInput from '@/components/form/NumberInput.svelte';
    import TextInput from '@/components/form/TextInput.svelte';
    import type { Draft, FormOptions } from '@/lib/inspection/types';

    type Props = { draft: Draft; options: FormOptions; errors: Record<string, string> };

    let { draft = $bindable(), options, errors }: Props = $props();

    function method(value: string): void {
        if (value !== 'other') {
            draft.wiring_method_other = null;
        }
    }
</script>

<Field id="earthing_system_type" label="Earthing system type" error={errors.earthing_system_type}>
    <ChoiceGroup label="Earthing system type" mono columns={4} class="[&>button]:min-h-[52px]" options={options.earthingSystemType} bind:value={draft.earthing_system_type} invalid={!!errors.earthing_system_type} />
</Field>

<div class="grid grid-cols-3 gap-2">
    <Field id="db_count" error={errors.db_count}>
        {#snippet labelAside()}<label for="db_count" class="min-h-8 text-[13px] leading-[1.2] font-semibold">Distribution boards</label>{/snippet}
        <NumberInput id="db_count" integer size="md" bind:value={draft.db_count} inputClass="px-3" invalid={!!errors.db_count} />
    </Field>
    <Field id="sub_circuit_count" error={errors.sub_circuit_count}>
        {#snippet labelAside()}<label for="sub_circuit_count" class="min-h-8 text-[13px] leading-[1.2] font-semibold">Sub-circuits</label>{/snippet}
        <NumberInput id="sub_circuit_count" integer size="md" bind:value={draft.sub_circuit_count} inputClass="px-3" invalid={!!errors.sub_circuit_count} />
    </Field>
    <Field id="main_cable_mm2" error={errors.main_cable_mm2}>
        {#snippet labelAside()}<label for="main_cable_mm2" class="min-h-8 text-[13px] leading-[1.2] font-semibold">Main cable</label>{/snippet}
        <NumberInput id="main_cable_mm2" unit="mm²" size="md" bind:value={draft.main_cable_mm2} inputClass="px-2.5" class="[&>span]:pr-2.5 [&>span]:text-xs" invalid={!!errors.main_cable_mm2} />
    </Field>
</div>

<Field id="conductor_type" label="Conductor type" error={errors.conductor_type}>
    <ChoiceGroup label="Conductor type" variant="segmented" compact options={options.conductorType} bind:value={draft.conductor_type} invalid={!!errors.conductor_type} />
</Field>

<Field id="wiring_method" label="Wiring method" error={errors.wiring_method}>
    <ChoiceGroup label="Wiring method" columns={4} compact class="[&>button]:px-1 [&>button]:text-sm" options={options.wiringMethod} bind:value={draft.wiring_method} onchange={method} invalid={!!errors.wiring_method} />
    {#if draft.wiring_method === 'other'}
        <TextInput id="wiring_method_other" size="md" aria-label="Describe the wiring method" placeholder="e.g. Conduit + cable tray (plant room)" bind:value={draft.wiring_method_other} />
    {/if}
</Field>

<Field id="cable_insulation" label="Cable insulation type" error={errors.cable_insulation}>
    <TextInput id="cable_insulation" size="md" placeholder="e.g. PVC, XLPE" bind:value={draft.cable_insulation} invalid={!!errors.cable_insulation} />
</Field>
