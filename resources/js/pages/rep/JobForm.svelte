<script lang="ts" module>
    export { default as layout } from '@/layouts/RepLayout.svelte';
</script>

<script lang="ts">
    import { Link, router, useForm } from '@inertiajs/svelte';
    import Button from '@/components/Button.svelte';
    import ChoiceGroup from '@/components/form/ChoiceGroup.svelte';
    import Field from '@/components/form/Field.svelte';
    import Select from '@/components/form/Select.svelte';
    import TextInput from '@/components/form/TextInput.svelte';
    import Required from '@/components/inspection/Required.svelte';
    import { form74Count } from '@/lib/inspection/fee';
    import type { Job } from '@/lib/inspection/job';
    import { destroy, index, store, update } from '@/routes/rep/jobs';
    import ArrowBack from '~icons/ms/arrow-back';
    import CheckCircle from '~icons/ms/check-circle';
    import Mail from '~icons/ms/mail';
    import RadioUnchecked from '~icons/ms/radio-button-unchecked';
    import Search from '~icons/ms/search';

    type Option = { value: string; label: string };
    type Contractor = { uuid: string; name: string; firm: string | null; category: string | null; state: string | null; openJobs: number };

    type Props = {
        job: Job | null;
        areas: { id: number; name: string; state: string; stateLabel: string }[];
        contractors: Contractor[];
        options: { purpose: Option[]; buildingType: (Option & { purpose: string })[] };
    };

    let { job, areas, contractors, options }: Props = $props();

    // svelte-ignore state_referenced_locally
    const form = useForm({
        owner_name: job?.ownerName ?? '',
        property_address: job?.propertyAddress ?? '',
        owner_phone: job?.ownerPhone ?? '',
        owner_email: job?.ownerEmail ?? '',
        // One area office: no need to pick.
        service_area_id: job?.areaId ?? (areas.length === 1 ? areas[0].id : null),
        purpose: job?.purpose ?? null,
        building_type: job?.buildingType ?? null,
        form74_no: job?.form74No ?? '',
        form74_last_no: job?.form74LastNo ?? '',
        contractor: job?.contractorUuid ?? null,
    });

    // svelte-ignore state_referenced_locally
    let estate = $state(!!job?.form74LastNo);
    let contractorSearch = $state('');
    let confirmingCancel = $state(false);

    const area = $derived(areas.find((a) => a.id === form.service_area_id) ?? null);
    const buildingOptions = $derived(options.buildingType.filter((t) => t.purpose === form.purpose));
    const count = $derived(form74Count(form.form74_no, estate ? form.form74_last_no : null));

    /** Only contractors who work in the area office's state. */
    const eligible = $derived(area ? contractors.filter((c) => c.state === area.state) : []);
    const shown = $derived.by(() => {
        const term = contractorSearch.trim().toLowerCase();

        return term ? eligible.filter((c) => `${c.name} ${c.firm ?? ''}`.toLowerCase().includes(term)) : eligible;
    });

    function areaChanged(): void {
        if (form.contractor && !eligible.some((c) => c.uuid === form.contractor)) {
            form.contractor = null;
        }
    }

    function purposeChanged(): void {
        if (form.building_type && !buildingOptions.some((t) => t.value === form.building_type)) {
            form.building_type = null;
        }
    }

    function setEstate(value: boolean): void {
        estate = value;

        if (!value) {
            form.form74_last_no = '';
        }
    }

    function submit(event: SubmitEvent): void {
        event.preventDefault();

        if (job) {
            form.put(update.url(job.uuid), { preserveScroll: true });
        } else {
            form.post(store.url(), { preserveScroll: true });
        }
    }

    function cancelJob(): void {
        if (job) {
            router.delete(destroy.url(job.uuid));
        }
    }

    const card = 'flex flex-col gap-4 rounded-[20px] border border-line bg-sf p-4 lg:p-6';
    const label = 'text-[15px] font-semibold';
</script>

<svelte:head>
    <title>{job ? 'Edit job' : 'New job'} · KENS</title>
</svelte:head>

<header class="sticky top-0 z-20 flex items-center gap-1 border-b border-line bg-sf px-2 pt-[calc(4px+env(safe-area-inset-top))] pb-1 md:hidden">
    <Link href={index.url()} class="flex size-12 items-center justify-center rounded-xl text-ink" aria-label="Back to jobs">
        <ArrowBack class="size-[22px]" />
    </Link>
    <h1 class="flex-1 text-[18px] font-bold">{job ? 'Edit job' : 'New job'}</h1>
</header>

<form class="mx-auto flex w-full max-w-[1100px] flex-col gap-5 px-4 py-4 md:px-6 lg:px-10 lg:py-8" onsubmit={submit} novalidate>
    <div class="hidden flex-col gap-1 md:flex">
        <Link href={index.url()} class="flex items-center gap-1 text-sm font-semibold text-mut no-underline"><ArrowBack class="size-4" />Jobs</Link>
        <h1 class="text-[28px] font-extrabold">{job ? `Edit job · ${job.ownerName}` : 'New job'}</h1>
        <p class="text-[15px] text-mut">The contractor sees these details filled in and can’t change them. You can edit or reassign the job until they start it.</p>
    </div>

    <div class="grid gap-5 lg:grid-cols-[1fr_420px] lg:items-start">
        <section class={card} aria-labelledby="customer-heading">
            <h2 id="customer-heading" class="text-[17px] font-bold">Customer</h2>

            <Field id="owner_name" error={form.errors.owner_name}>
                {#snippet labelAside()}<label for="owner_name" class={label}>Property owner name <Required /></label>{/snippet}
                <TextInput id="owner_name" size="md" bind:value={form.owner_name} autocomplete="off" autocapitalize="words" invalid={!!form.errors.owner_name} />
            </Field>

            <div class="grid gap-4 sm:grid-cols-2">
                <Field id="owner_phone" error={form.errors.owner_phone}>
                    {#snippet labelAside()}<label for="owner_phone" class={label}>Phone <Required /></label>{/snippet}
                    <TextInput id="owner_phone" type="tel" inputmode="tel" mono size="md" placeholder="08031234567" bind:value={form.owner_phone} invalid={!!form.errors.owner_phone} />
                </Field>
                <Field id="owner_email" error={form.errors.owner_email}>
                    {#snippet labelAside()}<label for="owner_email" class={label}>Email <Required /></label>{/snippet}
                    <TextInput id="owner_email" type="email" size="md" bind:value={form.owner_email} autocomplete="off" invalid={!!form.errors.owner_email} />
                </Field>
            </div>

            <Field id="property_address" error={form.errors.property_address}>
                {#snippet labelAside()}<label for="property_address" class={label}>Property address <Required /></label>{/snippet}
                <textarea
                    id="property_address"
                    bind:value={form.property_address}
                    rows="2"
                    maxlength="1000"
                    aria-invalid={!!form.errors.property_address || undefined}
                    class={[
                        'min-h-[72px] w-full rounded-xl bg-sf p-3.5 text-base leading-[1.4] outline-none',
                        form.errors.property_address ? 'border-2 border-bad' : 'border-[1.5px] border-line focus:border-2 focus:border-pri',
                    ]}
                ></textarea>
            </Field>

            <Field id="purpose" error={form.errors.purpose}>
                {#snippet labelAside()}<span class={label}>Purpose of property <Required /></span>{/snippet}
                <ChoiceGroup label="Purpose of property" options={options.purpose} bind:value={form.purpose} invalid={!!form.errors.purpose} onchange={purposeChanged} />
            </Field>

            {#if form.purpose}
                <Field id="building_type" error={form.errors.building_type}>
                    {#snippet labelAside()}<span class={label}>Description of building <Required /></span>{/snippet}
                    <ChoiceGroup label="Description of building" options={buildingOptions} bind:value={form.building_type} invalid={!!form.errors.building_type} />
                </Field>
            {/if}

            <ChoiceGroup
                label="Number of structures"
                variant="segmented"
                options={[
                    { value: false, label: 'Single building' },
                    { value: true, label: 'Estate · several' },
                ]}
                value={estate}
                onchange={setEstate}
            />

            <div class={['grid gap-4', estate && 'sm:grid-cols-2']}>
                <Field id="form74_no" error={form.errors.form74_no}>
                    {#snippet labelAside()}<label for="form74_no" class={label}>{estate ? 'First Form 74 number' : 'Form 74 number'} <Required /></label>{/snippet}
                    <TextInput id="form74_no" mono size="md" bind:value={form.form74_no} placeholder="084123" autocapitalize="characters" invalid={!!form.errors.form74_no} />
                </Field>
                {#if estate}
                    <Field
                        id="form74_last_no"
                        error={form.errors.form74_last_no ?? (form.form74_last_no && typeof count === 'string' ? count : null)}
                        hint={typeof count === 'number' && count > 1 ? `${count} structures, charged per structure.` : 'Full number or just its last digits.'}
                    >
                        {#snippet labelAside()}<label for="form74_last_no" class={label}>Last Form 74 number <Required /></label>{/snippet}
                        <TextInput id="form74_last_no" mono size="md" bind:value={form.form74_last_no} placeholder="084142" autocapitalize="characters" />
                    </Field>
                {/if}
            </div>
        </section>

        <div class="flex flex-col gap-5 lg:sticky lg:top-24">
            <section class={card} aria-labelledby="assign-heading">
                <h2 id="assign-heading" class="text-[17px] font-bold">Assign</h2>

                <Field id="service_area_id" error={form.errors.service_area_id}>
                    {#snippet labelAside()}<label for="service_area_id" class={label}>Area office <Required /></label>{/snippet}
                    <Select
                        id="service_area_id"
                        size="md"
                        options={areas.map((a) => ({ value: a.id, label: `${a.name} · ${a.stateLabel}` }))}
                        placeholder="Choose area office"
                        bind:value={form.service_area_id}
                        invalid={!!form.errors.service_area_id}
                        onchange={areaChanged}
                    />
                </Field>

                <fieldset class="flex flex-col gap-2.5" aria-describedby={form.errors.contractor ? 'contractor-error' : undefined}>
                    <legend class="mb-2.5 {label}">Contractor <Required /></legend>
                    {#if !area}
                        <p class="rounded-xl bg-sf2 px-3.5 py-3 text-sm text-mut">Choose the area office first. You’ll see contractors who work in its state.</p>
                    {:else if eligible.length === 0}
                        <p class="rounded-xl bg-imp-bg px-3.5 py-3 text-sm">No active contractors work in {area.stateLabel} State yet. Ask the NSD admin to add one.</p>
                    {:else}
                        {#if eligible.length > 6}
                            <label class="flex h-11 items-center gap-2 rounded-xl border-[1.5px] border-line bg-sf px-3 focus-within:border-pri">
                                <Search class="size-5 text-mut" />
                                <span class="sr-only">Search contractors</span>
                                <input bind:value={contractorSearch} placeholder="Search {eligible.length} contractors" class="h-full min-w-0 flex-1 bg-transparent text-[15px] outline-none" />
                            </label>
                        {/if}
                        <div class="flex max-h-[360px] flex-col gap-2 overflow-y-auto">
                            {#each shown as c (c.uuid)}
                                {@const picked = form.contractor === c.uuid}
                                <label
                                    class={[
                                        'flex min-h-14 cursor-pointer items-center gap-3 rounded-xl border px-3.5 py-2.5',
                                        picked ? 'border-2 border-pri bg-soft' : 'border-line bg-sf hover:bg-sf2',
                                    ]}
                                >
                                    <input type="radio" name="contractor" value={c.uuid} bind:group={form.contractor} class="sr-only" />
                                    {#if picked}<CheckCircle class="size-[22px] flex-none text-pri" />{:else}<RadioUnchecked class="size-[22px] flex-none text-mut" />{/if}
                                    <span class="flex min-w-0 flex-1 flex-col">
                                        <b class="truncate text-[15px]">{c.name}</b>
                                        <span class="truncate text-[13px] text-mut">{[c.firm, c.category].filter(Boolean).join(' · ')}</span>
                                    </span>
                                    <span class="flex-none text-right text-[13px] text-mut"><span class="font-mono">{c.openJobs}</span> open</span>
                                </label>
                            {:else}
                                <p class="px-1 text-sm text-mut">No contractor matches “{contractorSearch}”.</p>
                            {/each}
                        </div>
                    {/if}
                    {#if form.errors.contractor}<p id="contractor-error" class="text-sm font-semibold text-bad">{form.errors.contractor}</p>{/if}
                </fieldset>

                <div class="flex gap-2.5 rounded-xl bg-info-bg p-3.5 text-sm leading-[1.45]">
                    <Mail class="size-[22px] flex-none text-info" />
                    <span>
                        We’ll email the contractor the job, and tell the owner who will inspect. The owner also gets the payment receipt and, once approved, the certificate.
                    </span>
                </div>

                <Button type="submit" size="md" block disabled={form.processing}>
                    {job ? 'Save changes' : 'Assign job'}
                </Button>
            </section>

            {#if job}
                <section class="flex flex-col gap-3 rounded-[20px] border border-line bg-sf p-4 lg:p-6" aria-labelledby="cancel-heading">
                    <h2 id="cancel-heading" class="text-[15px] font-bold">Cancel this job</h2>
                    {#if confirmingCancel}
                        <p class="text-sm">The job is removed from {job.contractor}’s list. This can’t be undone.</p>
                        <div class="flex gap-2">
                            <Button variant="danger" size="sm" onclick={cancelJob}>Yes, cancel job</Button>
                            <Button variant="ghost" size="sm" onclick={() => (confirmingCancel = false)}>Keep it</Button>
                        </div>
                    {:else}
                        <p class="text-sm text-mut">Only while the contractor hasn’t started it.</p>
                        <Button variant="outline" size="sm" class="self-start" onclick={() => (confirmingCancel = true)}>Cancel job…</Button>
                    {/if}
                </section>
            {/if}
        </div>
    </div>
</form>
