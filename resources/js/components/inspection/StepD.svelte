<script lang="ts">
    import { DECLARATION } from '@/lib/inspection/declaration';
    import type { Draft, Inspector } from '@/lib/inspection/types';
    import SignatureField from './SignatureField.svelte';
    import Check from '~icons/ms/check';
    import ErrorIcon from '~icons/ms/error';
    import Lock from '~icons/ms/lock';

    type Props = {
        draft: Draft;
        inspector: Inspector;
        errors: Record<string, string>;
        onsignature: (dataUrl: string | null) => void;
    };

    let { draft = $bindable(), inspector, errors, onsignature }: Props = $props();

    const rows = $derived(
        [
            { label: 'Inspector', value: inspector.name, mono: false },
            { label: 'NEMSA reg. no.', value: inspector.regNo, mono: true },
            { label: 'NEMSA category', value: inspector.category, mono: false },
            { label: 'COREN no.', value: inspector.corenNo, mono: true },
            { label: 'Firm', value: inspector.firmName, mono: false },
        ].filter((r) => r.value),
    );
</script>

<!-- Read-only, from the contractor's licence; copied onto the record when it's submitted. -->
<dl class="grid grid-cols-[auto_1fr] gap-x-3.5 gap-y-1.5 rounded-2xl bg-sf2 px-4 py-3.5 text-sm">
    <div class="col-span-2 mb-0.5 flex items-center gap-1.5 font-mono text-xs font-semibold tracking-[0.05em] text-mut">
        <Lock class="size-4" />FROM YOUR PROFILE
    </div>
    {#each rows as row (row.label)}
        <dt class="text-mut">{row.label}</dt>
        <dd class={row.mono ? 'font-mono font-medium' : 'font-bold'}>{row.value}</dd>
    {/each}
</dl>

<label
    class={[
        'flex cursor-pointer items-start gap-3 rounded-2xl bg-sf p-3.5',
        draft.declaration_accepted ? 'border-2 border-pri' : errors.declaration ? 'border-2 border-bad' : 'border-[1.5px] border-line',
    ]}
>
    <input type="checkbox" class="peer sr-only" bind:checked={draft.declaration_accepted} />
    <span
        class={[
            'mt-0.5 flex size-7 flex-none items-center justify-center rounded-[7px] peer-focus-visible:outline-3 peer-focus-visible:outline-offset-2 peer-focus-visible:outline-mid',
            draft.declaration_accepted ? 'bg-pri text-on-pri' : 'border-2 border-line bg-sf',
        ]}
        aria-hidden="true"
    >
        {#if draft.declaration_accepted}<Check class="size-5" />{/if}
    </span>
    <span class="text-sm leading-normal">{DECLARATION}</span>
</label>
{#if errors.declaration}
    <span class="-mt-2 flex items-center gap-1 text-sm font-semibold text-bad"><ErrorIcon class="size-[18px]" />Tick the declaration to continue.</span>
{/if}

<SignatureField savedUrl={draft.signature_url} onchange={onsignature} error={errors.signature} />

<div class="flex items-center justify-between rounded-xl bg-sf2 px-4 py-3">
    <span class="flex items-center gap-1.5 text-[15px] font-semibold"><Lock class="size-5 text-mut" />Date</span>
    <span class="font-mono text-base font-medium">{draft.inspection_date}</span>
</div>
