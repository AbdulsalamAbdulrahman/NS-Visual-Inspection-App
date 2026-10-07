<script lang="ts" module>
    export { default as layout } from '@/layouts/AdminLayout.svelte';
</script>

<script lang="ts">
    import ReportPage from '@/components/report/ReportPage.svelte';
    import type { Report } from '@/lib/inspection/report';
    import { index } from '@/routes/admin/inspections';

    let { report, printUrl }: { report: Report; printUrl: string | null } = $props();
</script>

<!-- AD-03 / AM-04 -->
<ReportPage
    {report}
    {printUrl}
    subtitle={[report.a.ownerName, report.a.area].filter(Boolean).join(' · ')}
    backHref={index.url()}
    backLabel="Inspections"
    statusPill={report.payment ? 'Paid · submitted' : 'Submitted'}
/>
