<script lang="ts" module>
    export { default as layout } from '@/layouts/RepLayout.svelte';
</script>

<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import Avatar from '@/components/Avatar.svelte';
    import Button from '@/components/Button.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import MobileHeader from '@/components/MobileHeader.svelte';
    import Pagination, { type PageLinks, type PageMeta } from '@/components/Pagination.svelte';
    import SearchField from '@/components/SearchField.svelte';
    import StatusPill from '@/components/StatusPill.svelte';
    import UserMenu from '@/components/UserMenu.svelte';
    import { PROGRESS_TONE, type Job, type JobProgress } from '@/lib/inspection/job';
    import { REVIEW_TONE } from '@/lib/inspection/report';
    import { show as showInspection } from '@/routes/rep/inspections';
    import { create, edit, importMethod as importPage, index } from '@/routes/rep/jobs';
    import AddCircle from '~icons/ms/add-circle';
    import AssignmentInd from '~icons/ms/assignment-ind';
    import ChevronRight from '~icons/ms/chevron-right';
    import Edit from '~icons/ms/edit';
    import UploadFile from '~icons/ms/upload-file';

    type Props = {
        jobs: { data: Job[]; meta: PageMeta; links: PageLinks };
        counts: Record<'all' | JobProgress, number>;
        filters: { progress: JobProgress | null; search: string };
    };

    let { jobs, counts, filters }: Props = $props();

    const user = $derived(page.props.auth.user!);
    const filtered = $derived(!!filters.progress || !!filters.search);

    const tabs: { key: JobProgress | null; label: string }[] = [
        { key: null, label: 'All' },
        { key: 'not_started', label: 'Not started' },
        { key: 'in_progress', label: 'In progress' },
        { key: 'submitted', label: 'Submitted' },
    ];

    function pick(key: JobProgress | null): void {
        router.get(index.url({ query: { progress: key ?? undefined, search: filters.search || undefined } }), {}, { preserveState: true, preserveScroll: true, replace: true, only: ['jobs', 'filters'] });
    }

    /** Not started: the rep can still change it. Submitted: open the report. */
    function hrefFor(job: Job): string | null {
        if (job.progress === 'not_started') {
            return edit.url(job.uuid);
        }

        return job.progress === 'submitted' ? showInspection.url(job.uuid) : null;
    }

    const chip = 'flex h-10 flex-none items-center gap-1.5 rounded-full px-3.5 text-sm';
    const cols = 'grid-cols-[minmax(0,1.1fr)_minmax(0,1.4fr)_100px_minmax(0,1fr)_minmax(0,1.1fr)_100px_170px_40px]';
</script>

<svelte:head>
    <title>Jobs · KENS</title>
</svelte:head>

{#snippet chips()}
    <div class="flex gap-2 overflow-x-auto [scrollbar-width:none]" role="group" aria-label="Filter by progress">
        {#each tabs as tab (tab.key ?? 'all')}
            {@const active = filters.progress === tab.key}
            <button type="button" aria-pressed={active} class={[chip, active ? 'bg-pri font-bold text-on-pri' : 'border border-line bg-sf font-semibold']} onclick={() => pick(tab.key)}>
                {tab.label}<span class={['font-mono text-xs font-medium', active ? 'opacity-80' : 'text-mut']}>{counts[tab.key ?? 'all']}</span>
            </button>
        {/each}
    </div>
{/snippet}

{#snippet progress(job: Job)}
    {#if job.progress === 'submitted' && job.review}
        <StatusPill tone={REVIEW_TONE[job.review]} label={job.reviewLabel ?? ''} />
    {:else}
        <StatusPill tone={PROGRESS_TONE[job.progress]} label={job.progressLabel} />
    {/if}
{/snippet}

<!-- Phone header -->
<MobileHeader title="Jobs" subtitle={`${counts.not_started} not started · ${counts.in_progress} in progress`} class="md:hidden">
    {#snippet trailing()}
        <UserMenu {user} triggerClass="rounded-full">
            {#snippet trigger()}<Avatar initials={user.initials} />{/snippet}
        </UserMenu>
    {/snippet}
    {#if counts.all > 0}
        <SearchField tone="filled" value={filters.search} placeholder="Owner, ticket or Form 74" only={['jobs', 'filters']} />
        {@render chips()}
    {/if}
</MobileHeader>

<div class="flex flex-col gap-[18px] px-4 py-3 md:px-6 md:py-5 lg:px-12 lg:py-8">
    <div class="flex flex-wrap items-end gap-3">
        <div class="hidden flex-col gap-0.5 md:flex">
            <h1 class="text-[20px] font-bold lg:text-[28px] lg:font-extrabold">Jobs</h1>
            <span class="text-sm text-mut">Customers you assigned to contractors · {counts.not_started} not started</span>
        </div>
        <div class="flex w-full gap-2 md:ml-auto md:w-auto">
            <Button href={importPage.url()} variant="outline" size="md" class="flex-1 md:flex-none"><UploadFile />Upload CSV</Button>
            <Button href={create.url()} size="md" class="flex-1 md:flex-none"><AddCircle />New job</Button>
        </div>
    </div>

    {#if counts.all > 0}
        <div class="hidden flex-wrap items-center gap-2 md:flex">
            {@render chips()}
            <SearchField class="ml-auto w-[320px]" value={filters.search} placeholder="Owner, ticket or Form 74" only={['jobs', 'filters']} />
        </div>
    {/if}

    {#if jobs.data.length === 0}
        <EmptyState
            icon={AssignmentInd}
            tone="muted"
            title={filtered ? 'No jobs match' : 'No jobs yet'}
            body={filtered
                ? 'Try another filter or clear the search.'
                : 'Add a customer and assign a contractor in the area’s state. For many customers at once, upload the CSV template.'}
        />
    {:else}
        <!-- Desktop table -->
        <div class="hidden overflow-hidden rounded-2xl border border-line bg-sf lg:block" role="table" aria-label="Jobs">
            <div role="row" class="grid {cols} gap-3 border-b border-line bg-sf2 px-5 py-3 text-xs font-bold tracking-[0.06em] text-mut">
                <span role="columnheader">OWNER</span>
                <span role="columnheader">ADDRESS</span>
                <span role="columnheader">AREA</span>
                <span role="columnheader">BUILDING</span>
                <span role="columnheader">CONTRACTOR</span>
                <span role="columnheader" aria-sort="descending">ASSIGNED ↓</span>
                <span role="columnheader">PROGRESS</span>
                <span role="columnheader"><span class="sr-only">Open</span></span>
            </div>
            {#each jobs.data as job (job.uuid)}
                {@const href = hrefFor(job)}
                {#snippet cells()}
                    <span role="cell" class="flex min-w-0 flex-col">
                        <b class="truncate">{job.ownerName}</b>
                        <span class="truncate font-mono text-xs text-mut">{job.ticketNo ?? job.form74}</span>
                    </span>
                    <span role="cell" class="truncate text-mut">{job.address}</span>
                    <span role="cell">{job.area}</span>
                    <span role="cell" class="truncate">{job.building}{job.structures > 1 ? ` × ${job.structures}` : ''}</span>
                    <span role="cell" class="flex min-w-0 flex-col">
                        <span class="truncate">{job.contractor}</span>
                        {#if job.contractorLicenceExpired && job.progress === 'not_started'}<span class="text-xs font-bold text-bad">Licence expired · reassign</span>{/if}
                    </span>
                    <span role="cell" class="font-mono">{job.assignedAt}</span>
                    <span role="cell">{@render progress(job)}</span>
                    <span role="cell" class="flex justify-end text-mut">
                        {#if job.progress === 'not_started'}<Edit class="size-5" aria-label="Edit or reassign" />{:else if href}<ChevronRight class="size-6" aria-hidden="true" />{/if}
                    </span>
                {/snippet}
                {#if href}
                    <Link {href} role="row" class="grid h-[58px] items-center gap-3 border-b border-line px-5 text-sm text-ink no-underline last:border-b-0 hover:bg-sf2 {cols}">
                        {@render cells()}
                    </Link>
                {:else}
                    <div role="row" class="grid h-[58px] items-center gap-3 border-b border-line px-5 text-sm last:border-b-0 {cols}">
                        {@render cells()}
                    </div>
                {/if}
            {/each}
        </div>

        <!-- Tablet and phone -->
        <ul class="flex flex-col gap-2.5 md:gap-0 md:overflow-hidden md:rounded-2xl md:border md:border-line md:bg-sf lg:hidden">
            {#each jobs.data as job (job.uuid)}
                {@const href = hrefFor(job)}
                {#snippet body()}
                    <span class="flex min-w-0 flex-1 flex-col gap-[3px]">
                        <span class="flex flex-wrap items-center gap-2">
                            <b class="truncate text-base">{job.ownerName}</b>
                            {@render progress(job)}
                        </span>
                        <span class="truncate text-sm text-mut">{job.address}</span>
                        <span class="truncate text-[13px] text-mut">{job.area} · {job.contractor} · <span class="font-mono">{job.assignedAt}</span></span>
                        {#if job.contractorLicenceExpired && job.progress === 'not_started'}<span class="text-[13px] font-bold text-bad">Contractor’s licence expired · reassign</span>{/if}
                    </span>
                    {#if href}<ChevronRight class="size-6 flex-none text-mut" aria-hidden="true" />{/if}
                {/snippet}
                <li class="md:border-b md:border-line md:last:border-b-0">
                    {#if href}
                        <Link {href} class="flex min-h-[72px] items-center gap-2 rounded-2xl border border-line bg-sf py-3 pr-2 pl-3.5 text-ink no-underline md:rounded-none md:border-0 md:pl-5">
                            {@render body()}
                        </Link>
                    {:else}
                        <div class="flex min-h-[72px] items-center gap-2 rounded-2xl border border-line bg-sf py-3 pr-2 pl-3.5 md:rounded-none md:border-0 md:pl-5">
                            {@render body()}
                        </div>
                    {/if}
                </li>
            {/each}
        </ul>

        <Pagination meta={jobs.meta} links={jobs.links} noun="jobs" />
    {/if}
</div>
