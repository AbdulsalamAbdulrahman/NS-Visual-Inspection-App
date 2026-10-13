<script lang="ts">
    import ChoiceGroup, { STANDARD_YES_NO, type Choice } from '@/components/form/ChoiceGroup.svelte';
    import NumberInput from '@/components/form/NumberInput.svelte';
    import Stepper from '@/components/form/Stepper.svelte';
    import TextInput from '@/components/form/TextInput.svelte';
    import type { Draft, FormOptions } from '@/lib/inspection/types';
    import ErrorIcon from '~icons/ms/error';

    type Props = { draft: Draft; options: FormOptions; errors: Record<string, string> };

    let { draft = $bindable(), options, errors }: Props = $props();

    const AVAILABLE: Choice<boolean>[] = [
        { value: true, label: 'Available' },
        { value: false, label: 'Not available', tone: 'bad' },
    ];

    // "Not available" clears that device's details so nothing stale is kept.
    function cbAvailable(value: boolean): void {
        if (!value) {
            draft.cb_make = null;
            draft.cb_rated_a = null;
            draft.cb_standard = null;
            draft.poles = null;
        }
    }

    function fuseAvailable(value: boolean): void {
        if (!value) {
            draft.fuse_rated_a = null;
            draft.fuse_type = null;
            draft.fuse_standard = null;
            draft.fuse_poles = null;
        }
    }

    const card = 'flex flex-col gap-3.5 rounded-2xl border bg-sf p-4';
    const label = 'text-sm font-semibold text-mut';
</script>

<!-- Circuit breaker (client form, page 3) -->
<section class={[card, errors.cb ? 'border-2 border-bad' : 'border-line']} aria-labelledby="cb-title">
    <h2 id="cb-title" class="text-[17px] font-bold">Circuit breaker</h2>
    <ChoiceGroup label="Circuit breaker" variant="segmented" compact options={AVAILABLE} bind:value={draft.cb_available} onchange={cbAvailable} />
    {#if draft.cb_available}
        <div class="flex flex-col gap-1.5">
            <label for="cb_make" class={label}>Make <span class="font-normal">optional</span></label>
            <TextInput id="cb_make" class="bg-sf2" maxlength={80} placeholder="e.g. Schneider" bind:value={draft.cb_make} />
        </div>
        <div class="flex flex-col gap-1.5">
            <label for="cb_rated_a" class={label}>Rated current</label>
            <NumberInput id="cb_rated_a" unit="A" class="bg-sf2" bind:value={draft.cb_rated_a} />
        </div>
        <div class="flex items-center justify-between gap-3">
            <span class="text-[15px] font-semibold">Standard?</span>
            <ChoiceGroup label="Circuit breaker standard?" variant="segmented" compact class="w-[180px]" options={STANDARD_YES_NO} bind:value={draft.cb_standard} />
        </div>
        <div class="flex items-center justify-between gap-3">
            <span id="poles-label" class="text-[15px] font-semibold">Number of poles <span class="text-sm font-normal text-mut">max 4</span></span>
            <Stepper id="poles" label="Circuit breaker poles" bind:value={draft.poles} min={1} max={4} />
        </div>
    {/if}
    {#if errors.cb}
        <span class="flex items-center gap-1 text-sm font-semibold text-bad"><ErrorIcon class="size-[18px]" />{errors.cb}</span>
    {/if}
</section>

<!-- Cut-out fuse -->
<section class={[card, errors.fuse ? 'border-2 border-bad' : 'border-line']} aria-labelledby="fuse-title">
    <h2 id="fuse-title" class="text-[17px] font-bold">Cut-out fuse</h2>
    <ChoiceGroup label="Cut-out fuse" variant="segmented" compact options={AVAILABLE} bind:value={draft.fuse_available} onchange={fuseAvailable} />
    {#if draft.fuse_available}
        <div class="flex flex-col gap-1.5">
            <label for="fuse_rated_a" class={label}>Rated current</label>
            <NumberInput id="fuse_rated_a" unit="A" class="bg-sf2" bind:value={draft.fuse_rated_a} />
        </div>
        <div class="flex flex-col gap-1.5">
            <span class={label}>Type</span>
            <ChoiceGroup label="Cut-out fuse type" variant="segmented" compact options={options.fuseType} bind:value={draft.fuse_type} />
        </div>
        <div class="flex items-center justify-between gap-3">
            <span class="text-[15px] font-semibold">Standard?</span>
            <ChoiceGroup label="Cut-out fuse standard?" variant="segmented" compact class="w-[180px]" options={STANDARD_YES_NO} bind:value={draft.fuse_standard} />
        </div>
        <div class="flex items-center justify-between gap-3">
            <span id="fuse_poles-label" class="text-[15px] font-semibold">Number of poles <span class="text-sm font-normal text-mut">max 4</span></span>
            <Stepper id="fuse_poles" label="Cut-out fuse poles" bind:value={draft.fuse_poles} min={1} max={4} />
        </div>
    {/if}
    {#if errors.fuse}
        <span class="flex items-center gap-1 text-sm font-semibold text-bad"><ErrorIcon class="size-[18px]" />{errors.fuse}</span>
    {/if}
</section>
