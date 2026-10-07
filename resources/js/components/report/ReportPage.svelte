<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import BottomBar from '@/components/BottomBar.svelte';
    import Button from '@/components/Button.svelte';
    import { REVIEW_TONE, type Report } from '@/lib/inspection/report';
    import InspectionReport from './InspectionReport.svelte';
    import ReviewBanner from './ReviewBanner.svelte';
    import ArrowBack from '~icons/ms/arrow-back';
    import ChevronRight from '~icons/ms/chevron-right';
    import Description from '~icons/ms/description';
    import MapIcon from '~icons/ms/map';
    import Print from '~icons/ms/print';
    import Share from '~icons/ms/share';
    import WorkspacePremium from '~icons/ms/workspace-premium';

    type Props = {
        report: Report;
        backHref: string;
        backLabel: string;
        /** Phone header sub-line (owner · area, or "Submitted … · Paid …"). */
        subtitle: string;
        /** Two-page A4 report (any submitted inspection). */
        printUrl: string;
        /** One-page certificate, only once NSD has approved. */
        certificateUrl: string | null;
        /** Contractor view (CD-01): share button, "you" wording. */
        contractorView?: boolean;
        /** Replaces the default review banner (admin review panel, contractor actions). */
        children?: Snippet;
    };

    let { report, backHref, backLabel, subtitle, printUrl, certificateUrl, contractorView = false, children }: Props = $props();

    const pillTone = { ok: 'bg-ok-bg text-ok', info: 'bg-info-bg text-info', imp: 'bg-imp-bg text-imp' } as const;

    async function share(): Promise<void> {
        const url = certificateUrl ?? printUrl;

        if (navigator.share) {
            await navigator.share({ title: `Inspection report ${report.ticketNo}`, url }).catch(() => {});
        } else {
            await navigator.clipboard?.writeText(url);
        }
    }

    const pills = $derived(
        [report.a.purpose, [report.a.connection, report.a.voltage].filter(Boolean).join(' · '), report.a.area].filter(Boolean) as string[],
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
    {#if contractorView}
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
                {#if report.review.status}
                    <span class={['rounded-full px-2.5 py-1', pillTone[REVIEW_TONE[report.review.status]]]}>{report.review.label}</span>
                {/if}
                {#each pills as pill (pill)}
                    <span class="rounded-full bg-sf2 px-2.5 py-1">{pill}</span>
                {/each}
            </div>
        </div>
        {#if certificateUrl}
            <Button variant="outline" size="sm" class="ml-auto h-11 border-[1.5px] bg-sf" href={printUrl}><Description />Full report</Button>
            <Button size="sm" class="h-11" href={certificateUrl}><WorkspacePremium />Certificate</Button>
        {:else}
            <Button size="sm" class="ml-auto h-11" href={printUrl}><Print />Print report</Button>
        {/if}
    </div>

    {#if children}
        {@render children()}
    {:else}
        <ReviewBanner {report} audience={contractorView ? 'contractor' : 'staff'} />
    {/if}

    <InspectionReport {report} />
</div>

<!-- Phone action bar -->
<BottomBar class="lg:hidden">
    <div class="grid grid-cols-2 gap-2.5">
        {#if certificateUrl}
            <Button variant="outline" href={printUrl} class="text-[15px]"><Description />Full report</Button>
            <Button href={certificateUrl} class="text-[15px]"><WorkspacePremium />Certificate</Button>
        {:else}
            {#if report.a.gps && !contractorView}
                <Button variant="outline" href={report.a.gps.mapsUrl} external class="text-[15px]"><MapIcon />Google Maps</Button>
            {/if}
            <Button href={printUrl} class={['text-[15px]', (!report.a.gps || contractorView) && 'col-span-2']}><Print />Print report</Button>
        {/if}
    </div>
</BottomBar>
