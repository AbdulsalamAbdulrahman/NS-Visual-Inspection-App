<script lang="ts">
    import type { Snippet } from 'svelte';
    import { CONDITIONS, CONDITION_ORDER } from '@/lib/inspection/conditions';
    import { STEPS } from '@/lib/inspection/steps';
    import { warnings, type MissingItem } from '@/lib/inspection/checklist';
    import type { Draft, FormOptions, Inspector } from '@/lib/inspection/types';
    import { formatCoords, num } from '@/lib/format';
    import CheckCircle from '~icons/ms/check-circle';
    import ErrorIcon from '~icons/ms/error';

    type Props = {
        draft: Draft;
        options: FormOptions;
        areas: { id: number; name: string }[];
        inspector: Inspector;
        missing: MissingItem[];
        onedit: (step: number) => void;
    };

    let { draft, options, areas, inspector, missing, onedit }: Props = $props();

    const label = (list: { value: string; label: string }[], value: string | null): string =>
        list.find((o) => o.value === value)?.label ?? '—';
    const yes = (v: boolean | null, t = 'Standard', f = 'Not standard'): string => (v === null ? '—' : v ? t : f);

    const area = $derived(areas.find((a) => a.id === draft.service_area_id)?.name ?? '—');
    const warn = $derived(warnings(draft));
    const missingSteps = $derived(new Set(missing.map((m) => m.step)));

    // A long list is grouped by section so it stays readable on a phone.
    const GROUP_AFTER = 6;
    const fixRows = $derived(
        missing.length <= GROUP_AFTER
            ? missing.map((m) => ({ key: m.key, label: m.label, step: m.step }))
            : [...missingSteps].sort((a, b) => a - b).map((step) => {
                  const n = missing.filter((m) => m.step === step).length;

                  return { key: `step-${step}`, label: `${STEPS[step - 1].label} — ${n} item${n === 1 ? '' : 's'}`, step };
              }),
    );

    // B3 items marked not standard, for the short "Not standard" line (CR-01).
    const notStandard = $derived(
        [
            draft.cb_standard === false && 'Circuit breaker',
            draft.fuse_standard === false && 'Cut-out fuse',
            draft.db_seen && draft.db_standard === false && 'Distribution board',
            draft.changeover_seen && draft.changeover_standard === false && 'Change-over switch',
            draft.main_switch_standard === false && 'Main switch',
            draft.cb_type_standard === false && 'Circuit breaker type',
            draft.secondary_standard === false && 'Secondary protection',
            draft.socket_outlets_standard === false && 'Socket outlets',
        ].filter(Boolean) as string[],
    );

    const counts = $derived(
        CONDITION_ORDER.map((key) => ({ key, n: draft.circuits.filter((c) => c.condition === key).length })).filter((c) => c.n > 0),
    );

    const layoutCount = $derived(draft.attachments.filter((a) => a.type === 'layout').length);
    const photoCount = $derived(draft.attachments.filter((a) => a.type === 'photo').length);
    const calibrationCount = $derived(draft.attachments.filter((a) => a.type === 'calibration').length);
</script>

{#snippet card(title: string, steps: number[], body: Snippet)}
    {@const flagged = steps.some((s) => missingSteps.has(s))}
    <section class={['flex flex-col gap-2 rounded-[14px] bg-sf px-3.5 py-3 lg:gap-2.5 lg:px-[18px] lg:py-4', flagged ? 'border-2 border-bad' : 'border border-line']}>
        <div class="flex items-center justify-between">
            <h2 class="text-[15px] font-bold lg:text-base">{title}</h2>
            <button type="button" class="min-h-9 text-sm font-bold text-brand hover:underline" onclick={() => onedit(steps[0])}>Edit</button>
        </div>
        {@render body()}
    </section>
{/snippet}

{#snippet row(k: string, v: string, mono = false)}
    <dt class="text-mut">{k}</dt>
    <dd class={mono ? 'font-mono' : ''}>{v}</dd>
{/snippet}

{#snippet check(ok: boolean, text: string)}
    <span class={['flex items-center gap-1.5 text-sm', !ok && 'font-semibold text-bad']}>
        {#if ok}<CheckCircle class="size-[18px] text-ok" />{:else}<ErrorIcon class="size-[18px]" />{/if}
        {text}
    </span>
{/snippet}

{#if missing.length > 0}
    <div class="flex flex-col gap-2 rounded-[14px] bg-bad-bg p-3.5" role="alert">
        <b class="flex items-center gap-2 text-base text-bad">
            <ErrorIcon class="size-6" />{missing.length} item{missing.length === 1 ? '' : 's'} to fix before payment
        </b>
        <ul class="flex flex-col gap-1">
            {#each fixRows as item (item.key)}
                <li class="flex justify-between gap-3 text-sm">
                    <span>{item.label}</span>
                    <button type="button" class="min-h-8 font-bold text-bad hover:underline" onclick={() => onedit(item.step)}>Fix</button>
                </li>
            {/each}
        </ul>
    </div>
{:else}
    <span class="inline-flex items-center gap-1.5 self-start rounded-full bg-ok-bg px-3 py-1.5 text-sm font-bold text-ok">
        <CheckCircle class="size-[18px]" />All required fields complete
    </span>
{/if}

<div class="grid gap-2.5 lg:grid-cols-2 lg:gap-3.5">
    {#snippet customer()}
        <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm lg:grid-cols-[110px_1fr]">
            {@render row('Form 74', draft.form74_no || '—', true)}
            {@render row('Owner', draft.owner_name || '—')}
            {@render row('Address', draft.property_address || '—')}
            {@render row('Area · use', `${area} · ${label(options.purpose, draft.purpose)}`)}
            {@render row('Supply', `${label(options.connectionType, draft.connection_type)} · ${label(options.voltageLevel, draft.voltage_level)}`)}
            {@render row(
                'GPS',
                draft.gps_lat !== null && draft.gps_lng !== null
                    ? `${formatCoords(draft.gps_lat, draft.gps_lng)}${draft.gps_accuracy_m !== null ? ` ±${Math.round(draft.gps_accuracy_m)} m` : ''}`
                    : '—',
                true,
            )}
        </dl>
    {/snippet}
    {@render card('A · Customer', [1], customer)}

    {#snippet earthing()}
        <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm lg:grid-cols-[130px_1fr]">
            <dt class="text-mut">Earth</dt>
            <dd class="font-mono">
                <span class={warn.electrode && 'font-semibold text-imp'}>{num(draft.earth_electrode_ft)} ft</span> ·
                <span class={warn.conductor && 'font-semibold text-imp'}>{num(draft.earth_conductor_mm2)} mm²</span> ·
                <span class={warn.resistance && 'font-semibold text-imp'}>{num(draft.earth_resistance_ohm)} Ω{warn.resistance ? ' · above 2 Ω' : ''}</span>
            </dd>
            {@render row('Earth pit', yes(draft.earth_pit, 'Yes', 'No'))}
            <dt class="text-mut">Breaker / fuse</dt>
            <dd class="font-mono">
                {num(draft.cb_rated_a)} A {draft.cb_standard === null ? '' : draft.cb_standard ? '✓' : '✗'} ·
                {num(draft.fuse_rated_a)} A {draft.fuse_standard === null ? '' : draft.fuse_standard ? '✓' : '✗'} ·
                {draft.poles ?? '—'} poles
            </dd>
            <dt class="text-mut">Not standard</dt>
            <dd class={notStandard.length ? 'font-semibold text-bad' : ''}>{notStandard.length ? notStandard.join(' · ') : 'None'}</dd>
            {@render row(
                'Secondary',
                `${num(draft.secondary_rated_a)} A · ${label(options.protectionType, draft.secondary_type)} · ${yes(draft.secondary_standard, 'Std', 'Not std')}`,
                true,
            )}
        </dl>
    {/snippet}
    {@render card('B1–B3 · Earthing, protection & mains', [2, 3, 4], earthing)}

    {#snippet wiring()}
        {#if counts.length}
            <div class="flex flex-wrap gap-1.5 text-[13px] font-bold">
                {#each counts as c (c.key)}
                    <span class={['rounded-full px-2.5 py-1', CONDITIONS[c.key].bg, CONDITIONS[c.key].fg]}>{c.n} {CONDITIONS[c.key].count}</span>
                {/each}
            </div>
        {:else}
            <span class="text-sm text-mut">No circuits yet.</span>
        {/if}
    {/snippet}
    {@render card(`B4 · Wiring · ${draft.circuits.length} circuit${draft.circuits.length === 1 ? '' : 's'}`, [5], wiring)}

    {#snippet system()}
        <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1 text-sm">
            {@render row('Earthing', label(options.earthingSystemType, draft.earthing_system_type), true)}
            {@render row('Boards', `${draft.db_count ?? '—'} DBs · ${draft.sub_circuit_count ?? '—'} sub-circuits`)}
            {@render row('Main cable', `${num(draft.main_cable_mm2)} mm² ${label(options.conductorType, draft.conductor_type).toLowerCase()} · ${draft.cable_insulation || '—'}`)}
            {@render row('Method', draft.wiring_method === 'other' ? draft.wiring_method_other || 'Other' : label(options.wiringMethod, draft.wiring_method))}
        </dl>
    {/snippet}
    {@render card('C · System details', [6], system)}

    {#snippet files()}
        {@render check(layoutCount > 0, layoutCount > 0 ? 'Layout & load schedule' : 'Layout & load schedule — required')}
        {@render check(photoCount > 0, photoCount > 0 ? `${photoCount} photo${photoCount === 1 ? '' : 's'} of DB & earthing` : 'DB & earthing photos — required')}
        {#if calibrationCount > 0}{@render check(true, 'Calibration certificate')}{/if}
    {/snippet}
    {@render card('Attachments', [7], files)}

    {#snippet attestation()}
        <span class="text-sm">
            {inspector.name}{inspector.category ? ` · ${inspector.category}` : ''}
            {#if inspector.regNo}· <span class="font-mono">{inspector.regNo}</span>{/if}
        </span>
        {@render check(draft.declaration_accepted, draft.declaration_accepted ? 'Declaration accepted' : 'Declaration — required')}
        {#if draft.signature_url}
            <div class="flex items-center gap-2">
                {@render check(true, 'Signed')}
                <img src={draft.signature_url} alt="Signature" class="ml-auto h-10 rounded bg-white px-2" />
            </div>
        {:else}
            {@render check(false, 'Signature — required')}
        {/if}
    {/snippet}
    {@render card('D · Attestation', [8], attestation)}
</div>
