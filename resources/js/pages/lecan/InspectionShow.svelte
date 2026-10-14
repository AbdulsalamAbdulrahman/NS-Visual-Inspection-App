<script lang="ts" module>
    export { default as layout } from '@/layouts/LecanLayout.svelte';
</script>

<script lang="ts">
    import type { ReceiptLinks } from '@/components/report/ReceiptCard.svelte';
    import ReportPage from '@/components/report/ReportPage.svelte';
    import ReviewPanel from '@/components/report/ReviewPanel.svelte';
    import type { Report } from '@/lib/inspection/report';
    import { approve, index, requestChanges } from '@/routes/lecan/inspections';

    type Props = { report: Report; printUrl: string; certificateUrl: string | null; receipt: ReceiptLinks | null; canReview: boolean };

    let { report, printUrl, certificateUrl, receipt, canReview }: Props = $props();
</script>

<!-- LECAN first-level review (no payment section: never sent to LECAN). -->
<ReportPage
    {report}
    {printUrl}
    {certificateUrl}
    {receipt}
    subtitle={[report.a.ownerName, report.a.area].filter(Boolean).join(' · ')}
    backHref={index.url()}
    backLabel="Reports"
>
    <ReviewPanel {report} level="lecan" {canReview} approveUrl={approve.url(report.uuid)} requestChangesUrl={requestChanges.url(report.uuid)} />
</ReportPage>
