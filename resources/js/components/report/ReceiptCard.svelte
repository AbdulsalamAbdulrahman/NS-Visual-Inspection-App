<script lang="ts" module>
    export type ReceiptLinks = { printUrl: string; sendUrl: string; ownerEmail: string | null };
</script>

<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import Button from '@/components/Button.svelte';
    import ForwardToInbox from '~icons/ms/forward-to-inbox';
    import ReceiptLong from '~icons/ms/receipt-long';

    let { receipt }: { receipt: ReceiptLinks } = $props();

    let sending = $state(false);

    /** Emails the receipt to the owner again (it also went when the fee was paid). */
    function send(): void {
        router.post(receipt.sendUrl, {}, { preserveScroll: true, onStart: () => (sending = true), onFinish: () => (sending = false) });
    }
</script>

<section class="flex flex-col gap-3 rounded-2xl border border-line bg-sf p-4 sm:flex-row sm:items-center" aria-labelledby="receipt-heading">
    <div class="flex min-w-0 flex-1 items-center gap-3">
        <ReceiptLong class="size-6 flex-none text-mut" />
        <div class="flex min-w-0 flex-col">
            <b id="receipt-heading" class="text-base">Payment receipt</b>
            <span class="truncate text-[13px] text-mut">
                {receipt.ownerEmail ? `Emailed to ${receipt.ownerEmail} when paid` : 'No owner email on this job'}
            </span>
        </div>
    </div>
    <div class="grid grid-cols-2 gap-2.5 sm:flex">
        <Button variant="outline" size="sm" class="h-11 border-[1.5px]" href={receipt.printUrl}><ReceiptLong />Print receipt</Button>
        <Button size="sm" class="h-11" disabled={!receipt.ownerEmail || sending} onclick={send}><ForwardToInbox />{sending ? 'Sending…' : 'Send to customer'}</Button>
    </div>
</section>
