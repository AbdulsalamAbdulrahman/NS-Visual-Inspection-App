<script lang="ts" module>
    export { default as layout } from '@/layouts/ContractorLayout.svelte';
</script>

<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import Avatar from '@/components/Avatar.svelte';
    import BottomBar from '@/components/BottomBar.svelte';
    import Button from '@/components/Button.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import MobileHeader from '@/components/MobileHeader.svelte';
    import Pagination, { type PageLinks, type PageMeta } from '@/components/Pagination.svelte';
    import SearchField from '@/components/SearchField.svelte';
    import StatusPill from '@/components/StatusPill.svelte';
    import { REVIEW_TONE, type ReviewStatus } from '@/lib/inspection/report';
    import { STEP_COUNT, STEPS } from '@/lib/inspection/steps';
    import { getBootstrap, getLocalDraft, listLocalDrafts, offlineSupported, putLocalDraft, type LocalDraft } from '@/lib/offline/db';
    import { offlineSync } from '@/lib/offline/sync.svelte';
    import { onMount } from 'svelte';
    import { edit, show, store } from '@/routes/inspections';
    import { show as profile } from '@/routes/profile';
    import AddCircle from '~icons/ms/add-circle';
    import ArrowForward from '~icons/ms/arrow-forward';
    import AssignmentAdd from '~icons/ms/assignment-add';
    import ChevronRight from '~icons/ms/chevron-right';
    import CloudOff from '~icons/ms/cloud-off';
    import PhoneAndroid from '~icons/ms/phone-android';
    import EditNote from '~icons/ms/edit-note';
    import Print from '~icons/ms/print';

    type Card = {
        uuid: string;
        status: 'draft' | 'submitted';
        ticketNo: string | null;
        ownerName: string | null;
        address: string | null;
        area: string | null;
        step: number;
        stepLabel: string;
        savedLabel: string | null;
        submittedAt: string | null;
        review: ReviewStatus | null;
        reviewLabel: string | null;
        reviewNote: string | null;
        amount: string | null;
    };

    type Props = {
        /** Not paginated: a contractor has a handful of drafts at most. */
        drafts: Card[];
        /** Sent back by NSD; shown first because they block the certificate. */
        returned: Card[];
        submitted: { data: Card[]; meta: PageMeta; links: PageLinks };
        submittedTotal: number;
        filters: { search: string };
        fee: string | null;
    };

    let { drafts, returned, submitted, submittedTotal, filters, fee }: Props = $props();

    const user = $derived(page.props.auth.user!);
    let starting = $state(false);
    let online = $state(typeof navigator === 'undefined' ? true : navigator.onLine);
    let offlineNotice = $state<string | null>(null);

    // ── Offline (Phase 7): drafts kept on this phone ─────────────────────────
    let localDrafts = $state<LocalDraft[]>([]);

    async function loadLocal(): Promise<void> {
        localDrafts = offlineSupported() ? await listLocalDrafts(user.uuid) : [];
    }

    onMount(() => void loadLocal());

    // Re-read after a background sync has sent things.
    $effect(() => {
        if (!offlineSync.running) {
            void loadLocal();
        }
    });

    const serverDraftIds = $derived(new Set(drafts.map((d) => d.uuid)));
    const unsynced = $derived(new Set(localDrafts.filter((l) => l.dirty).map((l) => l.uuid)));
    /** Started on this phone and not on the server yet. */
    const phoneOnly = $derived<Card[]>(
        localDrafts
            .filter((l) => l.localOnly && !serverDraftIds.has(l.uuid))
            .map((l) => ({
                uuid: l.uuid,
                status: 'draft',
                ticketNo: null,
                ownerName: l.draft.owner_name,
                address: l.draft.property_address,
                area: null,
                step: l.step,
                stepLabel: STEPS[l.step - 1]?.short ?? '',
                savedLabel: 'on this phone',
                submittedAt: null,
                review: null,
                reviewLabel: null,
                reviewNote: null,
                amount: null,
            })),
    );
    const allDrafts = $derived([...phoneOnly, ...drafts]);

    /** Open a draft from the copy on this phone (no server round trip). */
    async function openFromPhone(uuid: string): Promise<boolean> {
        const [local, bootstrap] = await Promise.all([getLocalDraft(uuid, user.uuid), getBootstrap(user.uuid)]);

        if (!local || !bootstrap) {
            return false;
        }

        const { savedAt: _savedAt, ...formProps } = bootstrap;

        router.push({
            url: edit.url(uuid),
            component: 'contractor/InspectionForm',
            props: (current) => ({ ...current, ...formProps, inspection: local.draft, step: local.step, changesRequested: null }),
        });

        return true;
    }

    async function resume(uuid: string): Promise<void> {
        offlineNotice = null;
        const local = localDrafts.find((l) => l.uuid === uuid);

        // Only on this phone, or no network: open the phone copy straight away.
        if ((local?.localOnly || !navigator.onLine) && (await openFromPhone(uuid))) {
            return;
        }

        router.visit(edit.url(uuid), {
            // The connection failed even though the phone says it's online (weak signal).
            onNetworkError: () => {
                void openFromPhone(uuid).then((opened) => {
                    if (!opened) {
                        offlineNotice = "This draft hasn't been opened on this phone yet. Connect to open it.";
                    }
                });

                return false;
            },
        });
    }

    /** No network: a new draft with a phone-made id; the server creates it on the first sync. */
    async function startOnPhone(): Promise<void> {
        const bootstrap = await getBootstrap(user.uuid);

        if (!bootstrap) {
            offlineNotice = 'Open any inspection once while online; after that this phone can start new ones without network.';

            return;
        }

        const uuid = crypto.randomUUID();
        const today = new Date().toLocaleDateString('en-GB', { day: '2-digit', month: 'short', year: 'numeric' });

        await putLocalDraft({
            uuid,
            userId: user.uuid,
            draft: { ...bootstrap.blankDraft, uuid, status: 'draft', current_step: 1, inspection_date: today, updated_at: null, circuits: [], attachments: [] },
            step: 1,
            updatedAt: Date.now(),
            dirty: true,
            localOnly: true,
        });
        await openFromPhone(uuid);
    }

    const empty = $derived(allDrafts.length === 0 && submittedTotal === 0);
    const where = (c: Card): string => [c.address, c.area].filter(Boolean).join(' · ') || 'No address yet';

    function start(): void {
        starting = true;
        offlineNotice = null;

        if (!navigator.onLine) {
            void startOnPhone().finally(() => (starting = false));

            return;
        }

        router.post(
            store.url(),
            { uuid: crypto.randomUUID() },
            {
                onFinish: () => (starting = false),
                onNetworkError: () => {
                    void startOnPhone();

                    return false;
                },
            },
        );
    }

    const steps = $derived([
        'Fill sections A–D on site',
        fee ? `Pay the ${fee} inspection fee` : 'Pay the inspection fee',
        'NSD reviews the report',
        'Print the certificate',
    ]);
    const cols = 'grid-cols-[190px_minmax(0,1.2fr)_minmax(0,1.5fr)_120px_120px_150px_40px]';
</script>

<svelte:window ononline={() => (online = true)} onoffline={() => (online = false)} />

<svelte:head>
    <title>My inspections · KENS</title>
</svelte:head>

<MobileHeader title="My inspections" subtitle={user.badge ? `${user.name} · ${user.badge}` : user.name} class="lg:hidden">
    {#snippet trailing()}
        <Link href={profile()} aria-label="Profile" class="rounded-full">
            <Avatar initials={user.initials} />
        </Link>
    {/snippet}
    {#if !empty}
        <SearchField tone="filled" value={filters.search} placeholder="Ticket, owner or Form 74" only={['submitted', 'filters']} />
    {/if}
</MobileHeader>

<div class="mx-auto flex w-full max-w-[1344px] flex-1 flex-col gap-3 px-4 py-4 lg:gap-7 lg:px-12 lg:py-8">
    <div class="hidden items-end gap-4 lg:flex">
        <div class="flex flex-col gap-1">
            <h1 class="text-[32px] font-extrabold tracking-[-0.02em]">My inspections</h1>
            <span class="text-[15px] text-mut">
                {empty ? 'No inspections yet' : `${submittedTotal} submitted · ${allDrafts.length} draft${allDrafts.length === 1 ? '' : 's'}`}
            </span>
        </div>
        <Button size="md" class="ml-auto font-extrabold" onclick={start} disabled={starting}>
            <AddCircle />Start new inspection
        </Button>
    </div>

    {#if !online || offlineSync.queued.length > 0 || offlineSync.needsSignIn || unsynced.size > 0 || offlineNotice}
        <div class="flex flex-col gap-1.5 rounded-[14px] bg-info-bg px-4 py-3 text-sm" role="status">
            {#if !online}
                <span class="flex items-start gap-2"><CloudOff class="size-5 flex-none text-info" /><span><b>No network.</b> Drafts are kept on this phone and sync when you're back online. Paying needs network.</span></span>
            {:else if offlineSync.needsSignIn}
                <span class="flex items-start gap-2"><PhoneAndroid class="size-5 flex-none text-bad" /><span><b>Sign in again to sync</b> the work saved on this phone.</span></span>
            {:else if unsynced.size > 0 || offlineSync.queued.length > 0}
                <span class="flex items-start gap-2"><PhoneAndroid class="size-5 flex-none text-info" /><span>Syncing work saved on this phone…</span></span>
            {/if}
            {#if offlineSync.queued.length > 0}
                <span class="pl-7 text-mut">{offlineSync.queued.length} file{offlineSync.queued.length === 1 ? '' : 's'} waiting to upload.</span>
            {/if}
            {#if offlineNotice}
                <span class="pl-7 font-semibold text-ink">{offlineNotice}</span>
            {/if}
        </div>
    {/if}

    {#if empty}
        <EmptyState
            icon={AssignmentAdd}
            title="No inspections yet"
            body="Start your first inspection at the property. Your progress saves as you go — even without network."
        >
            <ol class="mt-2 flex w-full max-w-[300px] flex-col gap-2 text-left">
                {#each steps as text, i (i)}
                    <li class="flex items-center gap-2.5 text-[15px]">
                        <span class="flex size-7 flex-none items-center justify-center rounded-full bg-sf2 font-mono text-[13px] font-semibold">{i + 1}</span>
                        {text}
                    </li>
                {/each}
            </ol>
        </EmptyState>
    {:else}
        {#if returned.length > 0}
            <section class="flex flex-col gap-3" aria-labelledby="returned-heading">
                <h2 id="returned-heading" class="eyebrow px-1 text-imp">Needs changes · {returned.length}</h2>
                <ul class="grid gap-3 lg:grid-cols-2 lg:gap-4">
                    {#each returned as r (r.uuid)}
                        <li class="flex flex-col gap-2.5 rounded-2xl border-[1.5px] border-imp bg-sf p-3.5 lg:p-[18px]">
                            <div class="flex justify-between gap-2">
                                <div class="flex min-w-0 flex-col gap-0.5">
                                    <span class="font-mono text-sm font-medium text-brand">{r.ticketNo}</span>
                                    <b class="truncate text-base lg:text-[17px]">{r.ownerName}</b>
                                </div>
                                <StatusPill tone="imp" label="Changes requested" class="self-start" />
                            </div>
                            {#if r.reviewNote}
                                <p class="line-clamp-3 border-l-[3px] border-imp pl-3 text-sm whitespace-pre-line">{r.reviewNote}</p>
                            {/if}
                            <div class="flex items-center justify-between gap-2">
                                <span class="text-sm text-mut">No extra payment needed</span>
                                <Button href={edit.url(r.uuid)} size="sm" class="lg:h-10 lg:rounded-[10px] lg:text-sm"><EditNote />Fix and resubmit</Button>
                            </div>
                        </li>
                    {/each}
                </ul>
            </section>
        {/if}

        {#if allDrafts.length > 0}
            <section class="flex flex-col gap-3" aria-labelledby="drafts-heading">
                <h2 id="drafts-heading" class="eyebrow px-1">Drafts<span class="lg:hidden"> · {allDrafts.length}</span></h2>
                <ul class="grid gap-3 lg:grid-cols-3 lg:gap-4">
                    {#each allDrafts as d, i (d.uuid)}
                        <li class="flex flex-col gap-2.5 rounded-2xl border border-line bg-sf p-3.5 lg:gap-3 lg:p-[18px]">
                            <div class="flex justify-between gap-2">
                                <div class="flex min-w-0 flex-col gap-0.5">
                                    <b class="truncate text-base lg:text-[17px]">{d.ownerName || 'Not yet named'}</b>
                                    <span class="truncate text-sm text-mut">{where(d)}</span>
                                </div>
                                <span class="font-mono text-[13px] font-medium text-mut">{d.step}/{STEP_COUNT}</span>
                            </div>
                            <div class="h-1.5 rounded-full bg-sf2" aria-hidden="true">
                                <div class="h-full rounded-full bg-mid" style:width="{Math.round((d.step / STEP_COUNT) * 100)}%"></div>
                            </div>
                            <div class="flex items-center justify-between gap-2">
                                <span class="flex min-w-0 items-center gap-1.5 text-sm text-mut">
                                    {#if unsynced.has(d.uuid)}<PhoneAndroid class="size-4 flex-none text-info" aria-label="Not synced yet" />{/if}
                                    <span class="truncate">{d.stepLabel}{d.savedLabel ? ` · ${d.savedLabel}` : ''}</span>
                                </span>
                                <Button size="sm" variant={i === 0 ? 'primary' : 'soft'} class="lg:h-10 lg:rounded-[10px] lg:text-sm" onclick={() => resume(d.uuid)}>
                                    Resume{#if i === 0}<ArrowForward />{/if}
                                </Button>
                            </div>
                        </li>
                    {/each}
                </ul>
            </section>
        {/if}

        <section class="flex flex-col gap-3 lg:flex-1" aria-labelledby="submitted-heading">
            <div class="flex items-center gap-3 px-1 pt-2 lg:p-0">
                <h2 id="submitted-heading" class="eyebrow">Submitted<span class="lg:hidden"> · {submittedTotal}</span></h2>
                <SearchField class="ml-auto hidden w-[360px] lg:flex" value={filters.search} placeholder="Ticket, owner or Form 74" only={['submitted', 'filters']} />
            </div>

            {#if submitted.data.length === 0}
                <p class="rounded-2xl border border-line bg-sf px-4 py-5 text-sm text-mut">
                    {filters.search ? `No submitted inspections match “${filters.search}”.` : 'Paid inspections and their tickets appear here.'}
                </p>
            {:else}
                <!-- Phone list -->
                <ul class="flex flex-col rounded-2xl border border-line bg-sf lg:hidden">
                    {#each submitted.data as s (s.uuid)}
                        <li class="border-b border-line last:border-b-0">
                            <Link href={show.url(s.uuid)} class="flex items-center gap-2.5 py-3 pr-2 pl-3.5 text-ink no-underline">
                                <span class="flex min-w-0 flex-1 flex-col gap-[3px]">
                                    <span class="flex items-center gap-2">
                                        <span class="font-mono text-sm font-medium text-brand">{s.ticketNo}</span>
                                        {#if s.review}<StatusPill tone={REVIEW_TONE[s.review]} label={s.reviewLabel ?? ''} />{/if}
                                    </span>
                                    <b class="truncate text-[15px]">{s.ownerName}</b>
                                    <span class="text-[13px] text-mut">{s.area} · {s.submittedAt}</span>
                                </span>
                                <ChevronRight class="size-6 text-mut" />
                            </Link>
                        </li>
                    {/each}
                </ul>

                <!-- Desktop table (CK-01) -->
                <div class="hidden overflow-hidden rounded-2xl border border-line bg-sf lg:block" role="table" aria-label="Submitted inspections">
                    <div role="row" class="grid {cols} gap-3 border-b border-line bg-sf2 px-5 py-3 text-xs font-bold tracking-[0.06em] text-mut">
                        <span role="columnheader">TICKET</span>
                        <span role="columnheader">OWNER</span>
                        <span role="columnheader">ADDRESS</span>
                        <span role="columnheader">AREA</span>
                        <span role="columnheader">DATE</span>
                        <span role="columnheader">STATUS</span>
                        <span role="columnheader"><span class="sr-only">Print</span></span>
                    </div>
                    {#each submitted.data as s (s.uuid)}
                        <Link href={show.url(s.uuid)} role="row" class="grid h-[52px] items-center gap-3 border-b border-line px-5 text-sm text-ink no-underline last:border-b-0 hover:bg-sf2 {cols}">
                            <span role="cell" class="font-mono text-sm font-medium text-brand">{s.ticketNo}</span>
                            <b role="cell" class="truncate">{s.ownerName}</b>
                            <span role="cell" class="truncate text-mut">{s.address}</span>
                            <span role="cell">{s.area}</span>
                            <span role="cell" class="font-mono">{s.submittedAt}</span>
                            <span role="cell">{#if s.review}<StatusPill tone={REVIEW_TONE[s.review]} label={s.reviewLabel ?? ''} />{/if}</span>
                            <span role="cell" class="flex justify-end text-mut"><Print class="size-[22px]" aria-hidden="true" /></span>
                        </Link>
                    {/each}
                </div>

                <Pagination meta={submitted.meta} links={submitted.links} noun="submitted" />
            {/if}
        </section>
    {/if}
</div>

<BottomBar class="lg:hidden">
    <Button size="xl" block onclick={start} disabled={starting}><AddCircle />Start new inspection</Button>
</BottomBar>
