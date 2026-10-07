<script lang="ts" module>
    export { default as layout } from '@/layouts/ContractorLayout.svelte';
</script>

<script lang="ts">
    import Button from '@/components/Button.svelte';
    import ReportPage from '@/components/report/ReportPage.svelte';
    import ReviewBanner from '@/components/report/ReviewBanner.svelte';
    import type { Report } from '@/lib/inspection/report';
    import { index as home } from '@/routes/inspections';
    import EditNote from '~icons/ms/edit-note';

    type Props = {
        report: Report;
        printUrl: string;
        certificateUrl: string | null;
        editUrl: string | null;
        paid: string | null;
    };

    let { report, printUrl, certificateUrl, editUrl, paid }: Props = $props();

    const subtitle = $derived(
        [`Submitted ${report.submittedAt}`, paid ? `Paid ${paid.replace(/\.00$/, '')}` : null].filter(Boolean).join(' · '),
    );
</script>

<!-- CD-01 -->
<ReportPage {report} {printUrl} {certificateUrl} {subtitle} backHref={home.url()} backLabel="My inspections" contractorView>
    <ReviewBanner {report} audience="contractor">
        {#if editUrl}
            <Button size="sm" class="mt-1.5 h-11 w-fit" href={editUrl}><EditNote />Fix and resubmit</Button>
        {/if}
    </ReviewBanner>
</ReportPage>
