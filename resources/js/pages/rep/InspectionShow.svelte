<script lang="ts" module>
    export { default as layout } from '@/layouts/RepLayout.svelte';
</script>

<script lang="ts">
    import ReportPage from '@/components/report/ReportPage.svelte';
    import type { Report } from '@/lib/inspection/report';
    import { index } from '@/routes/rep/inspections';

    let { report, printUrl }: { report: Report; printUrl: string | null } = $props();
</script>

<!-- SR-M3: same report, no payment section (never sent to reps). -->
<ReportPage
    {report}
    {printUrl}
    subtitle={[report.a.ownerName, report.a.area].filter(Boolean).join(' · ')}
    backHref={index.url()}
    backLabel="Inspections"
    statusPill="Submitted"
/>
