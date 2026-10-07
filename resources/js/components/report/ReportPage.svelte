<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import BottomBar from '@/components/BottomBar.svelte';
    import Button from '@/components/Button.svelte';
    import type { Report } from '@/lib/inspection/report';
    import InspectionReport from './InspectionReport.svelte';
    import ArrowBack from '~icons/ms/arrow-back';
    import ChevronRight from '~icons/ms/chevron-right';
    import Download from '~icons/ms/download';
    import Lock from '~icons/ms/lock';
    import MapIcon from '~icons/ms/map';
    import Print from '~icons/ms/print';
    import Share from '~icons/ms/share';

    type Props = {
        report: Report;
        backHref: string;
        backLabel: string;
        /** Phone header sub-line (owner · area, or "Submitted … · Paid …"). */
        subtitle: string;
        printUrl: string | null;
        /** Contractor view shows the "can't be edited" note (CD-01). */
        lockedNote?: boolean;
        /** Status pill on desktop ("Paid · submitted" for admins). */
        statusPill?: string | null;
        /** Where the desktop chrome starts (rep shell switches at md). */
        children?: Snippet;
    };

    let { report, backHref, backLabel, subtitle, printUrl, lockedNote = false, statusPill = null, children }: Props = $props();

    async function share(): Promise<void> {
        const url = printUrl ?? window.location.href;

        if (navigator.share) {
            await navigator.share({ title: `Inspection report ${report.ticketNo}`, url }).catch(() => {});
        } else {
            await navigator.clipboard?.writeText(url);
        }
    }

    const pills = $derived(
        [statusPill, report.a.purpose, [report.a.connection, report.a.voltage].filter(Boolean).join(' · '), report.a.area].filter(Boolean) as string[],
    );
</script>

<svelte:head>
    <title>{report.ticketNo} · KENS</title>
</svelte:head>

<!-- Phone header (AM-04 / CD-01 / SR-M3) -->
<header class="sticky top-0 z-20 flex items-center gap-1 border-b border-line bg-sf px-2 pt-[env(safe-area-inset-top)] pb-3 lg:hidden">
    <Link href={backHref} class="flex size-12 items-center justify-center rounded-xl text-ink" aria-label={backLabel}>
        <ArrowBack class="size-6" />
    </Link>
    <div class="flex min-w-0 flex-1 flex-col gap-0.5 pt-3">
        <h1 class="font-mono text-base font-semibold text-brand">{report.ticketNo}</h1>
        <span class="truncate text-[13px] text-mut">{subtitle}</span>
    </div>
    {#if lockedNote}
        <button type="button" class="mt-3 flex size-12 items-center justify-center rounded-xl text-ink" aria-label="Share report" onclick={share}>
            <Share class="size-6" />
        </button>
    {/if}
</header>

<div class="flex flex-col gap-[18px] px-4 py-3.5 lg:px-10 lg:py-6">
    <!-- Desktop header (AD-03) -->
    <nav class="hidden items-center gap-1.5 text-sm text-mut lg:flex" aria-label="Breadcrumb">
        <Link href={backHref} class="font-semibold no-underline hover:underline">{backLabel}</Link>
        <ChevronRight class="size-[18px]" />
        <span class="font-mono" aria-current="page">{report.ticketNo}</span>
    </nav>
    <div class="hidden items-center gap-3.5 lg:flex">
        <div class="flex flex-col gap-1.5">
            <span class="font-mono text-2xl font-semibold text-brand">{report.ticketNo}</span>
            <div class="flex flex-wrap gap-2 text-[13px] font-bold">
                {#each pills as pill, i (pill)}
                    <span class={['rounded-full px-2.5 py-1', i === 0 && statusPill ? 'bg-ok-bg text-ok' : 'bg-sf2']}>{pill}</span>
                {/each}
            </div>
        </div>
        {#if printUrl}
            <Button variant="outline" size="sm" class="ml-auto h-11 border-[1.5px] bg-sf" href="{printUrl}?pdf=1"><Download />PDF</Button>
            <Button size="sm" class="h-11" href={printUrl}><Print />Print report</Button>
        {/if}
    </div>

    {#if lockedNote}
        <div class="flex items-center gap-2 rounded-xl bg-sf2 px-3.5 py-2.5 text-sm text-mut">
            <Lock class="size-[18px]" />Submitted reports can't be edited.
        </div>
    {/if}

    {@render children?.()}

    <InspectionReport {report} />
</div>

<!-- Phone action bar -->
<BottomBar class="lg:hidden">
    <div class={['grid gap-2.5', report.a.gps && !lockedNote ? 'grid-cols-2' : 'grid-cols-1']}>
        {#if report.a.gps && !lockedNote}
            <Button variant="outline" href={report.a.gps.mapsUrl} external class="text-[15px]"><MapIcon />Google Maps</Button>
        {/if}
        {#if printUrl}
            <Button href={printUrl} class="text-[15px]"><Print />{lockedNote ? 'Print report' : 'Print'}</Button>
        {/if}
    </div>
</BottomBar>
