<script lang="ts">
    import { page, useForm } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import Button from '@/components/Button.svelte';
    import ReviewBanner from '@/components/report/ReviewBanner.svelte';
    import type { Report } from '@/lib/inspection/report';
    import CheckCircle from '~icons/ms/check-circle';
    import ErrorIcon from '~icons/ms/error';
    import History from '~icons/ms/history';
    import Undo from '~icons/ms/undo';
    import WorkspacePremium from '~icons/ms/workspace-premium';

    /**
     * Review actions on a report, for both levels: a LECAN official approves
     * it for NSD; NSD approves it and issues the certificate. Either sends it
     * back with a note. Also shows the review history.
     */
    type Props = {
        report: Report;
        level: 'lecan' | 'nsd';
        canReview: boolean;
        approveUrl: string;
        requestChangesUrl: string;
        /** NSD only: approving is blocked until the certificate signatory is set. */
        approveBlocked?: Snippet;
    };

    let { report, level, canReview, approveUrl, requestChangesUrl, approveBlocked }: Props = $props();

    let mode = $state<'idle' | 'confirm-approve' | 'request-changes'>('idle');

    const approveForm = useForm({});
    const changesForm = useForm({ reason: '' });

    const reviewError = $derived((page.props.errors as Record<string, string> | undefined)?.review);
    const history = $derived(report.review.history ?? []);

    const copy = $derived(
        level === 'lecan'
            ? {
                  approve: 'Approve for NSD',
                  confirmTitle: `Approve ${report.ticketNo} for LECAN?`,
                  confirmBody: 'It goes to the New Service Department for final approval, and your name appears on the certificate. Check the report below first.',
                  confirming: 'Approving…',
              }
            : {
                  approve: 'Approve & issue certificate',
                  confirmTitle: `Issue certificate ${report.ticketNo}?`,
                  confirmBody: 'The contractor is emailed and anyone scanning the QR code will see it as approved. Check the report below first.',
                  confirming: 'Issuing…',
              },
    );

    function doApprove(): void {
        approveForm.post(approveUrl, { preserveScroll: true, onSuccess: () => (mode = 'idle') });
    }

    function sendBack(event: SubmitEvent): void {
        event.preventDefault();
        changesForm.post(requestChangesUrl, {
            preserveScroll: true,
            onSuccess: () => {
                mode = 'idle';
                changesForm.reset();
            },
        });
    }
</script>

<section class="flex flex-col gap-3" aria-label="Review">
    <ReviewBanner {report}>
        {#if canReview}
            {#if mode === 'idle'}
                <div class="mt-2 flex flex-wrap gap-2">
                    <Button size="sm" class="h-11" disabled={!!approveBlocked} onclick={() => (mode = 'confirm-approve')}>
                        {#if level === 'lecan'}<CheckCircle />{:else}<WorkspacePremium />{/if}{copy.approve}
                    </Button>
                    <Button size="sm" variant="outline" class="h-11 border-[1.5px] bg-sf" onclick={() => (mode = 'request-changes')}>
                        <Undo />Request changes
                    </Button>
                </div>
                {@render approveBlocked?.()}
            {:else if mode === 'confirm-approve'}
                <div class="mt-2 flex flex-col gap-2 rounded-xl border border-line bg-sf p-3.5">
                    <b class="text-[15px]">{copy.confirmTitle}</b>
                    <span class="text-sm text-mut">{copy.confirmBody}</span>
                    <div class="flex flex-wrap gap-2">
                        <Button size="sm" class="h-11" disabled={approveForm.processing} onclick={doApprove}>
                            {approveForm.processing ? copy.confirming : 'Yes, approve'}
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
                        placeholder="e.g. Earth resistance of 6.2 Ω is above the 5 Ω limit — improve the electrode and re-test, then attach a photo of the reading."
                        aria-invalid={!!changesForm.errors.reason || undefined}
                        aria-describedby={changesForm.errors.reason ? 'reason-error' : undefined}
                        class="rounded-[10px] border-[1.5px] border-line bg-sf p-3 text-[15px] outline-none focus:border-pri"
                    ></textarea>
                    {#if changesForm.errors.reason}
                        <span id="reason-error" class="flex items-center gap-1.5 text-sm font-semibold text-bad"><ErrorIcon class="size-4" />{changesForm.errors.reason}</span>
                    {/if}
                    <span class="text-[13px] text-mut">
                        The contractor gets this note by email, fixes the report and resubmits without paying again. It then starts again with LECAN.
                    </span>
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
