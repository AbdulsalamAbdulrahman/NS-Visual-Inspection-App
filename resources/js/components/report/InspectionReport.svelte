<script lang="ts">
    import type { Snippet } from 'svelte';
    import ConditionPill from '@/components/inspection/ConditionPill.svelte';
    import { formatCoords, num } from '@/lib/format';
    import type { Report } from '@/lib/inspection/report';
    import type { Attachment } from '@/lib/inspection/types';
    import Lightbox from './Lightbox.svelte';
    import ExpandMore from '~icons/ms/expand-more';
    import OpenInNew from '~icons/ms/open-in-new';
    import PictureAsPdf from '~icons/ms/picture-as-pdf';

    type Props = {
        report: Report;
    };

    let { report }: Props = $props();

    const photos = $derived(report.attachments.filter((a) => a.type === 'photo' && a.isImage));
    const files = $derived(report.attachments.filter((a) => !(a.type === 'photo' && a.isImage)));

    let lightboxOpen = $state(false);
    let lightboxIndex = $state(0);
    let stripIndex = $state(0);

    function openPhoto(i: number): void {
        lightboxIndex = i;
        lightboxOpen = true;
    }

    const yes = (v: boolean | null): string => (v === null ? '—' : v ? 'Yes' : 'No');
    const mark = (v: boolean | null): string => (v === null ? '' : v ? ' ✓' : ' ✗');
    const fileLabel = (a: Attachment): string =>
        a.type === 'layout' ? 'Layout & load schedule' : a.type === 'calibration' ? 'Calibration certificate' : a.name;

    // Mobile sections: A open by default (AM-04).
    let open = $state<Record<string, boolean>>({ a: true });

    // Which photo the swipe strip is showing (for the dots).
    function onStripScroll(event: Event & { currentTarget: HTMLElement }): void {
        const el = event.currentTarget;
        stripIndex = Math.min(photos.length - 1, Math.round((el.scrollLeft / Math.max(1, el.scrollWidth - el.clientWidth)) * (photos.length - 1)));
    }
</script>

{#snippet kv(rows: [string, string | null | undefined, boolean?][], cols = 'grid-cols-[auto_1fr] lg:grid-cols-[110px_1fr]')}
    <dl class={['grid gap-x-3 gap-y-[5px] text-sm', cols]}>
        {#each rows as [k, v, mono] (k)}
            <dt class="text-mut">{k}</dt>
            <dd class={mono ? 'font-mono' : ''}>{v || '—'}</dd>
        {/each}
    </dl>
{/snippet}

{#snippet customer()}
    {@render kv([
        ['Form 74', report.a.form74No, true],
        ['Owner', report.a.ownerName],
        ['Address', report.a.address],
        ['Area', report.a.area],
        ['Use · supply', [report.a.purpose, report.a.connection, report.a.voltage].filter(Boolean).join(' · ')],
        ['GPS', report.a.gps ? `${formatCoords(report.a.gps.lat, report.a.gps.lng)}${report.a.gps.accuracy !== null ? ` ±${Math.round(report.a.gps.accuracy)} m` : ''}` : null, true],
        ['Contractor', report.a.contractor],
        ['Inspected', report.a.inspectionDate, true],
    ])}
{/snippet}

{#snippet earthing()}
    <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-[5px] text-sm lg:grid-cols-[130px_1fr]">
        <dt class="text-mut">Electrode / cond.</dt>
        <dd class="font-mono">
            <span class={[report.b1.warnings.electrode && 'font-semibold text-imp']}>{num(report.b1.electrodeFt)} ft</span> ·
            <span class={[report.b1.warnings.conductor && 'font-semibold text-imp']}>{num(report.b1.conductorMm2)} mm²</span>
        </dd>
        <dt class="text-mut">Resistance</dt>
        <dd class={['font-mono', report.b1.warnings.resistance && 'font-semibold text-imp']}>
            {num(report.b1.resistanceOhm)} Ω{report.b1.warnings.resistance ? ' ⚠ > 2 Ω' : ''}
        </dd>
        <dt class="text-mut">Pit</dt>
        <dd>{yes(report.b1.pit)}</dd>
        <dt class="text-mut">Breaker / fuse</dt>
        <dd class="font-mono">
            {num(report.b2.cbRatedA)} A{mark(report.b2.cbStandard)} · {num(report.b2.fuseRatedA)} A{mark(report.b2.fuseStandard)} · {report.b2.poles ?? '—'}P
        </dd>
    </dl>
{/snippet}

{#snippet mains()}
    <div class="grid overflow-hidden rounded-xl border border-line text-[13px] sm:grid-cols-2 xl:grid-cols-3">
        {#each report.b3 as row (row.label)}
            <span class="flex justify-between gap-2.5 border-b border-line px-3 py-[7px]">
                <span class="text-mut">{row.label}{#if row.ref}<span class="hidden xl:inline"> ({row.ref})</span>{/if}</span>
                <span class={['text-right font-semibold', row.tone === 'ok' && 'text-ok', row.tone === 'bad' && 'text-bad']}>{row.value}</span>
            </span>
        {/each}
    </div>
{/snippet}

{#snippet wiring()}
    <!-- Table on desktop and print; stacked rows on phones. Colour always comes with icon + label. -->
    <div class="overflow-hidden rounded-xl border border-line">
        {#each report.b4 as c (c.n)}
            <div class="flex flex-col gap-1.5 border-b border-line px-3.5 py-2.5 text-[13px] last:border-b-0 lg:grid lg:min-h-[42px] lg:grid-cols-[36px_minmax(0,1.3fr)_64px_76px_196px_minmax(0,1fr)] lg:items-center lg:gap-2.5 lg:py-1.5">
                <span class="flex items-baseline gap-2 lg:contents">
                    <span class="font-mono text-mut">{c.n}</span>
                    <b>{c.description ?? '—'}</b>
                    <span class="ml-auto font-mono lg:ml-0">{num(c.rating)} A</span>
                    <span class="font-mono">{num(c.conductor)} mm²</span>
                </span>
                {#if c.condition}<ConditionPill condition={c.condition} length="short" class="py-[3px] pl-1.5 text-xs" />{:else}<span>—</span>{/if}
                <span class="text-mut">{c.observation ?? ''}</span>
            </div>
        {:else}
            <p class="px-3.5 py-3 text-sm text-mut">No circuits recorded.</p>
        {/each}
    </div>
{/snippet}

{#snippet system()}
    {@render kv([
        ['Earthing', report.c.earthingSystem, true],
        ['Boards', `${report.c.dbCount ?? '—'} DBs · ${report.c.subCircuitCount ?? '—'} sub-circuits`],
        ['Main cable', `${num(report.c.mainCableMm2)} mm² ${(report.c.conductorType ?? '').toLowerCase()}`],
        ['Method', report.c.wiringMethod],
        ['Insulation', report.c.cableInsulation],
    ])}
{/snippet}

{#snippet attestation()}
    <span class="text-sm">
        {report.d.name}{report.d.category ? ` · ${report.d.category}` : ''}{#if report.d.regNo} · <span class="font-mono">{report.d.regNo}</span>{/if}
    </span>
    {#if report.d.corenNo || report.d.firmName}
        <span class="text-sm text-mut">{[report.d.corenNo ? `COREN ${report.d.corenNo}` : null, report.d.firmName].filter(Boolean).join(' · ')}</span>
    {/if}
    {#if report.d.signatureUrl}
        <img src={report.d.signatureUrl} alt="Inspector's signature" class="h-14 self-start rounded-md bg-white px-2" />
    {/if}
    {#if report.d.declaredAt}<span class="text-xs text-mut">Declaration accepted {report.d.declaredAt}</span>{/if}
{/snippet}

{#snippet fileList()}
    {#each files as f (f.uuid)}
        <a href={f.url} target="_blank" rel="noopener" class="flex items-center gap-2 text-sm text-ink no-underline hover:underline">
            <PictureAsPdf class="size-[19px] flex-none text-bad" />{fileLabel(f)}
        </a>
    {/each}
{/snippet}

{#snippet heading(text: string)}
    <h2 class="text-[13px] font-bold tracking-[0.06em] text-mut uppercase">{text}</h2>
{/snippet}

<!-- ===== Desktop (AD-03) ===== -->
<div class="hidden gap-5 lg:grid lg:grid-cols-[minmax(0,1fr)_360px]">
    <div class="flex flex-col gap-[18px] rounded-2xl border border-line bg-sf px-6 py-[22px]">
        <div class="grid grid-cols-2 gap-5">
            <section class="flex flex-col gap-2">{@render heading('A · Customer')}{@render customer()}</section>
            <section class="flex flex-col gap-2">{@render heading('B1–B2 · Earthing & protection')}{@render earthing()}</section>
        </div>
        <section class="flex flex-col gap-2">{@render heading('B3 · Mains checklist')}{@render mains()}</section>
        <section class="flex flex-col gap-2">{@render heading('B4 · Wiring')}{@render wiring()}</section>
        <div class="grid grid-cols-2 gap-5">
            <section class="flex flex-col gap-2">{@render heading('C · System')}{@render system()}</section>
            <section class="flex flex-col gap-1.5">{@render heading('D · Attestation')}{@render attestation()}</section>
        </div>
    </div>

    <div class="flex flex-col gap-3.5">
        {#if report.payment}
            <section class="flex flex-col gap-2 rounded-2xl border border-line bg-sf px-[18px] py-4">
                {@render heading('Payment · Monnify')}
                <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-[5px] text-sm">
                    <dt class="text-mut">Reference</dt><dd class="text-right font-mono text-[13px] break-all">{report.payment.reference}</dd>
                    <dt class="text-mut">Amount</dt><dd class="text-right font-mono font-semibold">{report.payment.amount}</dd>
                    <dt class="text-mut">Channel</dt><dd class="text-right">{report.payment.channel ?? '—'}</dd>
                    <dt class="text-mut">Time</dt><dd class="text-right font-mono">{report.payment.paidAt ?? '—'}</dd>
                </dl>
            </section>
        {/if}

        {#if report.a.gps}
            <section class="overflow-hidden rounded-2xl border border-line bg-sf" aria-label="GPS location">
                <div class="relative h-24 bg-[repeating-linear-gradient(135deg,var(--sf2)_0_10px,var(--bg)_10px_20px)]" aria-hidden="true">
                    <span class="absolute top-1/2 left-1/2 -mt-2 -ml-2 size-4 rounded-full bg-mid shadow-[0_0_0_4px_var(--sf)]"></span>
                </div>
                <div class="flex items-center gap-2.5 px-[18px] py-3">
                    <div class="flex flex-1 flex-col gap-0.5">
                        <span class="font-mono text-sm font-medium">{formatCoords(report.a.gps.lat, report.a.gps.lng)}</span>
                        <span class="text-xs text-mut">
                            {report.a.gps.accuracy !== null ? `±${Math.round(report.a.gps.accuracy)} m` : ''}{report.a.gps.capturedAt ? ` · captured ${report.a.gps.capturedAt}` : ''}
                        </span>
                    </div>
                    <a href={report.a.gps.mapsUrl} target="_blank" rel="noopener" class="flex items-center gap-1 text-sm font-bold no-underline hover:underline">
                        Google Maps<OpenInNew class="size-[17px]" />
                    </a>
                </div>
            </section>
        {/if}

        <section class="flex flex-col gap-2.5 rounded-2xl border border-line bg-sf px-[18px] py-4">
            {@render heading(`Attachments · ${report.attachments.length}`)}
            {#if photos.length}
                <div class="grid grid-cols-3 gap-2">
                    {#each photos as p, i (p.uuid)}
                        <button type="button" class="aspect-square overflow-hidden rounded-[10px] bg-sf2" aria-label="Open photo {i + 1}" onclick={() => openPhoto(i)}>
                            <img src={p.url} alt="" loading="lazy" class="size-full object-cover" />
                        </button>
                    {/each}
                </div>
            {/if}
            {@render fileList()}
        </section>
    </div>
</div>

<!-- ===== Phone (AM-04 / CD-01 / SR-M3) ===== -->
<div class="flex flex-col gap-2.5 lg:hidden">
    {#if photos.length}
        <div class="-mx-4 flex snap-x snap-mandatory gap-2 overflow-x-auto px-4 [scrollbar-width:none]" onscroll={onStripScroll}>
            {#each photos as p, i (p.uuid)}
                <button type="button" class="h-[170px] w-[300px] max-w-[85vw] flex-none snap-start overflow-hidden rounded-[14px] bg-sf2" aria-label="Open photo {i + 1}" onclick={() => openPhoto(i)}>
                    <img src={p.url} alt="" loading="lazy" class="size-full object-cover" />
                </button>
            {/each}
        </div>
        {#if photos.length > 1}
            <div class="flex justify-center gap-1.5" aria-hidden="true">
                {#each photos as p, i (p.uuid)}
                    <span class={['h-1.5 rounded-full', i === stripIndex ? 'w-[18px] bg-mid' : 'w-1.5 bg-line']}></span>
                {/each}
            </div>
        {/if}
    {/if}

    {#snippet collapsible(key: string, title: string, badge: Snippet | null, body: Snippet)}
        <section class="flex flex-col rounded-[14px] border border-line bg-sf">
            <button
                type="button"
                class="flex min-h-[52px] items-center gap-2 pr-3 pl-4 text-left"
                aria-expanded={!!open[key]}
                onclick={() => (open[key] = !open[key])}
            >
                <b class="flex-1 text-[15px]">{title}</b>
                {@render badge?.()}
                <ExpandMore class={['size-6 transition-transform', open[key] && 'rotate-180']} aria-hidden="true" />
            </button>
            {#if open[key]}
                <div class="flex flex-col gap-3 px-4 pb-3.5">{@render body()}</div>
            {/if}
        </section>
    {/snippet}

    {@render collapsible('a', 'A · Customer information', null, customer)}

    {#if report.payment}
        {#snippet paidBadge()}<span class="font-mono text-[13px] font-medium text-ok">{report.payment?.amount} ✓</span>{/snippet}
        {#snippet paymentBody()}
            {@render kv([
                ['Reference', report.payment?.reference, true],
                ['Channel', report.payment?.channel],
                ['Time', report.payment?.paidAt, true],
            ])}
        {/snippet}
        {@render collapsible('pay', 'Payment', paidBadge, paymentBody)}
    {/if}

    {#snippet bBadge()}
        {#if report.summary.issues > 0}
            <span class="rounded-full bg-bad-bg px-2 py-[3px] text-xs font-bold text-bad">{report.summary.issues} issue{report.summary.issues === 1 ? '' : 's'}</span>
        {:else if report.summary.warnings > 0}
            <span class="rounded-full bg-imp-bg px-2 py-[3px] text-xs font-bold text-imp">{report.summary.warnings} warning{report.summary.warnings === 1 ? '' : 's'}</span>
        {/if}
    {/snippet}
    {#snippet bBody()}
        {@render earthing()}
        {@render mains()}
        <b class="text-sm">B4 · {report.b4.length} circuit{report.b4.length === 1 ? '' : 's'}</b>
        {@render wiring()}
    {/snippet}
    {@render collapsible('b', 'B · Building inspection', bBadge, bBody)}

    {@render collapsible('c', 'C · System details', null, system)}
    {@render collapsible('d', 'D · Attestation', null, attestation)}

    {#if files.length}
        {@render collapsible('files', `Attachments · ${report.attachments.length}`, null, fileList)}
    {/if}
</div>

<Lightbox bind:open={lightboxOpen} bind:index={lightboxIndex} {photos} />
