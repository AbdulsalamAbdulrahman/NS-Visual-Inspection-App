<script lang="ts" module>
    export { default as layout } from '@/layouts/AdminLayout.svelte';
</script>

<script lang="ts">
    import { Link, router, useForm } from '@inertiajs/svelte';
    import Button from '@/components/Button.svelte';
    import Field from '@/components/form/Field.svelte';
    import { fieldBox } from '@/components/form/inputClasses';
    import StatusPill from '@/components/StatusPill.svelte';
    import admin from '@/routes/admin';
    import { destroy, store } from '@/routes/admin/fees';
    import ArrowBack from '~icons/ms/arrow-back';
    import Schedule from '~icons/ms/schedule';

    type Fee = {
        id: number;
        amountKobo: number;
        amount: string;
        effectiveFrom: string;
        changedOn: string | null;
        changedBy: string;
        reason: string | null;
        isScheduled: boolean;
    };

    type Props = {
        current: Fee | null;
        scheduled: Fee | null;
        history: Fee[];
    };

    let { current, scheduled, history }: Props = $props();

    const today = new Date().toLocaleDateString('en-CA'); // YYYY-MM-DD in local time

    const form = useForm({ amount: '', effective_from: today, reason: '' });

    const isToday = $derived(form.effective_from === today);

    function submit(event: SubmitEvent): void {
        event.preventDefault();
        form.post(store.url(), { preserveScroll: true, onSuccess: () => form.reset() });
    }

    function cancelScheduled(fee: Fee): void {
        router.delete(destroy.url(fee.id), { preserveScroll: true });
    }

    const cols = 'grid-cols-[120px_120px_120px_minmax(0,1fr)_minmax(0,1.3fr)]';
</script>

<svelte:head>
    <title>Inspection fee · KENS</title>
</svelte:head>

<header class="sticky top-0 z-20 flex items-center gap-1 border-b border-line bg-sf px-2 pt-[calc(4px+env(safe-area-inset-top))] pb-1 lg:hidden">
    <Link href={admin.more()} class="flex size-12 items-center justify-center rounded-xl text-ink" aria-label="Back to More">
        <ArrowBack class="size-[22px]" />
    </Link>
    <h1 class="flex-1 text-[18px] font-bold">Inspection fee</h1>
</header>

<div class="flex flex-col gap-5 px-4 py-4 lg:px-10 lg:py-8">
    <h1 class="hidden text-[28px] font-extrabold lg:block">Inspection fee</h1>

    <div class="grid max-w-[1040px] gap-5 lg:grid-cols-2">
        <!-- Current fee with the ticket-band motif -->
        <section class="relative flex flex-col gap-2 overflow-hidden rounded-[20px] bg-hero p-[26px] text-white" aria-label="Current fee">
            <div class="absolute -right-[90px] bottom-[34px] h-[26px] w-[300px] -rotate-[26deg] bg-lime" aria-hidden="true"></div>
            <span class="mono-caps relative tracking-[0.08em] opacity-85">Current fee</span>
            {#if current}
                <span class="relative font-mono text-[40px] font-semibold tracking-[-0.02em] lg:text-[48px]">{current.amount}</span>
                <span class="relative text-sm opacity-90">In effect since {current.effectiveFrom} · set by {current.changedBy}</span>
            {:else}
                <span class="relative text-[22px] font-bold">No fee set</span>
                <span class="relative text-sm opacity-90">Contractors can't pay until a fee is in effect.</span>
            {/if}

            {#if scheduled}
                <div class="relative mt-2.5 flex flex-wrap items-center gap-2.5 rounded-xl bg-white/12 px-3.5 py-3 text-sm">
                    <Schedule class="size-5" />
                    <span>
                        Scheduled: <b class="font-mono">{scheduled.amount}</b> from <b class="font-mono">{scheduled.effectiveFrom}</b>
                    </span>
                    <button type="button" class="ml-auto font-bold underline" onclick={() => cancelScheduled(scheduled)}>Cancel</button>
                </div>
            {/if}
        </section>

        <!-- Set a new fee -->
        <form class="flex flex-col gap-3.5 rounded-[20px] border border-line bg-sf p-5 lg:p-6" onsubmit={submit} novalidate>
            <h2 class="text-[17px] font-bold">Set a new fee</h2>
            <div class="grid gap-3 sm:grid-cols-2">
                <Field id="fee-amount" label="Amount" error={form.errors.amount}>
                    <div class={fieldBox('md', !!form.errors.amount, 'h-12 gap-1.5 rounded-[10px] pl-3')}>
                        <span class="font-mono text-[17px] text-mut" aria-hidden="true">₦</span>
                        <input
                            id="fee-amount"
                            bind:value={form.amount}
                            inputmode="decimal"
                            placeholder="17,500"
                            autocomplete="off"
                            aria-invalid={!!form.errors.amount || undefined}
                            class="h-full min-w-0 flex-1 bg-transparent pr-3 font-mono text-[17px] font-medium outline-none focus-visible:outline-none"
                        />
                    </div>
                </Field>
                <Field id="fee-date" label="Effective date" error={form.errors.effective_from}>
                    <input
                        id="fee-date"
                        type="date"
                        min={today}
                        bind:value={form.effective_from}
                        aria-invalid={!!form.errors.effective_from || undefined}
                        class={fieldBox('md', !!form.errors.effective_from, 'h-12 rounded-[10px] px-3 font-mono text-[15px] font-medium outline-none')}
                    />
                </Field>
            </div>
            <Field id="fee-reason" error={form.errors.reason}>
                {#snippet labelAside()}
                    <label for="fee-reason" class="text-sm font-semibold">Reason <span class="font-normal text-mut">optional</span></label>
                {/snippet}
                <input
                    id="fee-reason"
                    bind:value={form.reason}
                    maxlength="255"
                    placeholder="e.g. 2027 tariff review — NSD memo 14/2026"
                    class={fieldBox('md', false, 'h-12 rounded-[10px] px-3 text-[15px] outline-none')}
                />
            </Field>
            <p class="text-[13px] leading-[1.45] text-mut">
                Contractors pay the fee in effect at the moment they pay. Drafts show the new amount from the effective date.
            </p>
            <Button type="submit" size="md" block class="h-12 rounded-xl" disabled={form.processing || !form.amount.trim()}>
                {isToday ? 'Change fee from today' : 'Schedule fee change'}
            </Button>
        </form>
    </div>

    <section class="flex max-w-[1040px] flex-col gap-2.5" aria-labelledby="fee-history">
        <h2 id="fee-history" class="eyebrow">History</h2>
        <div class="overflow-hidden rounded-2xl border border-line bg-sf" role="table" aria-label="Fee history">
            <div role="row" class="hidden gap-3 border-b border-line bg-sf2 px-5 py-3 text-xs font-bold tracking-[0.06em] text-mut lg:grid {cols}">
                <span role="columnheader">FEE</span>
                <span role="columnheader">EFFECTIVE</span>
                <span role="columnheader">CHANGED ON</span>
                <span role="columnheader">CHANGED BY</span>
                <span role="columnheader">REASON</span>
            </div>
            {#each history as fee (fee.id)}
                <div role="row" class="flex flex-col gap-1 border-b border-line px-4 py-3 text-sm last:border-b-0 lg:grid lg:h-[50px] lg:items-center lg:gap-3 lg:px-5 lg:py-0 {cols}">
                    <span role="cell" class="flex items-center gap-2 font-mono font-semibold">
                        {fee.amount}
                        {#if fee.isScheduled}<StatusPill tone="info" label="Scheduled" class="font-sans lg:hidden" />{/if}
                    </span>
                    <span role="cell" class="font-mono"><span class="text-mut lg:hidden">From </span>{fee.effectiveFrom}</span>
                    <span role="cell" class="hidden font-mono lg:block">{fee.changedOn ?? '—'}</span>
                    <span role="cell" class="text-mut lg:text-ink"><span class="lg:hidden">Set by </span>{fee.changedBy}</span>
                    <span role="cell" class="truncate text-mut">
                        {#if fee.isScheduled}<StatusPill tone="info" label="Scheduled" class="mr-1.5 hidden lg:inline-flex" />{/if}
                        {fee.reason ?? ''}
                    </span>
                </div>
            {:else}
                <p class="px-5 py-4 text-sm text-mut">No fee changes yet.</p>
            {/each}
        </div>
    </section>
</div>
