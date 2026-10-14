<script lang="ts" module>
    export { default as layout } from '@/layouts/RepLayout.svelte';
</script>

<script lang="ts">
    import type { ReceiptLinks } from '@/components/report/ReceiptCard.svelte';
    import ReportPage from '@/components/report/ReportPage.svelte';
    import type { Report } from '@/lib/inspection/report';
    import { index } from '@/routes/rep/inspections';

    let { report, printUrl, certificateUrl, receipt }: { report: Report; printUrl: string; certificateUrl: string | null; receipt: ReceiptLinks | null } = $props();
</script>

<!-- SR-M3: same report, no payment section (never sent to reps). -->
<ReportPage
    {report}
    {printUrl}
    {certificateUrl}
    {receipt}
    subtitle={[report.a.ownerName, report.a.area].filter(Boolean).join(' · ')}
    backHref={index.url()}
    backLabel="Inspections"
/>
