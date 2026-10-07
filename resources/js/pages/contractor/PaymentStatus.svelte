<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { onDestroy, onMount } from 'svelte';
    import Button from '@/components/Button.svelte';
    import { sendJson } from '@/lib/http';
    import { edit, index as home } from '@/routes/inspections';
    import { verify } from '@/routes/payments';
    import CheckCircle from '~icons/ms/check-circle';
    import CreditCardOff from '~icons/ms/credit-card-off';
    import Inventory from '~icons/ms/inventory';
    import Pending from '~icons/ms/pending';
    import RadioButtonUnchecked from '~icons/ms/radio-button-unchecked';
    import Refresh from '~icons/ms/refresh';

    type Status = 'pending' | 'paid' | 'failed' | 'abandoned';

    type Props = {
        payment: {
            reference: string;
            transactionReference: string | null;
            status: Status;
            amount: string;
            updatedAt: string | null;
            reason: string | null;
        };
        inspection: { uuid: string };
    };

    let { payment, inspection }: Props = $props();

    type VerifyResponse = { status: Status; transactionReference: string | null; reason: string | null; ticketUrl: string | null };

    /** CP-02 says transfers can take up to 2 minutes to confirm. */
    const POLL_EVERY_MS = 4000;
    const GIVE_UP_AFTER_MS = 120_000;

    // svelte-ignore state_referenced_locally
    let status = $state<Status>(payment.status);
    // svelte-ignore state_referenced_locally
    let reference = $state(payment.transactionReference ?? payment.reference);
    // svelte-ignore state_referenced_locally
    let reason = $state(payment.reason);
    let timedOut = $state(false);
    let failedAt = $state<string | null>(null);

    let timer: ReturnType<typeof setTimeout> | undefined;
    let startedAt = 0;

    async function check(): Promise<void> {
        clearTimeout(timer);

        try {
            const res = await sendJson<VerifyResponse>('POST', verify.url(payment.reference));
            reference = res.transactionReference ?? reference;

            if (res.status === 'paid' && res.ticketUrl) {
                status = 'paid';
                router.visit(res.ticketUrl, { replace: true });

                return;
            }

            if (res.status === 'failed' || res.status === 'abandoned') {
                status = res.status;
                reason = res.reason;
                failedAt = new Date().toLocaleTimeString('en-GB', { hour: '2-digit', minute: '2-digit' });

                return;
            }
        } catch {
            // Network blip: keep polling until the window closes.
        }

        if (Date.now() - startedAt < GIVE_UP_AFTER_MS) {
            timer = setTimeout(check, POLL_EVERY_MS);
        } else {
            timedOut = true;
        }
    }

    function checkAgain(): void {
        timedOut = false;
        startedAt = Date.now();
        void check();
    }

    onMount(() => {
        if (status === 'pending') {
            startedAt = Date.now();
            void check();
        }
    });

    onDestroy(() => clearTimeout(timer));

    const failed = $derived(status === 'failed' || status === 'abandoned');
</script>

<svelte:head>
    <title>{failed ? 'Payment didn’t go through' : 'Confirming payment'} · KENS</title>
</svelte:head>

<div class="flex min-h-dvh flex-col bg-bg pt-[env(safe-area-inset-top)]">
    <main class="mx-auto flex w-full max-w-[460px] flex-1 flex-col justify-center gap-6 p-6">
        {#if failed}
            <!-- CP-03 -->
            <div class="flex flex-col items-center gap-3.5 text-center" role="alert">
                <span class="flex size-20 items-center justify-center rounded-full bg-bad-bg text-bad"><CreditCardOff class="size-11" /></span>
                <h1 class="text-[22px] font-bold">Payment didn't go through</h1>
                <p class="max-w-[310px] text-base leading-normal text-mut">{reason ?? 'Monnify reported the payment failed. You have not been charged.'}</p>
            </div>
            <div class="flex items-center gap-2.5 rounded-[14px] bg-ok-bg px-4 py-3.5">
                <Inventory class="size-6 flex-none text-ok" />
                <span class="text-[15px] leading-[1.4]"><b>Your draft is safe.</b> Nothing has been submitted yet.</span>
            </div>
            <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1.5 px-1 text-sm">
                <dt class="text-mut">Reference</dt>
                <dd class="text-right font-mono break-all">{reference}</dd>
                <dt class="text-mut">Status</dt>
                <dd class="text-right font-bold text-bad">Failed{failedAt || payment.updatedAt ? ` · ${failedAt ?? payment.updatedAt}` : ''}</dd>
            </dl>
        {:else}
            <!-- CP-02 -->
            <div class="flex flex-col items-center gap-4 text-center" role="status" aria-live="polite">
                <span class="size-[72px] rounded-full border-[6px] border-sf2 border-t-mid border-r-mid motion-safe:animate-spin" aria-hidden="true"></span>
                <h1 class="text-[22px] font-bold">{timedOut ? 'Still waiting for confirmation' : 'Confirming your payment'}</h1>
                <p class="max-w-[300px] text-base leading-normal text-mut">
                    {timedOut
                        ? "Monnify hasn't confirmed this payment yet. If you completed it, check again in a moment — you won't be charged twice."
                        : 'Keep this screen open. Bank transfers can take up to 2 minutes to confirm.'}
                </p>
            </div>
            <ol class="flex flex-col gap-3.5 rounded-2xl border border-line bg-sf p-4 text-[15px]">
                <li class="flex items-center gap-2.5"><CheckCircle class="size-6 text-ok" />Monnify checkout completed</li>
                <li class="flex items-center gap-2.5 font-bold"><Pending class="size-6 text-mid" />Confirming with bank…</li>
                <li class="flex items-center gap-2.5 text-mut"><RadioButtonUnchecked class="size-6" />Generating ticket</li>
            </ol>
            <div class="flex justify-between gap-3 px-1 text-sm">
                <span class="text-mut">Reference</span>
                <span class="text-right font-mono break-all">{reference}</span>
            </div>
        {/if}
    </main>

    <div class="pb-safe mx-auto flex w-full max-w-[460px] flex-col gap-2.5 px-4 pt-3">
        {#if failed}
            <Button size="xl" block href={edit.url(inspection.uuid, { query: { step: 9 } })}><Refresh />Try again</Button>
            <Button variant="outline" block href={home.url()}>Back to my inspections</Button>
        {:else if timedOut}
            <Button size="xl" block onclick={checkAgain}><Refresh />Check again</Button>
            <Button variant="outline" block href={edit.url(inspection.uuid, { query: { step: 9 } })}>Back to review</Button>
        {/if}
    </div>
</div>
