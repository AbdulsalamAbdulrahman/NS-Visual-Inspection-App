<script lang="ts" module>
    export { default as layout } from '@/layouts/AdminLayout.svelte';
</script>

<script lang="ts">
    import { router, useForm } from '@inertiajs/svelte';
    import Button from '@/components/Button.svelte';
    import BankAccountForm from '@/components/account/BankAccountForm.svelte';
    import MobileHeader from '@/components/MobileHeader.svelte';
    import { more } from '@/routes/admin';
    import { account, exportMethod as exportCsv, ratios } from '@/routes/admin/settlements';
    import Download from '~icons/ms/download';

    type Party = {
        id: number;
        label: string;
        percentage: number;
        hasOwnAccount: boolean;
        bankName: string | null;
        accountNumber: string | null;
        accountName: string | null;
        ready: boolean;
        confirmed: boolean;
        total: string;
    };

    type Props = {
        parties: Party[];
        banks: { code: string; name: string }[];
        filters: { from: string; to: string };
        splitEnabled: boolean;
    };

    let { parties, banks, filters, splitEnabled }: Props = $props();

    // svelte-ignore state_referenced_locally
    const form = useForm({ percentages: Object.fromEntries(parties.map((p) => [p.id, p.percentage])) as Record<number, number> });
    // svelte-ignore state_referenced_locally
    let from = $state(filters.from);
    // svelte-ignore state_referenced_locally
    let to = $state(filters.to);

    const sum = $derived(parties.reduce((total, p) => total + Number(form.percentages[p.id] ?? 0), 0));
    const exportUrl = $derived(exportCsv.url({ query: { from, to } }));
    const accountOf = (p: Party) => (p.ready ? { name: p.bankName ?? '', number: p.accountNumber ?? '', accountName: p.accountName } : null);

    function applyPeriod(): void {
        router.get(window.location.pathname, { from, to }, { preserveState: true, preserveScroll: true, replace: true });
    }
</script>

<svelte:head>
    <title>Settlements · KENS</title>
</svelte:head>

<MobileHeader title="Settlements" backHref={more.url()} class="lg:hidden" />

<div class="flex max-w-[1000px] flex-col gap-5 px-4 py-3.5 lg:px-10 lg:py-8">
    <h1 class="hidden text-[28px] font-extrabold lg:block">Settlements</h1>

    <p class={['rounded-xl px-4 py-3 text-sm font-semibold', splitEnabled ? 'bg-ok-bg text-ok' : 'bg-imp-bg text-imp']}>
        {splitEnabled ? 'Automatic split is on: Monnify shares each payment.' : 'Automatic split is off: payments go to the main account, and shares are only recorded here.'}
    </p>

    <section class="flex flex-col gap-3 rounded-2xl border border-line bg-sf p-4">
        <div class="flex flex-wrap items-end gap-3">
            <label class="flex flex-col gap-1 text-sm font-semibold">From<input type="date" bind:value={from} class="h-11 rounded-xl border border-line bg-sf px-2" /></label>
            <label class="flex flex-col gap-1 text-sm font-semibold">To<input type="date" bind:value={to} class="h-11 rounded-xl border border-line bg-sf px-2" /></label>
            <Button size="sm" class="h-11" onclick={applyPeriod}>Apply</Button>
            <Button variant="outline" size="sm" href={exportUrl} external class="h-11 border-[1.5px]"><Download />Export</Button>
        </div>
    </section>

    <section class="flex flex-col overflow-hidden rounded-2xl border border-line bg-sf" aria-label="Settlement parties">
        {#each parties as party (party.id)}
            <div class="border-b border-line last:border-b-0">
                <div class="flex flex-wrap items-center gap-3 px-4 py-3">
                    <b class="min-w-0 flex-1">{party.label}</b>
                    <label class="flex items-center gap-1 text-sm">
                        <span class="sr-only">Percent for {party.label}</span>
                        <input type="number" min="0" max="100" step="0.01" bind:value={form.percentages[party.id]} class="h-11 w-20 rounded-xl border border-line bg-sf px-2 text-right font-mono" />%
                    </label>
                    <span class="w-32 text-right font-mono font-semibold tabular-nums">{party.total}</span>
                </div>
                {#if party.hasOwnAccount}
                    <BankAccountForm bank={accountOf(party)} url={account.url(party.id)} {banks} label="Settlement account · {party.label}" />
                {/if}
            </div>
        {/each}
    </section>

    <div class="flex items-center gap-3">
        <Button disabled={form.processing || Math.abs(sum - 100) > 0.001} onclick={() => form.put(ratios.url(), { preserveScroll: true })}>Save ratios</Button>
        <span class={['font-mono text-sm', Math.abs(sum - 100) > 0.001 ? 'font-semibold text-bad' : 'text-mut']}>Total {sum}%</span>
        {#if form.errors.percentages}<span class="text-sm font-semibold text-bad" role="alert">{form.errors.percentages}</span>{/if}
    </div>
</div>
