<script lang="ts" module>
    export { default as layout } from '@/layouts/ContractorLayout.svelte';
</script>

<script lang="ts">
    import Button from '@/components/Button.svelte';
    import Logo from '@/components/Logo.svelte';
    import { index as home } from '@/routes/inspections';
    import Check from '~icons/ms/check';
    import Description from '~icons/ms/description';
    import Print from '~icons/ms/print';

    type Props = {
        ticket: {
            uuid: string;
            ticketNo: string;
            /** SVG data URI of the verify-page QR code. */
            qr: string;
            ownerName: string | null;
            address: string | null;
            area: string | null;
            amountPaid: string | null;
            paymentReference: string | null;
            confirmedAt: string | null;
            date: string | null;
            reportUrl: string | null;
            printUrl: string | null;
        };
    };

    let { ticket }: Props = $props();

    const rows = $derived([
        { label: 'Owner', value: ticket.ownerName, mono: false, bold: true },
        { label: 'Address', value: ticket.address, mono: false, bold: false },
        { label: 'Service area', value: ticket.area, mono: false, bold: false },
        { label: 'Amount paid', value: ticket.amountPaid, mono: true, bold: true },
        { label: 'Payment ref.', value: ticket.paymentReference, mono: true, bold: false },
        { label: 'Date', value: ticket.date, mono: true, bold: false },
    ]);
</script>

<svelte:head>
    <title>{ticket.ticketNo} · KENS</title>
</svelte:head>

{#snippet details(cls: string)}
    <dl class={['grid grid-cols-[auto_1fr] gap-x-3.5 gap-y-[7px] text-sm lg:gap-x-[18px] lg:gap-y-2.5 lg:text-[15px]', cls]}>
        {#each rows as row (row.label)}
            <dt class="text-mut">{row.label}</dt>
            <dd class={['text-right lg:text-left', row.mono && 'font-mono', row.bold && 'font-semibold', row.label === 'Payment ref.' && 'text-[13px] break-all lg:text-sm']}>
                {row.value ?? '—'}
            </dd>
        {/each}
    </dl>
{/snippet}

{#snippet actions()}
    <div class="grid grid-cols-2 gap-2.5 lg:flex lg:gap-3">
        {#if ticket.reportUrl}
            <Button variant="outline" href={ticket.reportUrl} class="text-base lg:h-[52px]"><Description />View report</Button>
        {/if}
        {#if ticket.printUrl}
            <Button href={ticket.printUrl} class="text-base lg:h-[52px]"><Print />Print report</Button>
        {/if}
    </div>
    <Button variant="ghost" href={home.url()} class="text-[15px] text-brand lg:h-[52px] lg:text-base">Back to my inspections</Button>
{/snippet}

<!-- Phone: CT-01 -->
<div class="relative flex flex-1 flex-col lg:hidden">
    <div class="absolute inset-x-0 top-0 h-[250px] overflow-hidden bg-hero" aria-hidden="true">
        <div class="absolute top-[150px] -left-[60px] h-[30px] w-[560px] -rotate-[26deg] bg-lime"></div>
    </div>
    <div class="relative flex items-center gap-3 px-6 pt-[calc(14px+env(safe-area-inset-top))] pb-[18px] text-white">
        <span class="flex size-11 items-center justify-center rounded-full bg-lime text-[#062012]"><Check class="size-7" /></span>
        <div class="flex flex-col">
            <h1 class="text-[21px] font-bold">Inspection submitted</h1>
            <span class="text-sm opacity-85">Payment confirmed{ticket.confirmedAt ? ` · ${ticket.confirmedAt}` : ''}</span>
        </div>
    </div>
    <section class="relative mx-4 flex flex-col overflow-hidden rounded-[20px] bg-sf shadow-[0_10px_30px_rgba(0,0,0,.14)]" aria-label="Ticket">
        <div class="flex flex-col items-center gap-3 px-5 pt-[18px] pb-3.5">
            <span class="font-mono text-xs font-semibold tracking-[0.08em] text-mut">TICKET NUMBER</span>
            <span class="font-mono text-[23px] font-semibold tracking-[-0.01em] text-brand">{ticket.ticketNo}</span>
            <img src={ticket.qr} alt="QR code to verify ticket {ticket.ticketNo}" class="size-[168px] rounded-xl border border-line bg-white p-1.5" />
        </div>
        <div class="relative mx-[18px] h-0 border-t-2 border-dashed border-line" aria-hidden="true">
            <span class="absolute -top-3 -left-[30px] size-[22px] rounded-full bg-bg"></span>
            <span class="absolute -top-3 -right-[30px] size-[22px] rounded-full bg-bg"></span>
        </div>
        {@render details('px-5 py-3.5')}
    </section>
    <div class="flex flex-col gap-2 px-4 pt-3.5 pb-[max(12px,env(safe-area-inset-bottom))]">
        {@render actions()}
    </div>
</div>

<!-- Desktop: CK-04 -->
<div class="hidden flex-1 flex-col items-center justify-center gap-7 p-10 lg:flex">
    <div class="flex items-center gap-3.5">
        <span class="flex size-[52px] items-center justify-center rounded-full bg-lime text-[#062012]"><Check class="size-8" /></span>
        <div class="flex flex-col">
            <h1 class="text-[28px] font-bold">Inspection submitted</h1>
            <span class="text-[15px] text-mut">Payment confirmed by Monnify · {ticket.date}</span>
        </div>
    </div>
    <section class="grid min-h-[360px] w-[940px] max-w-full grid-cols-[340px_1fr] overflow-hidden rounded-3xl bg-sf shadow-[0_20px_50px_rgba(0,0,0,.25)]" aria-label="Ticket">
        <div class="relative flex flex-col justify-between overflow-hidden bg-hero p-8 text-white">
            <div class="absolute top-[230px] -left-[60px] h-[30px] w-[520px] -rotate-[26deg] bg-lime" aria-hidden="true"></div>
            <div class="absolute top-[290px] left-[30px] h-[30px] w-[520px] -rotate-[26deg] bg-leaf" aria-hidden="true"></div>
            <div class="relative flex items-center gap-2.5"><Logo size={40} ring={false} /><span class="mono-caps">New Service Dept.</span></div>
            <div class="relative mb-[90px] flex flex-col gap-1.5">
                <span class="font-mono text-xs font-semibold tracking-[0.08em] opacity-80">TICKET NUMBER</span>
                <span class="font-mono text-[25px] font-semibold">{ticket.ticketNo}</span>
            </div>
        </div>
        <div class="grid grid-cols-[1fr_auto] items-center gap-7 p-8">
            {@render details('')}
            <img src={ticket.qr} alt="QR code to verify ticket {ticket.ticketNo}" class="size-[184px] rounded-[14px] bg-white p-2" />
        </div>
    </section>
    <div class="flex items-center gap-3">{@render actions()}</div>
</div>
