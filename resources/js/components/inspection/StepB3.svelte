<script lang="ts">
    import type { Snippet } from 'svelte';
    import ChoiceGroup, { STANDARD } from '@/components/form/ChoiceGroup.svelte';
    import NumberInput from '@/components/form/NumberInput.svelte';
    import Stepper from '@/components/form/Stepper.svelte';
    import TextInput from '@/components/form/TextInput.svelte';
    import type { Draft, FormOptions } from '@/lib/inspection/types';
    import ErrorIcon from '~icons/ms/error';

    type Props = { draft: Draft; options: FormOptions; errors: Record<string, string> };

    let { draft = $bindable(), options, errors }: Props = $props();

    const SEEN = [
        { value: true, label: 'Seen' },
        { value: false, label: 'Not seen' },
    ];

    // "Not seen" hides the details and clears them, so nothing stale is kept.
    function dbSeen(value: boolean): void {
        if (!value) {
            draft.db_standard = null;
            draft.db_mccb_count = null;
            draft.db_make = null;
        }
    }

    function changeoverSeen(value: boolean): void {
        if (!value) {
            draft.changeover_standard = null;
            draft.changeover_phase = null;
            draft.changeover_rated_a = null;
        }
    }

    function mainBreakerSeen(value: boolean): void {
        if (!value) {
            draft.db_main_breaker_rated_a = null;
            draft.db_main_breaker_poles = null;
            draft.db_main_breaker_standard = null;
        }
    }

    const label = 'text-sm font-semibold text-mut';
    const row = 'flex items-center justify-between gap-3';
</script>

{#snippet card(title: string, ref: string | null, error: string | undefined, body: Snippet)}
    <section class={['flex flex-col gap-2.5 rounded-2xl border bg-sf p-3.5', error ? 'border-2 border-bad' : 'border-line']}>
        <div class="flex flex-col gap-0.5">
            <h2 class="text-base font-bold">{title}</h2>
            {#if ref}<span class="font-mono text-[13px] font-medium text-mut">{ref}</span>{/if}
        </div>
        {@render body()}
        {#if error}<span class="flex items-center gap-1 text-sm font-semibold text-bad"><ErrorIcon class="size-[18px]" />{error}</span>{/if}
    </section>
{/snippet}

{#snippet dbBody()}
    <ChoiceGroup label="Distribution board" variant="segmented" compact options={SEEN} bind:value={draft.db_seen} onchange={dbSeen} />
    {#if draft.db_seen}
        <ChoiceGroup label="Distribution board standard" compact options={STANDARD} bind:value={draft.db_standard} />
        <div class={row}>
            <span id="db_mccb_count-label" class="text-[15px] font-semibold">No. of MCCB breakers</span>
            <Stepper id="db_mccb_count" label="Number of MCCB breakers" bind:value={draft.db_mccb_count} min={0} max={99} />
        </div>
        <div class="flex flex-col gap-1.5">
            <label for="db_make" class={label}>Make <span class="font-normal">optional</span></label>
            <TextInput id="db_make" size="md" class="bg-sf2" maxlength={80} placeholder="e.g. Hager" bind:value={draft.db_make} />
        </div>
    {/if}
{/snippet}
{@render card('Distribution board', null, errors.db, dbBody)}

{#snippet changeoverBody()}
    <ChoiceGroup label="Change-over switch" variant="segmented" compact options={SEEN} bind:value={draft.changeover_seen} onchange={changeoverSeen} />
    {#if draft.changeover_seen}
        <ChoiceGroup label="Change-over switch phase" variant="segmented" compact options={options.connectionType} bind:value={draft.changeover_phase} />
        <div class="flex flex-col gap-1.5">
            <label for="changeover_rated_a" class={label}>Rated current</label>
            <NumberInput id="changeover_rated_a" size="md" unit="A" class="bg-sf2" bind:value={draft.changeover_rated_a} />
        </div>
        <ChoiceGroup label="Change-over switch standard" compact options={STANDARD} bind:value={draft.changeover_standard} />
    {/if}
{/snippet}
{@render card('Change-over switch', null, errors.changeover, changeoverBody)}

{#snippet mainSwitchBody()}
    <div class="flex flex-col gap-1.5">
        <label for="main_switch_rated_a" class={label}>Rated current</label>
        <NumberInput id="main_switch_rated_a" size="md" unit="A" class="bg-sf2" bind:value={draft.main_switch_rated_a} />
    </div>
    <div class={row}>
        <span id="main_switch_poles-label" class="text-[15px] font-semibold">Number of poles <span class="text-sm font-normal text-mut">max 4</span></span>
        <Stepper id="main_switch_poles" label="Main switch poles" bind:value={draft.main_switch_poles} min={1} max={4} />
    </div>
    <ChoiceGroup label="Main switch phase" variant="segmented" compact options={options.connectionType} bind:value={draft.main_switch_phase} />
    <ChoiceGroup label="Main switch standard" compact options={STANDARD} bind:value={draft.main_switch_standard} />
{/snippet}
{@render card('Main switch', 'BS EN 60947-3', errors.main_switch, mainSwitchBody)}

{#snippet mainBreakerBody()}
    <ChoiceGroup label="DB main isolator" variant="segmented" compact options={SEEN} bind:value={draft.db_main_breaker_seen} onchange={mainBreakerSeen} />
    {#if draft.db_main_breaker_seen}
        <div class="flex flex-col gap-1.5">
            <label for="db_main_breaker_rated_a" class={label}>Rated current</label>
            <NumberInput id="db_main_breaker_rated_a" size="md" unit="A" class="bg-sf2" bind:value={draft.db_main_breaker_rated_a} />
        </div>
        <div class={row}>
            <span id="db_main_breaker_poles-label" class="text-[15px] font-semibold">Number of poles <span class="text-sm font-normal text-mut">max 4</span></span>
            <Stepper id="db_main_breaker_poles" label="DB main isolator poles" bind:value={draft.db_main_breaker_poles} min={1} max={4} />
        </div>
        <ChoiceGroup label="DB main isolator standard" compact options={STANDARD} bind:value={draft.db_main_breaker_standard} />
    {/if}
{/snippet}
{@render card('DB main isolator', null, errors.db_main_breaker, mainBreakerBody)}

{#snippet cbTypeBody()}
    <ChoiceGroup label="Circuit breaker type" compact options={STANDARD} bind:value={draft.cb_type_standard} />
{/snippet}
{@render card('Circuit breaker type', 'BS EN 60898-1 · Type B · over-current', errors.cb_type_standard, cbTypeBody)}

{#snippet socketBody()}
    <ChoiceGroup label="Socket outlets type" compact options={STANDARD} bind:value={draft.socket_outlets_standard} />
{/snippet}
{@render card('Socket outlets type', 'BS 1363-2', errors.socket_outlets_standard, socketBody)}

{#snippet secondaryBody()}
    <div class="grid grid-cols-2 gap-2">
        <NumberInput id="secondary_rated_a" size="md" unit="A" class="h-12 bg-sf2" inputClass="text-[17px]" bind:value={draft.secondary_rated_a} />
        <ChoiceGroup label="Secondary protection type" variant="segmented" compact class="[&>button]:min-h-10" options={options.protectionType} bind:value={draft.secondary_type} />
    </div>
    <ChoiceGroup label="Secondary main protection standard" compact options={STANDARD} bind:value={draft.secondary_standard} />
{/snippet}
{@render card('Secondary main protection', null, errors.secondary, secondaryBody)}
