<script lang="ts" module>
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
    };

    let { open = $bindable(false), contractor, categories }: Props = $props();

    const form = useForm({
        name: '',
        email: '',
        phone: '',
        nemsa_category: null as string | null,
        nemsa_reg_no: '',
        coren_no: '',
        firm_name: '',
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
                    placeholder="NEMSA/A/2023/0142"
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
