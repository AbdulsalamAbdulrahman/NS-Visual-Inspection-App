<script lang="ts">
    import type { Component } from 'svelte';
    import KeSeal from '@/components/print/KeSeal.svelte';
    import PrintShell from '@/components/print/PrintShell.svelte';
    import { APP_TITLE } from '@/lib/brand';
    import Badge from '~icons/ms/badge';
    import Description from '~icons/ms/description';
    import Engineering from '~icons/ms/engineering';
    import Event from '~icons/ms/event';
    import EventAvailable from '~icons/ms/event-available';
    import HomeWork from '~icons/ms/home-work';
    import LocationOn from '~icons/ms/location-on';
    import MapIcon from '~icons/ms/map';
    import Person from '~icons/ms/person';
    import Verified from '~icons/ms/verified';

    type Certificate = {
        uuid: string;
        certificateNo: string;
        form74No: string | null;
        description: string;
        ownerName: string | null;
        address: string | null;
        area: string | null;
        inspectionDate: string | null;
        submittedAt: string | null;
        approvedAt: string | null;
        approvedAtShort: string | null;
        contractor: { name: string | null; firm: string | null; category: string | null; regNo: string | null; corenNo: string | null; signatureUrl: string | null };
        signatory: { name: string | null; title: string | null; signatureUrl: string | null };
        verifyUrl: string;
        verifyLabel: string;
        qr: string;
    };

    type Props = {
        certificate: Certificate;
        backUrl: string;
        reportUrl: string;
        autoPrint: boolean;
    };

    let { certificate: c, backUrl, reportUrl, autoPrint }: Props = $props();

    const rows = $derived<{ icon: Component; label: string; value: string | null; mono?: boolean }[]>([
        { icon: Description, label: 'Form 74 no.', value: c.form74No, mono: true },
        { icon: HomeWork, label: 'Description', value: c.description },
        { icon: Person, label: 'Owner', value: c.ownerName },
        { icon: LocationOn, label: 'Location', value: c.address },
        { icon: MapIcon, label: 'Service area', value: c.area },
        { icon: Engineering, label: 'Contractor', value: [c.contractor.name, c.contractor.firm].filter(Boolean).join(' · ') },
        { icon: Badge, label: 'NEMSA licence', value: [c.contractor.category, c.contractor.regNo].filter(Boolean).join(' · '), mono: true },
        { icon: Event, label: 'Inspected', value: c.inspectionDate },
        { icon: EventAvailable, label: 'Approved', value: c.approvedAt },
    ]);
</script>

<svelte:head>
    <title>Certificate {c.certificateNo}</title>
</svelte:head>

{#snippet infoBox(label: string, value: string | null)}
    <div class="rounded-[10px] border border-[#B9D59A] bg-[#E8F2E1] p-1.5">
        <div class="flex flex-col items-center gap-0.5 rounded-md bg-white px-1.5 py-2.5">
            <span class="text-[9px] font-extrabold tracking-[0.1em] text-ink">{label}</span>
            <span class="font-mono text-[12.5px] font-semibold whitespace-nowrap text-[#09502E]">{value ?? '—'}</span>
        </div>
    </div>
{/snippet}

{#snippet ribbon(text: string)}
    <div class="ribbon mx-auto px-9 py-1.5 text-center text-[11px] font-extrabold tracking-[0.14em] text-white">{text}</div>
{/snippet}

<PrintShell
    title="Certificate"
    subtitle="{c.certificateNo} · A4 · 1 page"
    {backUrl}
    pageCount={1}
    {autoPrint}
    share={{ title: `Certificate ${c.certificateNo}`, url: c.verifyUrl }}
    alternate={{ label: 'Full report', href: reportUrl, icon: Description }}
>
    <section class="print-sheet paper cert-frame" aria-label="Certificate {c.certificateNo}">
        <!-- Sections spread evenly down the sheet, so short addresses don't leave a gap. -->
        <div class="cert-panel relative flex h-full flex-col justify-between gap-3 px-9 pt-8 pb-5">
            <div class="pointer-events-none absolute inset-3 rounded-[14px] border border-[#C9A84F]/70"></div>
            <img src="/images/ke-logo.png" alt="" class="pointer-events-none absolute top-[420px] left-1/2 size-[360px] -translate-x-1/2 object-contain opacity-[0.045]" />

            <!-- Header -->
            <header class="relative grid grid-cols-[96px_1fr_96px] items-center gap-3">
                <div class="flex size-[88px] items-center justify-center rounded-full bg-white ring-2 ring-[#09502E] ring-offset-2">
                    <img src="/images/ke-logo.png" alt="Kaduna Electric" class="size-[66px] object-contain" />
                </div>
                <div class="flex flex-col items-center gap-0.5 text-center">
                    <span class="text-[11px] font-bold tracking-[0.12em] text-ink">KADUNA ELECTRIC</span>
                    <span class="text-[23px] leading-tight font-extrabold tracking-[0.01em] text-[#09502E]">NEW SERVICE DEPARTMENT</span>
                    <span class="text-[12.5px] font-bold tracking-[0.06em] text-[#188815] uppercase">{APP_TITLE}</span>
                    <span class="text-[10.5px] text-mut">Inspection reports by NEMSA-licensed contractors, reviewed for new service connections</span>
                </div>
                <div class="flex justify-end"><KeSeal size={88} /></div>
            </header>

            <!-- Number · title · issue date: symmetric, so the title shares the header's centre line -->
            <div class="relative grid grid-cols-[160px_1fr_160px] items-center gap-3">
                {@render infoBox('CERTIFICATE NO.', c.certificateNo)}
                <div class="flex flex-col items-center gap-2 text-center">
                    <h1 class="text-[17px] leading-[1.28] font-extrabold tracking-[0.01em] text-ink">
                        CERTIFICATE OF BUILDING<br />ELECTRICAL INSPECTION<br />AND COMPLIANCE
                    </h1>
                    <div class="flex w-56 items-center gap-2">
                        <span class="h-px flex-1 bg-[#C9A84F]"></span><span class="size-1.5 rotate-45 bg-[#C9A84F]"></span><span class="h-px flex-1 bg-[#C9A84F]"></span>
                    </div>
                </div>
                {@render infoBox('DATE OF ISSUE', c.approvedAtShort)}
            </div>

            <!-- Statement -->
            <div class="relative flex flex-col">
                {@render ribbon('CERTIFICATION STATEMENT')}
                <div class="-mt-3 rounded-md border border-[#B9D59A] bg-white/70 px-4 pt-5 pb-3 text-[11.5px] leading-[1.6]">
                    <p class="text-justify">
                        This is to certify that the electrical installation described below was inspected by a NEMSA-licensed electrical contractor,
                        and that the inspection report was reviewed and approved by the New Service Department of Kaduna Electric.
                    </p>
                    <p class="mt-1.5 font-bold text-[#188815]">
                        Approved on {c.approvedAt}. The installation was found satisfactory for connection at the time of inspection.
                    </p>
                </div>
            </div>

            <!-- Installation details + verify -->
            <div class="relative flex flex-col gap-0">
                <div class="tab w-fit py-1.5 pr-8 pl-3.5 text-[11px] font-extrabold tracking-[0.12em] text-white">INSTALLATION DETAILS</div>
                <div class="grid grid-cols-[1fr_196px] gap-4 rounded-md rounded-tl-none border border-[#B9D59A] bg-white/70 p-3">
                    <dl class="flex flex-col">
                        {#each rows as row (row.label)}
                            {@const Icon = row.icon}
                            <div class="grid grid-cols-[20px_104px_10px_1fr] items-start border-b border-[#E1E6DF] py-[5px] text-[11px]">
                                <Icon class="mt-px size-[15px] text-[#09502E]" aria-hidden="true" />
                                <dt class="text-mut">{row.label}</dt>
                                <span class="text-mut">:</span>
                                <dd class={['font-semibold', row.mono && 'font-mono font-medium']}>{row.value || '—'}</dd>
                            </div>
                        {/each}
                        <div class="grid grid-cols-[20px_104px_10px_1fr] items-center py-[5px] text-[11px]">
                            <Verified class="size-[15px] text-[#09502E]" aria-hidden="true" />
                            <dt class="text-mut">Status</dt>
                            <span class="text-mut">:</span>
                            <dd class="font-extrabold tracking-[0.06em] text-[#188815]">APPROVED</dd>
                        </div>
                    </dl>
                    <div class="flex flex-col items-center gap-1.5 rounded-md border border-[#B9D59A] bg-[#F4F9F0] px-3 py-3 text-center">
                        <span class="text-[11px] font-extrabold tracking-[0.06em] text-[#09502E]">VERIFY THIS<br />CERTIFICATE</span>
                        <span class="text-[9.5px] text-mut">Scan the QR code or visit:</span>
                        <span class="font-mono text-[8.5px] leading-snug font-medium break-all text-[#09502E]">{c.verifyLabel}</span>
                        <img src={c.qr} alt="QR code to verify {c.certificateNo}" class="size-[124px] rounded bg-white p-1" />
                        <span class="text-[8.5px] leading-snug text-mut">Valid only when the verification page shows this certificate as approved.</span>
                    </div>
                </div>
            </div>

            <!-- Signatures -->
            <div class="relative grid grid-cols-[1fr_120px_1fr] items-end gap-4 px-2">
                <div class="flex flex-col items-center text-center">
                    {#if c.contractor.signatureUrl}
                        <img src={c.contractor.signatureUrl} alt="Contractor's signature" class="h-12 w-auto max-w-[200px] object-contain" />
                    {:else}
                        <span class="h-12"></span>
                    {/if}
                    <span class="w-full border-t border-dashed border-ink/60"></span>
                    <b class="mt-1 text-[11.5px] text-[#09502E]">{c.contractor.name}</b>
                    <span class="text-[9.5px] font-semibold text-mut">Inspecting contractor · {c.contractor.regNo}</span>
                </div>
                <div class="flex justify-center pb-1"><KeSeal size={110} /></div>
                <div class="flex flex-col items-center text-center">
                    {#if c.signatory.signatureUrl}
                        <img src={c.signatory.signatureUrl} alt="Signature of {c.signatory.name}" class="h-12 w-auto max-w-[200px] object-contain" />
                    {:else}
                        <span class="h-12"></span>
                    {/if}
                    <span class="w-full border-t border-dashed border-ink/60"></span>
                    <b class="mt-1 text-[11.5px] text-[#09502E]">{c.signatory.name}</b>
                    <span class="text-[9.5px] font-semibold text-mut">{c.signatory.title} · Kaduna Electric</span>
                </div>
            </div>

            <!-- Liability -->
            <div class="relative rounded-md bg-[#EEF3EA] px-3.5 py-2 text-[9px] leading-snug text-mut">
                <b class="text-[9px] tracking-[0.08em] text-ink">EXCLUSION OF LIABILITY</b><br />
                This certificate covers only the installation described above, as inspected and reported by the contractor named. Kaduna Electric is not
                liable for any alteration or modification made after certification. It does not replace NEMSA certification where the law requires it.
            </div>

            <div class="relative text-center text-[10px] font-extrabold tracking-[0.12em] text-[#09502E]">
                SAFE INSTALLATIONS <span class="text-[#7BB43B]">•</span> VERIFIED INSPECTIONS <span class="text-[#7BB43B]">•</span> RELIABLE SUPPLY
            </div>
        </div>
    </section>
</PrintShell>

<style>
    /* Deep-green frame with a light panel whose corners are cut inwards (client's sample). */
    .cert-frame {
        background: #09502e;
        padding: 22px;
    }

    .cert-panel {
        --r: 30px;
        --paper: #fbfcf8;
        background:
            radial-gradient(circle at 0 0, transparent var(--r), var(--paper) calc(var(--r) + 0.5px)) top left / 51% 51% no-repeat,
            radial-gradient(circle at 100% 0, transparent var(--r), var(--paper) calc(var(--r) + 0.5px)) top right / 51% 51% no-repeat,
            radial-gradient(circle at 0 100%, transparent var(--r), var(--paper) calc(var(--r) + 0.5px)) bottom left / 51% 51% no-repeat,
            radial-gradient(circle at 100% 100%, transparent var(--r), var(--paper) calc(var(--r) + 0.5px)) bottom right / 51% 51% no-repeat;
    }

    .ribbon {
        width: fit-content;
        position: relative;
        z-index: 1;
        background: #09502e;
        clip-path: polygon(14px 0, calc(100% - 14px) 0, 100% 50%, calc(100% - 14px) 100%, 14px 100%, 0 50%);
    }

    .tab {
        background: #09502e;
        border-radius: 6px 0 0 0;
        clip-path: polygon(0 0, calc(100% - 14px) 0, 100% 50%, calc(100% - 14px) 100%, 0 100%);
    }
</style>
