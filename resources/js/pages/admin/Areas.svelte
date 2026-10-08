<script lang="ts" module>
    export { default as layout } from '@/layouts/AdminLayout.svelte';
</script>

<script lang="ts">
    import { Link, router, useForm } from '@inertiajs/svelte';
    import Button from '@/components/Button.svelte';
    import StatusPill from '@/components/StatusPill.svelte';
    import admin from '@/routes/admin';
    import { store, toggle, update } from '@/routes/admin/areas';
    import Add from '~icons/ms/add';
    import ArrowBack from '~icons/ms/arrow-back';
    import ErrorIcon from '~icons/ms/error';

    type Area = {
        id: number;
        name: string;
        isActive: boolean;
        reps: string[];
        inspectionsTotal: number;
        inspectionsThisMonth: number;
    };

    /** `editable` is off while areas come from Kaduna Electric's official list (config/kens.php). */
    let { areas, editable }: { areas: Area[]; editable: boolean } = $props();

    const addForm = useForm({ name: '' });
    const renameForm = useForm({ name: '' });

    let renaming = $state<number | null>(null);

    function add(event: SubmitEvent): void {
        event.preventDefault();
        addForm.post(store.url(), { preserveScroll: true, onSuccess: () => addForm.reset() });
    }

    function startRename(area: Area): void {
        renameForm.clearErrors();
        renameForm.name = area.name;
        renaming = area.id;
    }

    function saveRename(event: SubmitEvent, area: Area): void {
        event.preventDefault();
        renameForm.put(update.url(area.id), { preserveScroll: true, onSuccess: () => (renaming = null) });
    }

    function flip(area: Area): void {
        router.post(toggle.url(area.id), {}, { preserveScroll: true });
    }

    const cols = 'lg:grid-cols-[minmax(0,1.4fr)_110px_110px_minmax(0,1.2fr)_100px_190px]';
</script>

<svelte:head>
    <title>Service areas · KENS</title>
</svelte:head>

<header class="sticky top-0 z-20 flex items-center gap-1 border-b border-line bg-sf px-2 pt-[calc(4px+env(safe-area-inset-top))] pb-1 lg:hidden">
    <Link href={admin.more()} class="flex size-12 items-center justify-center rounded-xl text-ink" aria-label="Back to More">
        <ArrowBack class="size-[22px]" />
    </Link>
    <h1 class="flex-1 text-[18px] font-bold">Service areas</h1>
</header>

<div class="flex flex-col gap-[18px] px-4 py-4 lg:px-10 lg:py-8">
    <div class="hidden flex-col gap-0.5 lg:flex">
        <h1 class="text-[28px] font-extrabold">Service areas</h1>
        <span class="text-sm text-mut">
            {editable
                ? "Shown in the contractor's Service area dropdown. Deactivated areas keep their history."
                : "Kaduna Electric's area offices, shown in the contractor's Service area dropdown."}
        </span>
    </div>
    <p class="text-sm text-mut lg:hidden">
        {editable
            ? "Shown in the contractor's Service area dropdown. Deactivated areas keep their history."
            : "Kaduna Electric's area offices, shown in the contractor's Service area dropdown."}
    </p>

    {#if editable}
    <form class="flex flex-col gap-1.5" onsubmit={add} novalidate>
        <div class="flex gap-2.5">
            <label class="sr-only" for="new-area">New area name</label>
            <input
                id="new-area"
                bind:value={addForm.name}
                placeholder="New area name"
                maxlength="80"
                aria-invalid={!!addForm.errors.name || undefined}
                class={[
                    'h-12 min-w-0 flex-1 rounded-xl bg-sf px-3.5 text-[15px] outline-none lg:w-[360px] lg:flex-none',
                    addForm.errors.name ? 'border-2 border-bad' : 'border-[1.5px] border-line focus:border-2 focus:border-pri',
                ]}
            />
            <Button type="submit" size="md" class="h-12 rounded-xl px-[18px] text-[15px]" disabled={addForm.processing || !addForm.name.trim()}>
                <Add />Add area
            </Button>
        </div>
        {#if addForm.errors.name}
            <span class="flex items-center gap-1 text-[13px] font-semibold text-bad"><ErrorIcon class="size-4" />{addForm.errors.name}</span>
        {/if}
    </form>
    {/if}

    <div class="overflow-hidden rounded-2xl border border-line bg-sf lg:max-w-[980px]" role="table" aria-label="Service areas">
        <div role="row" class="hidden gap-3 border-b border-line bg-sf2 px-5 py-3 text-xs font-bold tracking-[0.06em] text-mut lg:grid {cols}">
            <span role="columnheader">AREA</span>
            <span role="columnheader" class="text-right">INSPECTIONS</span>
            <span role="columnheader" class="text-right">THIS MONTH</span>
            <span role="columnheader">REPS</span>
            <span role="columnheader">STATUS</span>
            <span role="columnheader"><span class="sr-only">Actions</span></span>
        </div>

        {#each areas as area (area.id)}
            {@const editing = renaming === area.id}
            <div
                role="row"
                class={[
                    'grid grid-cols-[1fr_auto] items-center gap-x-3 gap-y-1 border-b border-line px-4 py-3 text-sm last:border-b-0 lg:min-h-[50px] lg:px-5 lg:py-0',
                    cols,
                    editing && 'bg-soft lg:min-h-[58px]',
                    !area.isActive && !editing && 'text-mut',
                ]}
            >
                {#if editing}
                    <form id="rename-{area.id}" class="contents" onsubmit={(e) => saveRename(e, area)} novalidate>
                        <div role="cell" class="col-span-2 flex flex-col gap-1 lg:col-span-1">
                            <label class="sr-only" for="rename-input-{area.id}">Area name</label>
                            <!-- svelte-ignore a11y_autofocus -->
                            <input
                                id="rename-input-{area.id}"
                                bind:value={renameForm.name}
                                autofocus
                                maxlength="80"
                                class={[
                                    'h-10 rounded-[10px] bg-sf px-2.5 font-semibold text-ink outline-none',
                                    renameForm.errors.name ? 'border-2 border-bad' : 'border-2 border-pri',
                                ]}
                            />
                            {#if renameForm.errors.name}
                                <span class="text-[13px] font-semibold text-bad">{renameForm.errors.name}</span>
                            {/if}
                        </div>
                    </form>
                {:else}
                    <b role="cell" class="truncate text-[15px] lg:text-sm">{area.name}</b>
                {/if}

                <span role="cell" class="hidden text-right font-mono lg:block">{area.inspectionsTotal}</span>
                <span role="cell" class="hidden text-right font-mono lg:block">{area.inspectionsThisMonth}</span>
                <span role="cell" class={['truncate text-mut', editing ? 'hidden lg:block' : 'col-start-1 row-start-2 lg:col-auto lg:row-auto']}>
                    {area.reps.length ? area.reps.join(', ') : '—'}
                </span>
                <span role="cell" class={editing ? 'hidden lg:block' : 'col-start-2 row-start-1 justify-self-end lg:col-auto lg:row-auto lg:justify-self-start'}>
                    {#if !editing}
                        <StatusPill tone={area.isActive ? 'ok' : 'muted'} label={area.isActive ? 'Active' : 'Inactive'} />
                    {/if}
                </span>
                <span
                    role="cell"
                    class={[
                        'flex justify-end gap-1.5 text-[13px] font-bold',
                        editing ? 'col-span-2 lg:col-span-1' : 'col-start-2 row-start-2 lg:col-auto lg:row-auto',
                    ]}
                >
                    {#if editing}
                        <Button type="submit" form="rename-{area.id}" size="xs" class="h-9 rounded-lg px-3 text-[13px]" disabled={renameForm.processing}>
                            Save
                        </Button>
                        <Button variant="ghost" size="xs" class="h-9 rounded-lg px-3 text-[13px]" onclick={() => (renaming = null)}>
                            Cancel
                        </Button>
                    {:else if editable}
                        <button type="button" class="min-h-9 rounded-lg px-2 text-brand hover:bg-sf2" onclick={() => startRename(area)}>
                            Rename
                        </button>
                        <button type="button" class="min-h-9 rounded-lg px-2 text-brand hover:bg-sf2" onclick={() => flip(area)}>
                            {area.isActive ? 'Deactivate' : 'Reactivate'}
                        </button>
                    {/if}
                </span>
            </div>
        {/each}
    </div>
</div>
