<script lang="ts" module>
    export type LecanRow = {
        uuid: string;
        name: string;
        email: string;
        phone: string | null;
        state: string | null;
        stateLabel: string | null;
        status: 'invited' | 'active' | 'suspended';
        statusLabel: string;
        lastActive: string;
    };
</script>

<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import Button from '@/components/Button.svelte';
    import Drawer from '@/components/Drawer.svelte';
    import ChoiceGroup from '@/components/form/ChoiceGroup.svelte';
    import Field from '@/components/form/Field.svelte';
    import TextInput from '@/components/form/TextInput.svelte';
    import { store, update } from '@/routes/admin/lecan';
    import Mail from '~icons/ms/mail';
    import Send from '~icons/ms/send';

    type Props = {
        open: boolean;
        official: LecanRow | null;
        states: { value: string; label: string }[];
    };

    let { open = $bindable(false), official, states }: Props = $props();

    const form = useForm({ name: '', email: '', phone: '', lecan_state: null as string | null });

    const editing = $derived(official !== null);

    export function reset(row: LecanRow | null): void {
        form.clearErrors();
        form.name = row?.name ?? '';
        form.email = row?.email ?? '';
        form.phone = row?.phone ?? '';
        form.lecan_state = row?.state ?? null;
    }

    function submit(event: SubmitEvent): void {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => (open = false) };

        if (official) {
            form.put(update.url(official.uuid), options);
        } else {
            form.post(store.url(), options);
        }
    }

    const compact = 'lg:h-12 lg:rounded-[10px] lg:text-[15px]';
</script>

<Drawer bind:open title={editing ? 'Edit LECAN official' : 'Add LECAN official'}>
    <form id="lecan-form" class="contents" onsubmit={submit} novalidate>
        <Field id="l-name" label="Full name" error={form.errors.name}>
            <TextInput id="l-name" size="md" class={compact} bind:value={form.name} autocomplete="off" invalid={!!form.errors.name} />
        </Field>

        <div class="grid gap-3.5 lg:grid-cols-[1.3fr_1fr] lg:gap-3">
            <Field id="l-email" label="Email" error={form.errors.email}>
                <TextInput id="l-email" type="email" size="md" class={compact} bind:value={form.email} autocomplete="off" invalid={!!form.errors.email} />
            </Field>
            <Field id="l-phone" error={form.errors.phone}>
                {#snippet labelAside()}
                    <label for="l-phone" class="text-[15px] font-semibold lg:text-sm">Phone <span class="font-normal text-mut">optional</span></label>
                {/snippet}
                <TextInput id="l-phone" type="tel" inputmode="tel" mono size="md" class={compact} bind:value={form.phone} invalid={!!form.errors.phone} />
            </Field>
        </div>

        <Field id="l-state" label="State chapter" hint="They review paid reports from area offices in this state, before NSD." error={form.errors.lecan_state}>
            <ChoiceGroup label="State chapter" variant="segmented" options={states} bind:value={form.lecan_state} invalid={!!form.errors.lecan_state} />
        </Field>

        {#if !editing}
            <div class="flex gap-2.5 rounded-xl bg-info-bg p-3.5 text-sm leading-[1.45]">
                <Mail class="size-[22px] flex-none text-info" />
                <span>We'll email a temporary password to this address. They must set their own on first sign-in.</span>
            </div>
        {/if}
    </form>

    {#snippet footer()}
        <Button variant="outline" size="md" class="hidden border-[1.5px] lg:inline-flex lg:h-12" onclick={() => (open = false)}>Cancel</Button>
        <Button type="submit" form="lecan-form" block class="lg:h-12 lg:flex-1 lg:text-base" disabled={form.processing}>
            {#if editing}Save changes{:else}<Send />Create &amp; send login details{/if}
        </Button>
    {/snippet}
</Drawer>
