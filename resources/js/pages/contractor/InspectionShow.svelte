<script lang="ts" module>
    export { default as layout } from '@/layouts/ContractorLayout.svelte';
</script>

<script lang="ts">
    import ReportPage from '@/components/report/ReportPage.svelte';
    import type { Report } from '@/lib/inspection/report';
    import { index as home } from '@/routes/inspections';

    let { report, printUrl, paid }: { report: Report; printUrl: string | null; paid: string | null } = $props();

    const subtitle = $derived(
        [`Submitted ${report.submittedAt}`, paid ? `Paid ${paid.replace(/\.00$/, '')}` : null].filter(Boolean).join(' · '),
    );
</script>

<!-- CD-01 -->
<ReportPage {report} {printUrl} {subtitle} backHref={home.url()} backLabel="My inspections" lockedNote />
