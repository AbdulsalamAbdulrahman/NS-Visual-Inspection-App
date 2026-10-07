<script lang="ts">
    import ChoiceGroup, { STANDARD_YES_NO } from '@/components/form/ChoiceGroup.svelte';
    import NumberInput from '@/components/form/NumberInput.svelte';
    import Stepper from '@/components/form/Stepper.svelte';
    import type { Draft } from '@/lib/inspection/types';
    import ErrorIcon from '~icons/ms/error';

    type Props = { draft: Draft; errors: Record<string, string> };

    let { draft = $bindable(), errors }: Props = $props();
</script>

{#snippet device(title: string, key: 'cb' | 'fuse')}
    <section class={['flex flex-col gap-3.5 rounded-2xl border bg-sf p-4', errors[key] ? 'border-2 border-bad' : 'border-line']} aria-labelledby="{key}-title">
        <h2 id="{key}-title" class="text-[17px] font-bold">{title}</h2>
        <div class="flex flex-col gap-1.5">
            <label for="{key}_rated_a" class="text-sm font-semibold text-mut">Rated current</label>
            {#if key === 'cb'}
                <NumberInput id="cb_rated_a" unit="A" class="bg-sf2" bind:value={draft.cb_rated_a} />
            {:else}
                <NumberInput id="fuse_rated_a" unit="A" class="bg-sf2" bind:value={draft.fuse_rated_a} />
            {/if}
        </div>
        <div class="flex items-center justify-between gap-3">
            <span class="text-[15px] font-semibold">Standard?</span>
            {#if key === 'cb'}
                <ChoiceGroup label="{title} standard?" variant="segmented" compact class="w-[180px]" options={STANDARD_YES_NO} bind:value={draft.cb_standard} />
            {:else}
                <ChoiceGroup label="{title} standard?" variant="segmented" compact class="w-[180px]" options={STANDARD_YES_NO} bind:value={draft.fuse_standard} />
            {/if}
        </div>
        {#if errors[key]}
            <span class="flex items-center gap-1 text-sm font-semibold text-bad"><ErrorIcon class="size-[18px]" />{errors[key]}</span>
        {/if}
    </section>
{/snippet}

{@render device('Circuit breaker', 'cb')}
{@render device('Cut-out fuse', 'fuse')}

<div class={['flex items-center justify-between rounded-2xl border bg-sf py-3 pr-3 pl-4', errors.poles ? 'border-2 border-bad' : 'border-line']}>
    <span id="poles-label" class="text-base font-semibold">Number of poles</span>
    <Stepper id="poles" label="Poles" bind:value={draft.poles} min={1} max={8} />
</div>
