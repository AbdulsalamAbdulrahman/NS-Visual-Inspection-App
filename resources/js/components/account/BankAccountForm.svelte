<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import Button from '@/components/Button.svelte';
    import Select from '@/components/form/Select.svelte';

    type Props = {
        bank: { name: string; number: string; accountName: string } | null;
        url: string;
        banks: { code: string; name: string }[];
        label?: string;
    };

    let { bank, url, banks, label = 'Bank account' }: Props = $props();

    const form = useForm({ bank_code: null as string | null, account_number: '' });
    let editing = $state(false);

    const options = $derived(banks.map((b) => ({ value: b.code, label: b.name })));

    function save(): void {
        form.post(url, {
            preserveScroll: true,
            onSuccess: () => {
                form.reset();
                editing = false;
            },
        });
    }
</script>

<div class="flex flex-col gap-2.5 border-t border-line px-4 py-3.5">
    <span class="text-[15px] font-semibold">{label}</span>
    {#if bank && !editing}
        <p class="text-sm"><b>{bank.accountName}</b><br /><span class="font-mono">{bank.number}</span> · {bank.name}</p>
        <Button variant="outline" size="sm" class="h-11 w-fit border-[1.5px]" onclick={() => (editing = true)}>Change</Button>
    {:else if banks.length === 0}
        <p class="text-sm text-mut">The bank list isn’t available right now. Try again shortly.</p>
    {:else}
        <Select id="bank-{label}" size="md" {options} placeholder="Choose bank" bind:value={form.bank_code} />
        <input
            aria-label="Account number"
            inputmode="numeric"
            maxlength="10"
            placeholder="10-digit account number"
            bind:value={form.account_number}
            class="h-14 rounded-xl border-[1.5px] border-line bg-sf px-3 font-mono outline-none focus:border-2 focus:border-pri"
        />
        {#if form.errors.account_number || form.errors.bank_code}
            <span class="text-sm font-semibold text-bad" role="alert">{form.errors.account_number ?? form.errors.bank_code}</span>
        {/if}
        <Button size="sm" class="h-11 w-fit" disabled={form.processing || !form.bank_code || form.account_number.length !== 10} onclick={save}>Confirm account</Button>
    {/if}
</div>
