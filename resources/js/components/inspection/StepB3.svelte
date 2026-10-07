<script lang="ts">
    import type { Snippet } from 'svelte';
    import ChoiceGroup, { STANDARD } from '@/components/form/ChoiceGroup.svelte';
    import NumberInput from '@/components/form/NumberInput.svelte';
    import type { Draft, FormOptions } from '@/lib/inspection/types';
    import ErrorIcon from '~icons/ms/error';

    type Props = { draft: Draft; options: FormOptions; errors: Record<string, string> };

    let { draft = $bindable(), options, errors }: Props = $props();

    const SEEN = [
        { value: true, label: 'Seen' },
        { value: false, label: 'Not seen' },
    ];

    // Hiding the Standard question when "Not seen" also clears its answer.
    function seen(key: 'db' | 'changeover', value: boolean): void {
        if (!value) {
            draft[key === 'db' ? 'db_standard' : 'changeover_standard'] = null;
        }
    }
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
    <ChoiceGroup label="Distribution board" variant="segmented" compact options={SEEN} bind:value={draft.db_seen} onchange={(v) => seen('db', v)} />
    {#if draft.db_seen}
        <ChoiceGroup label="Distribution board standard" compact options={STANDARD} bind:value={draft.db_standard} />
    {/if}
{/snippet}
{@render card('Distribution board', null, errors.db, dbBody)}

{#snippet changeoverBody()}
    <ChoiceGroup label="Change-over switch" variant="segmented" compact options={SEEN} bind:value={draft.changeover_seen} onchange={(v) => seen('changeover', v)} />
    {#if draft.changeover_seen}
        <ChoiceGroup label="Change-over switch standard" compact options={STANDARD} bind:value={draft.changeover_standard} />
    {/if}
{/snippet}
{@render card('Change-over switch', null, errors.changeover, changeoverBody)}

{#snippet mainSwitchBody()}
    <ChoiceGroup label="Main switch type" compact options={STANDARD} bind:value={draft.main_switch_standard} />
{/snippet}
{@render card('Main switch type', 'BS', errors.main_switch_standard, mainSwitchBody)}

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
