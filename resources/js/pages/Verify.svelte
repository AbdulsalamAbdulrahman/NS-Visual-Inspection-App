<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import Logo from '@/components/Logo.svelte';
    import { APP_TITLE } from '@/lib/brand';
    import type { ReviewStatus } from '@/lib/inspection/report';
    import { verify } from '@/routes';
    import CheckCircle from '~icons/ms/check-circle';
    import Error from '~icons/ms/error';
    import HourglassTop from '~icons/ms/hourglass-top';
    import Search from '~icons/ms/search';
    import Warning from '~icons/ms/warning';

    type Props = {
        ticket: string;
        result: {
            status: ReviewStatus;
            submittedAt: string | null;
            approvedAt: string | null;
            area: string | null;
            contractor: string | null;
            owner: string | null;
        } | null;
    };

    let { ticket, result }: Props = $props();

    // svelte-ignore state_referenced_locally
    let lookup = $state(ticket);

    const states = {
        approved: {
            icon: CheckCircle,
            title: 'Valid certificate',
            body: 'This installation\'s inspection report was reviewed and approved by the Kaduna Electric New Service Department.',
            tone: 'border-ok bg-ok-bg text-ok',
        },
        pending: {
            icon: HourglassTop,
            title: 'Report submitted, not yet certified',
            body: 'The inspection report is genuine and paid, and is waiting for NSD review. No certificate has been issued yet.',
            tone: 'border-info bg-info-bg text-info',
        },
        changes_requested: {
            icon: Warning,
            title: 'Not certified: changes requested',
            body: 'NSD reviewed this report and asked the contractor for changes. No certificate has been issued yet.',
            tone: 'border-imp bg-imp-bg text-imp',
        },
    } as const;

    const outcome = $derived(result ? states[result.status] : null);

    function search(event: SubmitEvent): void {
        event.preventDefault();
        const value = lookup.trim().toUpperCase();

        if (value) {
            router.visit(verify.url(value));
        }
    }
</script>

<svelte:head>
    <title>Verify {ticket}</title>
</svelte:head>

<main class="flex min-h-dvh flex-col items-center bg-bg px-4 py-8 text-ink">
    <div class="flex w-full max-w-lg flex-col gap-5">
        <header class="flex items-center gap-3">
            <Logo size={48} />
            <div class="flex flex-col">
                <b class="text-base">Kaduna Electric · New Service Department</b>
                <span class="text-[13px] text-mut">{APP_TITLE}</span>
            </div>
        </header>

        <h1 class="text-[26px] font-extrabold tracking-[-0.01em]">Certificate check</h1>

        <section class="flex flex-col gap-4 rounded-2xl border border-line bg-sf p-5" aria-live="polite">
            <div class="flex flex-col gap-0.5">
                <span class="text-xs font-bold tracking-[0.08em] text-mut">CERTIFICATE / TICKET NO.</span>
                <span class="font-mono text-lg font-semibold break-all text-brand">{ticket}</span>
            </div>

            {#if result && outcome}
                {@const Icon = outcome.icon}
                <div class={['flex gap-3 rounded-xl border px-4 py-3.5', outcome.tone]}>
                    <Icon class="size-7 flex-none" aria-hidden="true" />
                    <div class="flex flex-col gap-1">
                        <b class="text-base">{outcome.title}</b>
                        <span class="text-sm text-ink">{outcome.body}</span>
                    </div>
                </div>
                <dl class="grid grid-cols-[auto_1fr] gap-x-4 gap-y-2 text-[15px]">
                    {#if result.approvedAt}
                        <dt class="text-mut">Approved</dt><dd class="font-mono">{result.approvedAt}</dd>
                    {/if}
                    <dt class="text-mut">Submitted</dt><dd class="font-mono">{result.submittedAt ?? '—'}</dd>
                    <dt class="text-mut">Service area</dt><dd>{result.area ?? '—'}</dd>
                    <dt class="text-mut">Contractor</dt><dd>{result.contractor ?? '—'}</dd>
                    <dt class="text-mut">Owner</dt><dd>{result.owner ?? '—'}</dd>
                </dl>
                <p class="text-[13px] text-mut">Compare these details with the printed certificate. If they don't match, contact the New Service Department.</p>
            {:else}
                <div class="flex gap-3 rounded-xl border border-bad bg-bad-bg px-4 py-3.5 text-bad">
                    <Error class="size-7 flex-none" aria-hidden="true" />
                    <div class="flex flex-col gap-1">
                        <b class="text-base">No record found</b>
                        <span class="text-sm text-ink">There is no submitted inspection with this number. Check the number on the certificate and try again.</span>
                    </div>
                </div>
            {/if}
        </section>

        <form class="flex gap-2" onsubmit={search}>
            <label class="sr-only" for="lookup">Certificate number</label>
            <input
                id="lookup"
                bind:value={lookup}
                class="h-12 min-w-0 flex-1 rounded-xl border-[1.5px] border-line bg-sf px-3.5 font-mono text-[15px] uppercase outline-none focus:border-pri"
                placeholder="KE-NSD-2026-000123"
                autocomplete="off"
                autocapitalize="characters"
                spellcheck="false"
            />
            <button type="submit" class="flex h-12 items-center gap-1.5 rounded-xl bg-pri px-4 font-bold text-on-pri">
                <Search class="size-5" />Check
            </button>
        </form>
    </div>
</main>
