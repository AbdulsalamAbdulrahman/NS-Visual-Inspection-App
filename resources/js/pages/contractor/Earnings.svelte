<script lang="ts" module>
    export { default as layout } from '@/layouts/ContractorLayout.svelte';
</script>

<script lang="ts">
    type Props = {
        total: string;
        rows: { id: number; ticketNo: string | null; owner: string | null; paidAt: string | null; amount: string }[];
    };

    let { total, rows }: Props = $props();
</script>

<svelte:head>
    <title>Earnings · KENS</title>
</svelte:head>

<div class="mx-auto flex w-full max-w-3xl flex-col gap-4 px-4 py-5">
    <h1 class="text-[26px] font-extrabold">Earnings</h1>
    <div class="rounded-2xl border border-line bg-sf px-4 py-3.5">
        <p class="text-[13px] font-semibold text-mut">Your share of paid inspection fees</p>
        <p class="font-mono text-[26px] font-semibold tabular-nums">{total}</p>
        <p class="text-[13px] text-mut">Monnify pays this to your bank account on its normal settlement schedule.</p>
    </div>
    {#if rows.length === 0}
        <p class="text-[15px] text-mut">No earnings yet.</p>
    {:else}
        <ul class="overflow-hidden rounded-2xl border border-line bg-sf">
            {#each rows as row (row.id)}
                <li class="flex items-center justify-between gap-3 border-b border-line px-4 py-3 last:border-b-0">
                    <span class="flex min-w-0 flex-col">
                        <span class="font-mono text-sm font-semibold">{row.ticketNo ?? '—'}</span>
                        <span class="truncate text-[13px] text-mut">{row.owner} · {row.paidAt}</span>
                    </span>
                    <span class="font-mono font-semibold tabular-nums">{row.amount}</span>
                </li>
            {/each}
        </ul>
    {/if}
</div>
