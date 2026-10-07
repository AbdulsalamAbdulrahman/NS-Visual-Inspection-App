<script lang="ts" module>
    export { default as layout } from '@/layouts/AdminLayout.svelte';
</script>

<script lang="ts">
    import { Link, page, useForm } from '@inertiajs/svelte';
    import Button from '@/components/Button.svelte';
    import ReportPage from '@/components/report/ReportPage.svelte';
    import ReviewBanner from '@/components/report/ReviewBanner.svelte';
    import type { Report } from '@/lib/inspection/report';
    import { edit as certificateSettings } from '@/routes/admin/certificate';
    import { approve, index, requestChanges } from '@/routes/admin/inspections';
    import ErrorIcon from '~icons/ms/error';
    import History from '~icons/ms/history';
    import Undo from '~icons/ms/undo';
    import WorkspacePremium from '~icons/ms/workspace-premium';

    type Props = {
        report: Report;
        printUrl: string;
        certificateUrl: string | null;
        canReview: boolean;
        signatoryReady: boolean;
    };

    let { report, printUrl, certificateUrl, canReview, signatoryReady }: Props = $props();

    let mode = $state<'idle' | 'confirm-approve' | 'request-changes'>('idle');

    const approveForm = useForm({});
    const changesForm = useForm({ reason: '' });

    const reviewError = $derived((page.props.errors as Record<string, string> | undefined)?.review);
    const history = $derived(report.review.history ?? []);

    function doApprove(): void {
        approveForm.post(approve.url(report.uuid), { preserveScroll: true, onSuccess: () => (mode = 'idle') });
    }

    function sendBack(event: SubmitEvent): void {
        event.preventDefault();
        changesForm.post(requestChanges.url(report.uuid), {
            preserveScroll: true,
            onSuccess: () => {
                mode = 'idle';
                changesForm.reset();
            },
        });
    }
</script>

<!-- AD-03 / AM-04 + NSD review -->
<ReportPage
    {report}
    {printUrl}
    {certificateUrl}
    subtitle={[report.a.ownerName, report.a.area].filter(Boolean).join(' · ')}
    backHref={index.url()}
    backLabel="Inspections"
>
    <section class="flex flex-col gap-3" aria-label="NSD review">
        <ReviewBanner {report}>
            {#if canReview}
                {#if mode === 'idle'}
                    <div class="mt-2 flex flex-wrap gap-2">
                        <Button size="sm" class="h-11" disabled={!signatoryReady} onclick={() => (mode = 'confirm-approve')}>
                            <WorkspacePremium />Approve &amp; issue certificate
                        </Button>
                        <Button size="sm" variant="outline" class="h-11 border-[1.5px] bg-sf" onclick={() => (mode = 'request-changes')}>
                            <Undo />Request changes
                        </Button>
                    </div>
                    {#if !signatoryReady}
                        <span class="text-[13px] text-mut">
                            Set the <Link href={certificateSettings.url()} class="font-bold">certificate signatory</Link> before approving.
                        </span>
                    {/if}
                {:else if mode === 'confirm-approve'}
                    <div class="mt-2 flex flex-col gap-2 rounded-xl border border-line bg-sf p-3.5">
                        <b class="text-[15px]">Issue certificate {report.ticketNo}?</b>
                        <span class="text-sm text-mut">
                            The contractor is emailed and anyone scanning the QR code will see it as approved. Check the report below first.
                        </span>
                        <div class="flex flex-wrap gap-2">
                            <Button size="sm" class="h-11" disabled={approveForm.processing} onclick={doApprove}>
                                {approveForm.processing ? 'Issuing…' : 'Yes, approve'}
                            </Button>
                            <Button size="sm" variant="ghost" class="h-11" onclick={() => (mode = 'idle')}>Cancel</Button>
                        </div>
                    </div>
                {:else}
                    <form class="mt-2 flex flex-col gap-2 rounded-xl border border-line bg-sf p-3.5" onsubmit={sendBack} novalidate>
                        <label for="reason" class="text-[15px] font-semibold">What should the contractor change?</label>
                        <textarea
                            id="reason"
                            bind:value={changesForm.reason}
                            rows="4"
                            maxlength="2000"
                            placeholder="e.g. Earth resistance of 2.6 Ω is above the 2 Ω limit — improve the electrode and re-test, then attach a photo of the reading."
                            aria-invalid={!!changesForm.errors.reason || undefined}
                            aria-describedby={changesForm.errors.reason ? 'reason-error' : undefined}
                            class="rounded-[10px] border-[1.5px] border-line bg-sf p-3 text-[15px] outline-none focus:border-pri"
                        ></textarea>
                        {#if changesForm.errors.reason}
                            <span id="reason-error" class="flex items-center gap-1.5 text-sm font-semibold text-bad"><ErrorIcon class="size-4" />{changesForm.errors.reason}</span>
                        {/if}
                        <span class="text-[13px] text-mut">The contractor gets this note by email, fixes the report and resubmits without paying again.</span>
                        <div class="flex flex-wrap gap-2">
                            <Button type="submit" size="sm" class="h-11" disabled={changesForm.processing || changesForm.reason.trim().length === 0}>
                                {changesForm.processing ? 'Sending…' : 'Send back to contractor'}
                            </Button>
                            <Button size="sm" variant="ghost" class="h-11" onclick={() => (mode = 'idle')}>Cancel</Button>
                        </div>
                    </form>
                {/if}
            {/if}
            {#if reviewError}
                <span class="flex items-center gap-1.5 text-sm font-semibold text-bad" role="alert"><ErrorIcon class="size-4" />{reviewError}</span>
            {/if}
        </ReviewBanner>

        {#if history.length > 0}
            <details class="rounded-2xl border border-line bg-sf px-4 py-3 text-sm">
                <summary class="flex cursor-pointer items-center gap-2 font-semibold"><History class="size-5 text-mut" />Review history · {history.length}</summary>
                <ol class="mt-3 flex flex-col gap-2.5 border-l-2 border-line pl-4">
                    {#each history as entry (entry.id)}
                        <li class="flex flex-col gap-0.5">
                            <span><b>{entry.label}</b>{entry.by ? ` · ${entry.by}` : ''}</span>
                            <span class="font-mono text-xs text-mut">{entry.at}</span>
                            {#if entry.note}<span class="whitespace-pre-line text-mut">“{entry.note}”</span>{/if}
                        </li>
                    {/each}
                </ol>
            </details>
        {/if}
    </section>
</ReportPage>
