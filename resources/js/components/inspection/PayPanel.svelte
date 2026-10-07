<script lang="ts">
    import type { Snippet } from 'svelte';
    import AccountBalance from '~icons/ms/account-balance';
    import CreditCard from '~icons/ms/credit-card';
    import Dialpad from '~icons/ms/dialpad';
    import Info from '~icons/ms/info';

    type Props = {
        fee: { amount: string; effectiveFrom: string } | null;
        /** Owner / area / Form 74 rows under the fee (CP-01). */
        details?: Snippet;
        /** Pay button area. */
        action: Snippet;
        /** "card" = desktop side panel (CK-03); "page" = phone CP-01. */
        variant?: 'card' | 'page';
    };

    let { fee, details, action, variant = 'card' }: Props = $props();

    // "₦15,000.00" → big "₦15,000" + small ".00", as in the designs.
    const whole = $derived(fee?.amount.replace(/\.\d{2}$/, '') ?? '');
    const kobo = $derived(fee?.amount.match(/\.\d{2}$/)?.[0] ?? '');

    const methods = [
        { label: 'Card', icon: CreditCard },
        { label: 'Transfer', icon: AccountBalance },
        { label: 'USSD', icon: Dialpad },
    ];
</script>

<div class="flex flex-col gap-4">
    <section class="flex flex-col gap-1.5 rounded-[20px] border border-line bg-sf p-5 lg:gap-3.5 lg:p-6" aria-label="Inspection fee">
        <span class="font-mono text-xs font-semibold tracking-[0.06em] text-mut">INSPECTION FEE</span>
        {#if fee}
            <span class="font-mono text-[40px] font-semibold tracking-[-0.02em] lg:text-[44px]">
                {whole}<span class="text-[22px] text-mut">{kobo}</span>
            </span>
            <span class="text-sm text-mut">Set by Kaduna Electric · effective {fee.effectiveFrom}</span>
        {:else}
            <span class="text-base font-bold text-bad">The fee hasn't been set yet. Contact the New Service Department.</span>
        {/if}

        {#if details}
            <div class="my-2.5 h-px bg-line"></div>
            {@render details()}
        {/if}

        {#if variant === 'card'}
            <div class="grid grid-cols-3 gap-2 text-[13px] font-semibold text-mut" aria-label="Payment methods">
                {#each methods as m (m.label)}
                    <span class="flex h-14 flex-col items-center justify-center gap-0.5 rounded-xl bg-sf2"><m.icon class="size-5" />{m.label}</span>
                {/each}
            </div>
            {@render action()}
            <span class="text-[13px] leading-[1.45] text-mut">
                Paying submits the report — it can't be edited afterwards. If payment fails, the draft is kept.
            </span>
        {/if}
    </section>

    {#if variant === 'page'}
        <div class="flex flex-col gap-2">
            <span class="px-1 text-sm font-bold text-mut">Pay securely via Monnify</span>
            <div class="grid grid-cols-3 gap-2 text-sm font-semibold" aria-label="Payment methods">
                {#each methods as m (m.label)}
                    <span class="flex h-16 flex-col items-center justify-center gap-1 rounded-xl border border-line bg-sf"><m.icon class="size-6" />{m.label}</span>
                {/each}
            </div>
        </div>
        <div class="flex items-start gap-2.5 rounded-[14px] bg-imp-bg p-3.5">
            <Info class="size-6 flex-none text-imp" />
            <span class="text-sm leading-[1.45]"><b>Paying submits the report.</b> After payment it can't be edited. If payment fails, your draft is kept.</span>
        </div>
    {/if}
</div>
