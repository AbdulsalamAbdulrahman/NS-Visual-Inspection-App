<script lang="ts">
    import CertificateBorder from '@/components/print/CertificateBorder.svelte';
    import PrintShell from '@/components/print/PrintShell.svelte';
    import { APP_TITLE } from '@/lib/brand';
    import Description from '~icons/ms/description';

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

    const licence = $derived([c.contractor.category, c.contractor.regNo].filter(Boolean).join(' · '));

    const details = $derived<[string, string | null][]>([
        ['Form 74 no.', c.form74No],
        ['Installation', c.description || null],
        ['Date of issue', c.approvedAtShort],
    ]);
</script>

<svelte:head>
    <title>Certificate {c.certificateNo}</title>
</svelte:head>

{#snippet signature(url: string | null, alt: string, name: string | null, role: string, org: string | null)}
    <div class="flex flex-col items-center text-center">
        {#if url}
            <img src={url} {alt} class="h-14 w-auto max-w-[210px] object-contain" />
        {:else}
            <span class="h-14"></span>
        {/if}
        <span class="w-[220px] border-t border-ink/70"></span>
        <span class="serif mt-1.5 text-[14px] font-bold text-[#0B5A33]">{name ?? '—'}</span>
        <span class="text-[10px] leading-snug text-mut">{role}</span>
        <span class="text-[10px] leading-snug text-mut">{org ?? ' '}</span>
    </div>
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
    <section class="print-sheet paper certificate relative" aria-label="Certificate {c.certificateNo}">
        <CertificateBorder />

        <div class="relative flex h-full flex-col items-center justify-between px-[86px] pt-[78px] pb-[70px] text-center">
            <!-- Issuers -->
            <header class="grid w-full grid-cols-[96px_1fr_96px] items-center gap-4">
                <img src="/images/lecan-logo.jpg" alt="Licensed Electrical Contractors Association of Nigeria (LECAN)" class="size-24 rounded-full object-cover" />
                <div class="flex flex-col items-center gap-1">
                    <span class="text-[12px] font-extrabold tracking-[0.14em] text-[#0B5A33]">KADUNA ELECTRIC · NEW SERVICE DEPARTMENT</span>
                    <span class="text-[9.5px] font-bold tracking-[0.12em] text-ink">LICENSED ELECTRICAL CONTRACTORS ASSOCIATION OF NIGERIA</span>
                    <span class="serif mt-1 text-[13px] text-[#8A6A12] italic">{APP_TITLE}</span>
                </div>
                <img src="/images/ke-logo.png" alt="Kaduna Electric" class="size-24 object-contain" />
            </header>

            <!-- Title -->
            <div class="flex flex-col items-center">
                <span class="serif text-[22px] text-[#8A6A12] italic">Certificate of</span>
                <h1 class="serif mt-1 text-[27px] leading-[1.15] font-bold tracking-[0.05em] whitespace-nowrap text-[#0B5A33]">
                    BUILDING ELECTRICAL INSPECTION
                </h1>
                <span class="serif mt-1 text-[18px] tracking-[0.18em] text-ink">AND COMPLIANCE</span>
                <svg viewBox="0 0 220 16" class="mt-3 h-4 w-[220px]" aria-hidden="true">
                    <path d="M4 8 C40 8 60 2 92 8" fill="none" stroke="#C9A13B" stroke-width="1.2" />
                    <path d="M216 8 C180 8 160 2 128 8" fill="none" stroke="#C9A13B" stroke-width="1.2" />
                    <circle cx="104" cy="8" r="3" fill="#0B5A33" />
                    <circle cx="116" cy="8" r="3" fill="#0B5A33" />
                    <circle cx="110" cy="8" r="4.5" fill="none" stroke="#C9A13B" stroke-width="1.2" />
                </svg>
                <span class="mt-3 text-[10px] font-bold tracking-[0.16em] text-mut">CERTIFICATE NO.</span>
                <span class="font-mono text-[17px] font-semibold tracking-[0.04em] text-[#0B5A33]">{c.certificateNo}</span>
            </div>

            <!-- Statement -->
            <div class="serif max-w-[580px] text-[14.5px] leading-[1.75] text-ink">
                <p>
                    This is to certify that the electrical installation at <b>{c.address ?? '—'}</b>{c.area ? ` (${c.area} service area)` : ''},
                    owned by <b>{c.ownerName ?? '—'}</b>, was inspected on <b>{c.inspectionDate ?? '—'}</b> by
                    <b>{c.contractor.name ?? '—'}</b>{c.contractor.firm ? ` of ${c.contractor.firm}` : ''}, a NEMSA-licensed electrical contractor{licence ? ` (${licence})` : ''},
                    and that the inspection report was reviewed and approved by the New Service Department of Kaduna Electric on <b>{c.approvedAt ?? '—'}</b>.
                </p>
                <p class="mt-3 text-[#0B5A33] italic">The installation was found satisfactory for connection at the time of inspection.</p>
            </div>

            <!-- Details line -->
            <dl class="grid w-full max-w-[622px] grid-cols-[0.9fr_2.2fr_0.9fr] border-y border-[#C9A13B]/70">
                {#each details as [label, value], i (label)}
                    <div class={['flex flex-col items-center gap-0.5 px-2 py-2.5', i > 0 && 'border-l border-[#C9A13B]/50']}>
                        <dt class="text-[9px] font-bold tracking-[0.14em] text-mut uppercase">{label}</dt>
                        <dd class={['text-[12px] font-semibold text-balance', label !== 'Installation' && 'font-mono']}>{value ?? '—'}</dd>
                    </div>
                {/each}
            </dl>

            <!-- Signatures and verification -->
            <div class="grid w-full grid-cols-[1fr_150px_1fr] items-end gap-4">
                {@render signature(c.contractor.signatureUrl, "Contractor's signature", c.contractor.name, 'Inspecting contractor', c.contractor.regNo)}
                <div class="flex flex-col items-center gap-1">
                    <img src={c.qr} alt="QR code to verify {c.certificateNo}" class="size-[104px]" />
                    <span class="text-[9px] font-bold tracking-[0.12em] text-[#0B5A33]">SCAN TO VERIFY</span>
                    <span class="font-mono text-[7.5px] leading-tight break-all text-mut">{c.verifyLabel}</span>
                </div>
                {@render signature(c.signatory.signatureUrl, `Signature of ${c.signatory.name}`, c.signatory.name, c.signatory.title ?? '', 'Kaduna Electric')}
            </div>

            <p class="max-w-[600px] text-[8.5px] leading-snug text-mut">
                This certificate covers only the installation described above, as inspected and reported by the contractor named, and is valid only
                when the verification page shows it as approved. Kaduna Electric and LECAN are not liable for any alteration or modification made after
                certification. It does not replace NEMSA certification where the law requires it.
            </p>
        </div>
    </section>
</PrintShell>

<style>
    .certificate {
        background: #fffdf6;
    }

    /* Classic serif for the certificate text; system fonts, so nothing extra is downloaded. */
    .serif {
        font-family: Georgia, 'Times New Roman', 'Noto Serif', serif;
    }
</style>
