<script lang="ts">
    import ChoiceGroup from '@/components/form/ChoiceGroup.svelte';
    import Field from '@/components/form/Field.svelte';
    import Select from '@/components/form/Select.svelte';
    import TextInput from '@/components/form/TextInput.svelte';
    import type { Draft, FormOptions } from '@/lib/inspection/types';
    import GpsField from './GpsField.svelte';
    import Required from './Required.svelte';
    import Lock from '~icons/ms/lock';

    type Props = {
        draft: Draft;
        options: FormOptions;
        areas: { id: number; name: string }[];
        errors: Record<string, string>;
    };

    let { draft = $bindable(), options, areas, errors }: Props = $props();

    const areaOptions = $derived(areas.map((a) => ({ value: a.id, label: a.name })));
</script>

<Field id="form74_no" error={errors.form74_no}>
    {#snippet labelAside()}<label for="form74_no" class="text-[15px] font-semibold">Form 74 number <Required /></label>{/snippet}
    <TextInput id="form74_no" mono bind:value={draft.form74_no} placeholder="e.g. F74/KD/2026/08841" autocapitalize="characters" invalid={!!errors.form74_no} />
</Field>

<Field id="owner_name" error={errors.owner_name}>
    {#snippet labelAside()}<label for="owner_name" class="text-[15px] font-semibold">Property owner name <Required /></label>{/snippet}
    <TextInput id="owner_name" bind:value={draft.owner_name} autocomplete="off" autocapitalize="words" invalid={!!errors.owner_name} />
</Field>

<Field id="property_address" error={errors.property_address}>
    {#snippet labelAside()}<label for="property_address" class="text-[15px] font-semibold">Property address <Required /></label>{/snippet}
    <textarea
        id="property_address"
        bind:value={draft.property_address}
        rows="3"
        maxlength="1000"
        aria-invalid={!!errors.property_address || undefined}
        class={[
            'min-h-[84px] w-full rounded-xl bg-sf p-3.5 text-[17px] leading-[1.4] outline-none',
            errors.property_address ? 'border-2 border-bad' : 'border-[1.5px] border-line focus:border-2 focus:border-pri',
        ]}
    ></textarea>
</Field>

<Field id="purpose" error={errors.purpose}>
    {#snippet labelAside()}<span id="purpose-label" class="text-[15px] font-semibold">Purpose of property <Required /></span>{/snippet}
    <ChoiceGroup label="Purpose of property" options={options.purpose} bind:value={draft.purpose} invalid={!!errors.purpose} />
</Field>

<Field id="service_area_id" hint="Decides which area office can view this report." error={errors.service_area_id}>
    {#snippet labelAside()}<label for="service_area_id" class="text-[15px] font-semibold">Service area <Required /></label>{/snippet}
    <Select
        id="service_area_id"
        options={areaOptions}
        placeholder="Select service area"
        bind:value={draft.service_area_id}
        invalid={!!errors.service_area_id}
    />
</Field>

<Field id="connection_type" error={errors.connection_type}>
    {#snippet labelAside()}<span class="text-[15px] font-semibold">Connection type <Required /></span>{/snippet}
    <ChoiceGroup label="Connection type" variant="segmented" options={options.connectionType} bind:value={draft.connection_type} invalid={!!errors.connection_type} />
</Field>

<Field id="voltage_level" error={errors.voltage_level}>
    {#snippet labelAside()}<span class="text-[15px] font-semibold">Voltage level <Required /></span>{/snippet}
    <ChoiceGroup label="Voltage level" mono columns={4} options={options.voltageLevel} bind:value={draft.voltage_level} invalid={!!errors.voltage_level} />
</Field>

<div class="flex flex-col gap-2.5">
    <span class="text-[15px] font-semibold">GPS location <Required /></span>
    <GpsField
        bind:lat={draft.gps_lat}
        bind:lng={draft.gps_lng}
        bind:accuracy={draft.gps_accuracy_m}
        bind:capturedAt={draft.gps_captured_at}
        error={errors.gps}
    />
</div>

<div class="flex items-center justify-between rounded-xl bg-sf2 px-4 py-3.5">
    <span class="flex items-center gap-1.5 text-[15px] font-semibold"><Lock class="size-5 text-mut" />Inspection date</span>
    <span class="font-mono text-base font-medium">{draft.inspection_date}</span>
</div>
