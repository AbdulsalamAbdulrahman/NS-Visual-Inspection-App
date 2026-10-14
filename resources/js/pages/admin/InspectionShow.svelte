<script lang="ts" module>
    export { default as layout } from '@/layouts/AdminLayout.svelte';
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import type { ReceiptLinks } from '@/components/report/ReceiptCard.svelte';
    import ReportPage from '@/components/report/ReportPage.svelte';
    import ReviewPanel from '@/components/report/ReviewPanel.svelte';
    import type { Report } from '@/lib/inspection/report';
    import { edit as certificateSettings } from '@/routes/admin/certificate';
    import { approve, index, requestChanges } from '@/routes/admin/inspections';

    type Props = {
        report: Report;
        printUrl: string;
        certificateUrl: string | null;
        receipt: ReceiptLinks | null;
        canReview: boolean;
        signatoryReady: boolean;
    };

    let { report, printUrl, certificateUrl, receipt, canReview, signatoryReady }: Props = $props();
</script>

{#snippet signatoryMissing()}
    <span class="text-[13px] text-mut">
        Set the <Link href={certificateSettings.url()} class="font-bold">certificate signatory</Link> before approving.
    </span>
{/snippet}

<!-- AD-03 / AM-04 + NSD final review (after LECAN) -->
<ReportPage
    {report}
    {printUrl}
    {certificateUrl}
    {receipt}
    subtitle={[report.a.ownerName, report.a.area].filter(Boolean).join(' · ')}
    backHref={index.url()}
    backLabel="Inspections"
>
    <ReviewPanel
        {report}
        level="nsd"
        {canReview}
        approveUrl={approve.url(report.uuid)}
        requestChangesUrl={requestChanges.url(report.uuid)}
        approveBlocked={signatoryReady ? undefined : signatoryMissing}
    />
</ReportPage>
