<script lang="ts">
    import PrintShell from '@/components/print/PrintShell.svelte';
    import { APP_TITLE } from '@/lib/brand';
    import { fileSize, formatCoords, num } from '@/lib/format';
    import { CONDITIONS } from '@/lib/inspection/conditions';
    import type { Report } from '@/lib/inspection/report';
    import WorkspacePremium from '~icons/ms/workspace-premium';

    type Props = {
        report: Report;
        qr: string;
        generatedAt: string;
        backUrl: string;
        certificateUrl: string | null;
        autoPrint: boolean;
    };

    let { report, qr, generatedAt, backUrl, certificateUrl, autoPrint }: Props = $props();

    // Long wiring tables move B4 to page 2 (the design's second variant) so page 1 never overflows.
    const longWiring = $derived(report.b4.length > 10);

    const yes = (v: boolean | null): string => (v === null ? '—' : v ? 'Yes' : 'No');
    const amps = (v: number | null): string => (v === null ? '—' : `${num(v)} A`);

    const sectionA = $derived<[string, string | null, boolean?][]>([
        ['Form 74 number', report.a.form74No, true],
        ['Inspection date', report.a.inspectionDate, true],
        ['Property owner', report.a.ownerName],
        ['Service area', report.a.area],
        ['Property address', report.a.address],
        ['Purpose', report.a.purpose],
        ['Connection type', report.a.connection],
        ['Voltage level', report.a.voltage, true],
        ['GPS', report.a.gps ? `${formatCoords(report.a.gps.lat, report.a.gps.lng)}${report.a.gps.accuracy !== null ? ` ±${Math.round(report.a.gps.accuracy)} m` : ''}` : null, true],
        ['Contractor', report.a.contractor],
    ]);

    const sectionC = $derived<[string, string | null][]>([
        ['Earthing system type', report.c.earthingSystem],
        ['Conductor type', report.c.conductorType],
        ['Distribution boards', report.c.dbCount === null ? null : String(report.c.dbCount)],
        ['Wiring method', report.c.wiringMethod],
        ['Sub-circuits', report.c.subCircuitCount === null ? null : String(report.c.subCircuitCount)],
        ['Cable insulation', report.c.cableInsulation],
        ['Main cable size', report.c.mainCableMm2 === null ? null : `${num(report.c.mainCableMm2)} mm²`],
    ]);

    const toneClass = { ok: 'text-ink', bad: 'font-bold text-bad', muted: 'text-mut' } as const;
</script>

<svelte:head>
    <title>Report {report.ticketNo}</title>
</svelte:head>

{#snippet header()}
    <div class="flex items-center gap-3.5 border-b-2 border-brand pb-3">
        <img src="/images/ke-logo.png" alt="Kaduna Electric" class="size-[58px] flex-none object-contain" />
        <div class="flex flex-1 flex-col gap-0.5">
            <span class="font-mono text-[10px] font-semibold tracking-[0.1em] text-brand">KADUNA ELECTRIC · NEW SERVICE DEPARTMENT</span>
            <span class="text-[20px] leading-tight font-extrabold tracking-[-0.01em]">{APP_TITLE}</span>
            <span class="text-[11px] text-mut">Inspection report · inspected under NESIS and the Nigerian Electricity Health &amp; Safety Code</span>
        </div>
        <div class="flex items-center gap-2.5">
            <div class="flex flex-col items-end gap-0.5">
                <span class="font-mono text-[9px] font-semibold tracking-[0.1em] text-mut">TICKET</span>
                <span class="font-mono text-sm font-semibold text-brand">{report.ticketNo}</span>
                <span class="font-mono text-[10px] font-medium text-mut">Submitted {report.submittedAt}</span>
            </div>
            <img src={qr} alt="QR code to verify {report.ticketNo}" class="size-16" />
        </div>
    </div>
{/snippet}

{#snippet footer(page: number)}
    <div class="mt-auto flex items-center justify-between border-t border-[#C9D2C7] pt-2.5 font-mono text-[10px] font-medium text-mut">
        <span>Generated {generatedAt} · {report.ticketNo}</span>
        <span class="flex items-center gap-2"><span class="h-1 w-7 -skew-x-[26deg] bg-acc"></span>Page {page} of 2</span>
    </div>
{/snippet}

{#snippet title(text: string)}
    <div class="text-[11px] font-bold tracking-[0.08em] text-brand">{text}</div>
{/snippet}

{#snippet kvGrid(rows: [string, string | null, boolean?][], keyWidth: string)}
    <div class="grid grid-cols-2 overflow-hidden rounded-md border border-[#C9D2C7]">
        {#each rows as [k, v, mono] (k)}
            <div class="grid border-r border-b border-[#E1E6DF] text-[11.5px]" style:grid-template-columns="{keyWidth} 1fr">
                <span class="bg-bg px-2 py-1.5 text-mut">{k}</span>
                <span class={['px-2 py-1.5', mono && 'font-mono']}>{v || '—'}</span>
            </div>
        {/each}
    </div>
{/snippet}

{#snippet wiring()}
    <div class="flex flex-col gap-1.5">
        {@render title('B4 · DESCRIPTION OF WIRING')}
        <div class="overflow-hidden rounded-md border border-[#C9D2C7] text-[11.5px]">
            <div class="grid grid-cols-[34px_1.3fr_58px_74px_150px_1fr] bg-bg text-[10px] font-bold tracking-[0.05em] text-mut">
                <span class="px-2 py-1.5">#</span><span class="px-2 py-1.5">CIRCUIT</span><span class="px-2 py-1.5">RATING</span><span class="px-2 py-1.5">CONDUCTOR</span><span class="px-2 py-1.5">CONDITION</span><span class="px-2 py-1.5">OBSERVATION</span>
            </div>
            {#each report.b4 as c (c.n)}
                {@const cond = c.condition ? CONDITIONS[c.condition] : null}
                <div class="grid grid-cols-[34px_1.3fr_58px_74px_150px_1fr] items-center border-t border-[#E1E6DF]">
                    <span class="px-2 py-1.5 font-mono text-mut">{c.n}</span>
                    <span class="px-2 py-1.5 font-semibold">{c.description ?? '—'}</span>
                    <span class="px-2 py-1.5 font-mono">{amps(c.rating)}</span>
                    <span class="px-2 py-1.5 font-mono">{c.conductor === null ? '—' : `${num(c.conductor)} mm²`}</span>
                    <span class="px-2 py-1">
                        {#if cond}
                            {@const Icon = cond.icon}
                            <span class={['inline-flex items-center gap-1 rounded-full px-1.5 py-0.5 text-[10.5px] font-bold', cond.fg, cond.bg]}><Icon class="size-3" aria-hidden="true" />{cond.label}</span>
                        {:else}—{/if}
                    </span>
                    <span class="px-2 py-1.5 text-mut">{c.observation || '—'}</span>
                </div>
            {/each}
        </div>
    </div>
{/snippet}

{#snippet systemDetails()}
    <div class="flex flex-col gap-1.5">
        {@render title('C · SYSTEM DETAILS')}
        {@render kvGrid(sectionC, '150px')}
    </div>
{/snippet}

<PrintShell
    title="Inspection report"
    subtitle="{report.ticketNo} · A4 · 2 pages"
    {backUrl}
    pageCount={2}
    {autoPrint}
    alternate={certificateUrl ? { label: 'Certificate', href: certificateUrl, icon: WorkspacePremium } : null}
>
    <!-- PR-01 -->
    <section class="print-sheet paper flex flex-col gap-3.5 px-12 pt-10 pb-7" aria-label="Report page 1">
        {@render header()}
        <div class="flex flex-col gap-1.5">
            {@render title('A · BASIC CUSTOMER INFORMATION')}
            {@render kvGrid(sectionA, '130px')}
        </div>
        <div class="grid grid-cols-2 gap-3.5">
            <div class="flex flex-col gap-1.5">
                {@render title('B1 · EARTHING SYSTEM')}
                <div class="overflow-hidden rounded-md border border-[#C9D2C7] text-[11.5px]">
                    {#each [['Earth electrode size (min 6 ft)', report.b1.electrodeFt === null ? '—' : `${num(report.b1.electrodeFt)} ft`, report.b1.warnings.electrode], ['Earth conductor size (min 10 mm²)', report.b1.conductorMm2 === null ? '—' : `${num(report.b1.conductorMm2)} mm²`, report.b1.warnings.conductor], ['Earth resistance (≤ 2 Ω advised)', report.b1.resistanceOhm === null ? '—' : `${num(report.b1.resistanceOhm)} Ω`, report.b1.warnings.resistance], ['Earth inspection pit available', yes(report.b1.pit), false]] as [k, v, warn] (k)}
                        <div class="grid grid-cols-[1fr_110px] border-b border-[#E1E6DF] last:border-b-0">
                            <span class="bg-bg px-2 py-1.5 text-mut">{k}</span>
                            <span class={['px-2 py-1.5 font-mono', warn ? 'font-bold text-imp' : '']}>{v}{warn ? ' ⚠' : ''}</span>
                        </div>
                    {/each}
                </div>
            </div>
            <div class="flex flex-col gap-1.5">
                {@render title('B2 · PRIMARY SUPPLY OVER-CURRENT PROTECTION')}
                <div class="overflow-hidden rounded-md border border-[#C9D2C7] text-[11.5px]">
                    <div class="grid grid-cols-[1fr_70px_74px] bg-bg text-[10px] font-bold tracking-[0.05em] text-mut">
                        <span class="px-2 py-1.5">ITEM</span><span class="px-2 py-1.5">RATED</span><span class="px-2 py-1.5">STANDARD</span>
                    </div>
                    <div class="grid grid-cols-[1fr_70px_74px] border-t border-[#E1E6DF]">
                        <span class="px-2 py-1.5">Circuit breaker</span><span class="px-2 py-1.5 font-mono">{amps(report.b2.cbRatedA)}</span>
                        <span class={['px-2 py-1.5', report.b2.cbStandard === false && 'font-bold text-bad']}>{yes(report.b2.cbStandard)}</span>
                    </div>
                    <div class="grid grid-cols-[1fr_70px_74px] border-t border-[#E1E6DF]">
                        <span class="px-2 py-1.5">Cut-out fuse</span><span class="px-2 py-1.5 font-mono">{amps(report.b2.fuseRatedA)}</span>
                        <span class={['px-2 py-1.5', report.b2.fuseStandard === false && 'font-bold text-bad']}>{yes(report.b2.fuseStandard)}</span>
                    </div>
                    <div class="grid grid-cols-[1fr_144px] border-t border-[#E1E6DF]">
                        <span class="px-2 py-1.5">Number of poles</span><span class="px-2 py-1.5 font-mono">{report.b2.poles ?? '—'}</span>
                    </div>
                </div>
            </div>
        </div>
        <div class="flex flex-col gap-1.5">
            {@render title('B3 · MAINS CHECKLIST')}
            <div class="grid grid-cols-2 overflow-hidden rounded-md border border-[#C9D2C7]">
                {#each report.b3 as row (row.label)}
                    <div class="grid grid-cols-[1fr_130px] border-r border-b border-[#E1E6DF] text-[11.5px]">
                        <span class="bg-bg px-2 py-1.5 text-mut">{row.label}{row.ref ? ` (${row.ref})` : ''}</span>
                        <span class={['px-2 py-1.5', toneClass[row.tone]]}>{row.value}</span>
                    </div>
                {/each}
            </div>
        </div>
        {#if longWiring}
            {@render systemDetails()}
        {:else}
            {@render wiring()}
        {/if}
        {@render footer(1)}
    </section>

    <!-- PR-02 -->
    <section class="print-sheet paper flex flex-col gap-4 px-12 pt-10 pb-7" aria-label="Report page 2">
        {@render header()}
        {#if longWiring}
            {@render wiring()}
        {:else}
            {@render systemDetails()}
        {/if}
        <div class="grid grid-cols-2 gap-3.5">
            <div class="flex flex-col gap-1.5">
                {@render title('GPS LOCATION')}
                <div class="flex flex-col gap-0.5 rounded-md border border-[#C9D2C7] p-2.5 text-[11.5px]">
                    {#if report.a.gps}
                        <span class="font-mono text-[13px] font-medium">{formatCoords(report.a.gps.lat, report.a.gps.lng)}</span>
                        <span class="text-mut">
                            {report.a.gps.accuracy !== null ? `Accuracy ±${Math.round(report.a.gps.accuracy)} m · ` : ''}captured {report.a.inspectionDate}{report.a.gps.capturedAt ? `, ${report.a.gps.capturedAt}` : ''}
                        </span>
                    {:else}
                        <span class="text-mut">Not captured</span>
                    {/if}
                </div>
            </div>
            <div class="flex flex-col gap-1.5">
                {@render title('ATTACHMENTS')}
                <div class="flex flex-col gap-0.5 rounded-md border border-[#C9D2C7] px-2.5 py-2 text-[11.5px]">
                    {#each report.attachments as file (file.uuid)}
                        <span class="flex justify-between gap-2">
                            <span class="truncate">{file.name}{file.exifLat !== null ? ' (GPS)' : ''}</span>
                            <span class="flex-none font-mono text-mut">{fileSize(file.size)}</span>
                        </span>
                    {:else}
                        <span class="text-mut">None</span>
                    {/each}
                </div>
            </div>
        </div>
        <div class="flex flex-col gap-1.5">
            {@render title('D · ATTESTATION')}
            <div class="flex flex-col gap-3 rounded-md border border-[#C9D2C7] px-3.5 py-3">
                <div class="grid grid-cols-3 gap-x-4 gap-y-2 text-[11.5px]">
                    <div class="flex flex-col gap-0.5"><span class="text-[10px] text-mut">INSPECTOR</span><b>{report.d.name ?? '—'}</b></div>
                    <div class="flex flex-col gap-0.5"><span class="text-[10px] text-mut">NEMSA CATEGORY / REG. NO.</span><span class="font-mono">{[report.d.category, report.d.regNo].filter(Boolean).join(' · ') || '—'}</span></div>
                    <div class="flex flex-col gap-0.5"><span class="text-[10px] text-mut">COREN NO.</span><span class="font-mono">{report.d.corenNo ?? '—'}</span></div>
                    <div class="col-span-2 flex flex-col gap-0.5"><span class="text-[10px] text-mut">FIRM</span><span>{report.d.firmName ?? '—'}</span></div>
                </div>
                <div class="rounded bg-bg px-3 py-2.5 text-[11.5px] leading-[1.55]">
                    ☑ I hereby certify that the electrical installation at the location above was inspected and tested in accordance with the Nigerian Electricity Supply Installation Standards (NESIS), the Nigerian Electricity Health and Safety Code, and all applicable regulations. The findings are accurate and complete to the best of my knowledge.
                </div>
                <div class="grid grid-cols-[1fr_200px] items-end gap-6">
                    <div class="flex flex-col gap-0.5">
                        {#if report.d.signatureUrl}
                            <img src={report.d.signatureUrl} alt="Inspector's signature" class="h-14 w-auto max-w-[260px] object-contain object-left" />
                        {:else}
                            <span class="h-14"></span>
                        {/if}
                        <span class="border-t border-ink pt-1 text-[10px] text-mut">SIGNATURE</span>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <span class="font-mono text-[13px] font-medium">{report.d.declaredAt ?? '—'}</span>
                        <span class="border-t border-ink pt-1 text-[10px] text-mut">DATE</span>
                    </div>
                </div>
            </div>
        </div>
        {#if report.review.status === 'approved'}
            <div class="flex items-center gap-2.5 rounded-md border border-ok bg-ok-bg px-3 py-2.5 text-[11px] text-ok">
                <b>Approved by the New Service Department on {report.review.approvedAt}.</b> Certificate no. {report.ticketNo}.
            </div>
        {/if}
        <div class="flex items-center gap-2.5 rounded-md border border-dashed border-[#C9D2C7] px-3 py-2.5 text-[11px] text-mut">
            <b class="text-ink">Verify:</b>Scan the QR code to confirm this report is authentic and see its review status.
        </div>
        {@render footer(2)}
    </section>
</PrintShell>
