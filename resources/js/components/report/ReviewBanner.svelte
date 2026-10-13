<script lang="ts">
    import type { Snippet } from 'svelte';
    import type { Report } from '@/lib/inspection/report';
    import CheckCircle from '~icons/ms/check-circle';
    import HourglassTop from '~icons/ms/hourglass-top';
    import Warning from '~icons/ms/warning';

    type Props = {
        report: Report;
        /** Copy for whoever is reading: the contractor gets "you" wording. */
        audience?: 'contractor' | 'staff';
        /** Actions under the message (Fix & resubmit, Approve…). */
        children?: Snippet;
    };

    let { report, audience = 'staff', children }: Props = $props();

    const review = $derived(report.review);
    const chapter = $derived(review.chapter ? `${review.chapter} State` : 'state');
    const by = $derived(review.sentBackBy === 'LECAN' ? `LECAN (${chapter} chapter)` : 'NSD');
</script>

{#if review.status === 'approved'}
    <div class="flex gap-3 rounded-2xl border border-ok bg-ok-bg px-4 py-3.5" role="status">
        <CheckCircle class="size-6 flex-none text-ok" aria-hidden="true" />
        <div class="flex min-w-0 flex-1 flex-col gap-1">
            <b class="text-[15px] text-ok">Approved · certificate issued</b>
            <span class="text-sm">Approved{review.lecanApprovedBy ? ` by LECAN (${review.lecanApprovedBy}) and` : ''} by the New Service Department on {review.approvedAt}. Certificate no. <span class="font-mono">{report.ticketNo}</span>.</span>
            {@render children?.()}
        </div>
    </div>
{:else if review.status === 'changes_requested'}
    <div class="flex gap-3 rounded-2xl border border-imp bg-imp-bg px-4 py-3.5" role="status">
        <Warning class="size-6 flex-none text-imp" aria-hidden="true" />
        <div class="flex min-w-0 flex-1 flex-col gap-1.5">
            <b class="text-[15px] text-imp">
                {audience === 'contractor' ? `${by} needs changes before the certificate can be issued` : `Sent back for changes by ${by}`}
            </b>
            {#if review.note}
                <blockquote class="border-l-[3px] border-imp pl-3 text-sm whitespace-pre-line">{review.note}</blockquote>
            {/if}
            <span class="text-[13px] text-mut">
                {audience === 'contractor'
                    ? 'Fix the report and resubmit. It goes back to LECAN, then NSD. There is nothing more to pay.'
                    : `Waiting for the contractor to resubmit (sent ${review.reviewedAt}).`}
            </span>
            {@render children?.()}
        </div>
    </div>
{:else if review.status === 'pending_lecan' || review.status === 'pending_nsd'}
    <div class="flex gap-3 rounded-2xl border border-info bg-info-bg px-4 py-3.5" role="status">
        <HourglassTop class="size-6 flex-none text-info" aria-hidden="true" />
        <div class="flex min-w-0 flex-1 flex-col gap-1">
            <b class="text-[15px] text-info">{review.status === 'pending_lecan' ? `Awaiting LECAN review · ${chapter}` : 'Approved by LECAN · awaiting NSD'}</b>
            <span class="text-sm">
                {#if review.status === 'pending_lecan'}
                    {audience === 'contractor'
                        ? `The LECAN ${chapter} chapter reviews this report first, then the New Service Department. You'll get an email when the certificate is issued or if anything needs changing.`
                        : `Step 1 of 2: the LECAN ${chapter} chapter reviews it, then NSD gives final approval. No certificate has been issued yet.`}
                {:else}
                    Approved for LECAN by {review.lecanApprovedBy ?? 'the state chapter'}{review.lecanApprovedAt ? ` on ${review.lecanApprovedAt}` : ''}.
                    {audience === 'contractor' ? 'The New Service Department gives the final approval.' : 'Step 2 of 2: waiting for NSD final approval.'}
                {/if}
            </span>
            {@render children?.()}
        </div>
    </div>
{/if}
