<script lang="ts">
    import PrintShell from '@/components/print/PrintShell.svelte';
    import { APP_TITLE } from '@/lib/brand';

    type Receipt = {
        uuid: string;
        /** Monnify's transaction reference (our payment reference if missing). */
        receiptNo: string | null;
        paymentReference: string | null;
        ticketNo: string;
        paidAt: string | null;
        channel: string | null;
        contractor: { name: string | null; firm: string | null };
        ownerName: string | null;
        address: string | null;
        area: string | null;
        form74No: string | null;
        building: string | null;
        structures: number;
        /** Per structure, for estates only. */
        unit: string | null;
        total: string;
        verifyUrl: string;
        qr: string;
    };

    type Props = { receipt: Receipt; backUrl: string | null; autoPrint: boolean };

    let { receipt: r, backUrl, autoPrint }: Props = $props();

    const paidBy = $derived([r.contractor.name, r.contractor.firm].filter(Boolean).join(' · ') || null);
    const customer = $derived<[string, string | null, boolean][]>([
        ['Property owner', r.ownerName, false],
        ['Address', r.address, false],
        ['Area office', r.area, false],
        [r.structures > 1 ? `Form 74 · ${r.structures} structures` : 'Form 74', r.form74No, true],
    ]);
    const payment = $derived<[string, string | null, boolean][]>([
        ['Receipt no.', r.receiptNo, true],
        ['Payment reference', r.paymentReference, true],
        ['Date paid', r.paidAt, true],
        ['Paid by', paidBy, false],
        ['Payment method', r.channel ? `Monnify · ${r.channel}` : 'Monnify', false],
    ]);
</script>

<svelte:head>
    <title>Receipt {r.ticketNo}</title>
</svelte:head>

{#snippet kv(rows: [string, string | null, boolean][])}
    <dl class="grid grid-cols-[150px_1fr] gap-x-4 gap-y-1.5 text-[12.5px]">
        {#each rows as [k, v, mono] (k)}
            <dt class="text-mut">{k}</dt>
            <dd class={mono ? 'font-mono font-medium' : 'font-semibold'}>{v || '—'}</dd>
        {/each}
    </dl>
{/snippet}

<PrintShell title="Payment receipt" subtitle="{r.ticketNo} · A4 · 1 page" {backUrl} pageCount={1} {autoPrint} share={{ title: `Receipt ${r.ticketNo}`, url: r.verifyUrl }}>
    <section class="print-sheet paper flex flex-col gap-6 px-12 pt-10 pb-8" aria-label="Receipt {r.ticketNo}">
        <div class="flex items-center gap-3.5 border-b-2 border-brand pb-3">
            <img src="/images/ke-logo.png" alt="Kaduna Electric" class="size-[58px] flex-none object-contain" />
            <img src="/images/lecan-logo.jpg" alt="Licensed Electrical Contractors Association of Nigeria (LECAN)" class="size-[54px] flex-none rounded-full object-cover" />
            <div class="flex flex-1 flex-col gap-0.5">
                <span class="font-mono text-[10px] font-semibold tracking-[0.1em] text-brand">KADUNA ELECTRIC · NEW SERVICE DEPARTMENT · LECAN</span>
                <span class="text-[20px] leading-tight font-extrabold tracking-[-0.01em]">{APP_TITLE}</span>
                <span class="text-[11px] text-mut">Payment receipt · inspection fee</span>
            </div>
            <div class="flex items-center gap-2.5">
                <div class="flex flex-col items-end gap-0.5">
                    <span class="font-mono text-[9px] font-semibold tracking-[0.1em] text-mut">TICKET</span>
                    <span class="font-mono text-sm font-semibold text-brand">{r.ticketNo}</span>
                </div>
                <img src={r.qr} alt="QR code to verify {r.ticketNo}" class="size-16" />
            </div>
        </div>

        <div class="flex items-end justify-between gap-6">
            <div class="flex flex-col gap-1">
                <span class="text-[11px] font-bold tracking-[0.08em] text-brand">RECEIPT</span>
                <span class="text-[26px] font-extrabold tracking-[-0.01em]">Inspection fee paid</span>
            </div>
            <div class="flex flex-col items-end gap-0.5">
                <span class="text-[11px] font-semibold text-mut">Amount paid</span>
                <span class="font-mono text-[30px] font-bold tabular-nums">{r.total}</span>
            </div>
        </div>

        <div class="grid grid-cols-2 gap-8">
            <div class="flex flex-col gap-2">
                <span class="text-[11px] font-bold tracking-[0.08em] text-brand">CUSTOMER</span>
                {@render kv(customer)}
            </div>
            <div class="flex flex-col gap-2">
                <span class="text-[11px] font-bold tracking-[0.08em] text-brand">PAYMENT</span>
                {@render kv(payment)}
            </div>
        </div>

        <div class="overflow-hidden rounded-md border border-[#C9D2C7] text-[12.5px]" role="table" aria-label="Charges">
            <div role="row" class="grid grid-cols-[1fr_110px_130px_130px] gap-3 bg-[#EEF2EC] px-3.5 py-2 text-[10.5px] font-bold tracking-[0.06em] text-mut">
                <span role="columnheader">DESCRIPTION</span>
                <span role="columnheader" class="text-right">STRUCTURES</span>
                <span role="columnheader" class="text-right">UNIT FEE</span>
                <span role="columnheader" class="text-right">AMOUNT</span>
            </div>
            <div role="row" class="grid grid-cols-[1fr_110px_130px_130px] gap-3 border-t border-[#C9D2C7] px-3.5 py-2.5">
                <span role="cell">Building electrical inspection{r.building ? ` · ${r.building}` : ''}</span>
                <span role="cell" class="text-right font-mono tabular-nums">{r.structures}</span>
                <span role="cell" class="text-right font-mono tabular-nums">{r.unit ?? r.total}</span>
                <span role="cell" class="text-right font-mono font-semibold tabular-nums">{r.total}</span>
            </div>
            <div role="row" class="grid grid-cols-[1fr_130px] gap-3 border-t-2 border-brand px-3.5 py-2.5">
                <b role="cell">Total paid</b>
                <b role="cell" class="text-right font-mono tabular-nums">{r.total}</b>
            </div>
        </div>

        <p class="text-[11.5px] leading-[1.5] text-mut">
            Price set by LECAN, reviewed by Kaduna Electric. This receipt confirms payment of the inspection fee only; the certificate is issued once the report is
            approved. Check this ticket at <span class="font-mono">{r.verifyUrl.replace(/^https?:\/\//, '')}</span>.
        </p>

        <div class="mt-auto flex items-center justify-between border-t border-[#C9D2C7] pt-2.5 font-mono text-[10px] font-medium text-mut">
            <span>{r.ticketNo} · {r.receiptNo}</span>
            <span class="flex items-center gap-2"><span class="h-1 w-7 -skew-x-[26deg] bg-acc"></span>Page 1 of 1</span>
        </div>
    </section>
</PrintShell>
