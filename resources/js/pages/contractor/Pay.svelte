<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import BottomBar from '@/components/BottomBar.svelte';
    import Button from '@/components/Button.svelte';
    import MobileHeader from '@/components/MobileHeader.svelte';
    import Toaster from '@/components/Toaster.svelte';
    import PayPanel from '@/components/inspection/PayPanel.svelte';
    import { edit } from '@/routes/inspections';
    import { store as pay } from '@/routes/inspections/pay';
    import Lock from '~icons/ms/lock';

    type Props = {
        inspection: { uuid: string; ownerName: string | null; area: string | null; form74No: string | null };
        fee: { amount: string; effectiveFrom: string } | null;
    };

    let { inspection, fee }: Props = $props();

    let paying = $state(false);

    // Monnify's hosted checkout opens as a full-page redirect (reliable on phones).
    function start(): void {
        paying = true;
        router.post(pay.url(inspection.uuid), {}, { onFinish: () => (paying = false) });
    }

    const short = $derived(fee?.amount.replace(/\.00$/, '') ?? '');
</script>

<svelte:head>
    <title>Pay &amp; submit · KENS</title>
</svelte:head>

<!-- CP-01 -->
<div class="flex min-h-dvh flex-col bg-bg">
    <MobileHeader title="Pay & submit" backHref={edit.url(inspection.uuid, { query: { step: 9 } })} />

    <div class="mx-auto flex w-full max-w-[560px] flex-1 flex-col gap-4 px-4 py-5">
        <PayPanel {fee} variant="page">
            {#snippet details()}
                <dl class="grid grid-cols-[auto_1fr] gap-x-3 gap-y-1.5 text-sm">
                    <dt class="text-mut">Owner</dt><dd class="font-bold">{inspection.ownerName}</dd>
                    <dt class="text-mut">Area</dt><dd class="font-bold">{inspection.area}</dd>
                    <dt class="text-mut">Form 74</dt><dd class="font-mono">{inspection.form74No}</dd>
                </dl>
            {/snippet}
            {#snippet action()}{/snippet}
        </PayPanel>
    </div>

    <BottomBar>
        <Button size="xl" block disabled={!fee || paying} onclick={start}>
            <Lock />{paying ? 'Opening Monnify…' : `Pay ${short} & submit`}
        </Button>
    </BottomBar>
</div>

<Toaster />
