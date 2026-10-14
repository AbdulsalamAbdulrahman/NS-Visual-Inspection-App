<script lang="ts" module>
    import type { LicenceStatus } from '@/components/account/ContractorDocuments.svelte';

    export type ContractorRow = {
        uuid: string;
        name: string;
        email: string;
        phone: string | null;
        status: 'invited' | 'active' | 'suspended';
        statusLabel: string;
        lastActive: string;
        inspectionsCount: number;
        category: string | null;
        categoryLabel: string | null;
        regNo: string | null;
        corenNo: string | null;
        firmName: string | null;
        engineerName: string | null;
        engineerRegNo: string | null;
        state: string | null;
        stateLabel: string | null;
        /** Uploaded by the contractor on Profile; admins view only. */
        licence: LicenceStatus;
        documents: { licence: string | null; passport: string | null; signature: string | null };
    };
</script>

<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import Button from '@/components/Button.svelte';
    import Drawer from '@/components/Drawer.svelte';
    import ChoiceGroup from '@/components/form/ChoiceGroup.svelte';
    import Field from '@/components/form/Field.svelte';
    import TextInput from '@/components/form/TextInput.svelte';
    import { store, update } from '@/routes/admin/contractors';
    import Mail from '~icons/ms/mail';
    import Send from '~icons/ms/send';

    type Props = {
        open: boolean;
        /** null = add a new contractor. */
        contractor: ContractorRow | null;
        categories: { value: string; label: string }[];
        /** LECAN chapters (Kaduna Electric's franchise states). */
        states: { value: string; label: string }[];
    };

    let { open = $bindable(false), contractor, categories, states }: Props = $props();

    const form = useForm({
        name: '',
        email: '',
        phone: '',
        nemsa_category: null as string | null,
        nemsa_reg_no: '',
        coren_no: '',
        firm_name: '',
        engineer_name: '',
        engineer_reg_no: '',
        state: null as string | null,
    });

    const editing = $derived(contractor !== null);
    const corporate = $derived(form.nemsa_category === 'corporate');

    // Refill the form each time the drawer opens for a different row.
    export function reset(row: ContractorRow | null): void {
        form.clearErrors();
        form.name = row?.name ?? '';
        form.email = row?.email ?? '';
        form.phone = row?.phone ?? '';
        form.nemsa_category = row?.category ?? null;
        form.nemsa_reg_no = row?.regNo ?? '';
        form.coren_no = row?.corenNo ?? '';
        form.firm_name = row?.firmName ?? '';
        form.engineer_name = row?.engineerName ?? '';
        form.engineer_reg_no = row?.engineerRegNo ?? '';
        form.state = row?.state ?? null;
    }

    function submit(event: SubmitEvent): void {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => (open = false) };

        if (contractor) {
            form.put(update.url(contractor.uuid), options);
        } else {
            form.post(store.url(), options);
        }
    }

    const compact = 'lg:h-12 lg:rounded-[10px] lg:text-[15px]';
</script>

<Drawer bind:open title={editing ? 'Edit contractor' : 'Add contractor'}>
    {#if contractor}
        <!-- Documents the contractor uploaded on their Profile (view only). -->
        <section class="flex flex-col gap-2.5 rounded-xl border border-line p-3.5" aria-label="Licence and documents">
            <div class="flex items-center gap-3">
                {#if contractor.documents.passport}
                    <img src={contractor.documents.passport} alt="Passport photograph of {contractor.name}" class="size-16 flex-none rounded-lg border border-line object-cover" />
                {/if}
                <div class="flex min-w-0 flex-col gap-0.5">
                    <b class="text-sm">Licence &amp; documents</b>
                    <span class={['text-sm font-semibold', contractor.licence.state === 'valid' ? 'text-ok' : contractor.licence.state === 'expired' ? 'text-bad' : 'text-imp']}>
                        {contractor.licence.label}
                    </span>
                    {#if contractor.licence.missing.length > 0}
                        <span class="text-[13px] text-mut">Missing: {contractor.licence.missing.join(', ')}</span>
                    {/if}
                </div>
            </div>
            <div class="flex flex-wrap gap-x-4 gap-y-1 text-sm font-semibold">
                {#if contractor.documents.licence}<a href={contractor.documents.licence} target="_blank" rel="noopener" class="text-brand">View licence</a>{/if}
                {#if contractor.documents.signature}<a href={contractor.documents.signature} target="_blank" rel="noopener" class="text-brand">View signature</a>{/if}
            </div>
            <span class="text-[13px] text-mut">The contractor uploads these on their Profile.</span>
        </section>
    {/if}

    <form id="contractor-form" class="contents" onsubmit={submit} novalidate>
        <Field id="c-name" label="Full name" error={form.errors.name}>
            <TextInput id="c-name" size="md" class={compact} bind:value={form.name} autocomplete="off" invalid={!!form.errors.name} />
        </Field>

        <div class="grid gap-3.5 lg:grid-cols-[1.3fr_1fr] lg:gap-3">
            <Field id="c-email" label="Email" error={form.errors.email}>
                <TextInput id="c-email" type="email" size="md" class={compact} bind:value={form.email} autocomplete="off" invalid={!!form.errors.email} />
            </Field>
            <Field id="c-phone" label="Phone" error={form.errors.phone}>
                <TextInput id="c-phone" type="tel" inputmode="tel" mono size="md" class={compact} bind:value={form.phone} invalid={!!form.errors.phone} />
            </Field>
        </div>

        <Field id="c-state" label="State they work in" hint="Reps assign jobs to contractors in the area office's state." error={form.errors.state}>
            <ChoiceGroup label="State they work in" variant="segmented" options={states} bind:value={form.state} invalid={!!form.errors.state} />
        </Field>

        <Field id="c-category" label="NEMSA category" error={form.errors.nemsa_category}>
            <ChoiceGroup
                label="NEMSA category"
                variant="responsive"
                options={categories}
                bind:value={form.nemsa_category}
                invalid={!!form.errors.nemsa_category}
            />
        </Field>

        <div class="grid gap-3.5 lg:grid-cols-2 lg:gap-3">
            <Field id="c-reg" label="NEMSA registration no." error={form.errors.nemsa_reg_no}>
                <TextInput
                    id="c-reg"
                    mono
                    size="md"
                    class={compact}
                    placeholder={corporate ? 'CC/CE1F/PR/08634' : 'Letters and numbers only'}
                    autocapitalize="characters"
                    bind:value={form.nemsa_reg_no}
                    invalid={!!form.errors.nemsa_reg_no}
                />
            </Field>
            <Field id="c-coren" error={form.errors.coren_no}>
                {#snippet labelAside()}
                    <label for="c-coren" class="text-[15px] font-semibold lg:text-sm">
                        COREN no. <span class="font-normal text-mut">optional</span>
                    </label>
                {/snippet}
                <TextInput id="c-coren" mono size="md" class={compact} placeholder="—" bind:value={form.coren_no} />
            </Field>
        </div>

        {#if corporate || form.firm_name}
            <Field id="c-firm" error={form.errors.firm_name}>
                {#snippet labelAside()}
                    <label for="c-firm" class="text-[15px] font-semibold lg:text-sm">
                        Firm name
                        {#if corporate}<span class="text-bad" aria-hidden="true">*</span>{:else}<span class="font-normal text-mut">optional</span>{/if}
                    </label>
                {/snippet}
                <TextInput id="c-firm" size="md" class={compact} bind:value={form.firm_name} invalid={!!form.errors.firm_name} required={corporate} />
            </Field>
        {/if}

        {#if corporate}
            <div class="grid gap-3.5 lg:grid-cols-2 lg:gap-3">
                <Field id="c-engineer" error={form.errors.engineer_name}>
                    {#snippet labelAside()}
                        <label for="c-engineer" class="text-[15px] font-semibold lg:text-sm">
                            Electrical engineer / technologist <span class="text-bad" aria-hidden="true">*</span>
                        </label>
                    {/snippet}
                    <TextInput id="c-engineer" size="md" class={compact} bind:value={form.engineer_name} invalid={!!form.errors.engineer_name} required />
                </Field>
                <Field id="c-engineer-reg" error={form.errors.engineer_reg_no}>
                    {#snippet labelAside()}
                        <label for="c-engineer-reg" class="text-[15px] font-semibold lg:text-sm">
                            Their reg. no. <span class="font-normal text-mut">optional</span>
                        </label>
                    {/snippet}
                    <TextInput id="c-engineer-reg" mono size="md" class={compact} placeholder="—" bind:value={form.engineer_reg_no} />
                </Field>
            </div>
        {/if}

        {#if !editing}
            <div class="flex gap-2.5 rounded-xl bg-info-bg p-3.5 text-sm leading-[1.45]">
                <Mail class="size-[22px] flex-none text-info" />
                <span>We'll email a temporary password to this address. They must set their own on first sign-in.</span>
            </div>
        {/if}
    </form>

    {#snippet footer()}
        <Button variant="outline" size="md" class="hidden border-[1.5px] lg:inline-flex lg:h-12" onclick={() => (open = false)}>
            Cancel
        </Button>
        <Button type="submit" form="contractor-form" block class="lg:h-12 lg:flex-1 lg:text-base" disabled={form.processing}>
            {#if editing}
                Save changes
            {:else}
                <Send />Create &amp; send login details
            {/if}
        </Button>
    {/snippet}
</Drawer>
