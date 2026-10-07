<script lang="ts" module>
    export type RepRow = {
        uuid: string;
        name: string;
        email: string;
        phone: string | null;
        status: 'invited' | 'active' | 'suspended';
        statusLabel: string;
        lastActive: string;
        areas: { id: number; name: string }[];
    };

    export type AreaOption = { id: number; name: string; reps: string[] };
</script>

<script lang="ts">
    import { useForm } from '@inertiajs/svelte';
    import Button from '@/components/Button.svelte';
    import Drawer from '@/components/Drawer.svelte';
    import Field from '@/components/form/Field.svelte';
    import TextInput from '@/components/form/TextInput.svelte';
    import { store, update } from '@/routes/admin/reps';
    import CheckBox from '~icons/ms/check-box';
    import CheckBoxOutlineBlank from '~icons/ms/check-box-outline-blank';
    import Close from '~icons/ms/close';
    import Send from '~icons/ms/send';

    const MAX_AREAS = 3;

    type Props = {
        open: boolean;
        rep: RepRow | null;
        areas: AreaOption[];
    };

    let { open = $bindable(false), rep, areas }: Props = $props();

    const form = useForm({
        name: '',
        email: '',
        phone: '',
        service_area_ids: [] as number[],
    });

    const editing = $derived(rep !== null);
    const full = $derived(form.service_area_ids.length >= MAX_AREAS);
    const picked = $derived(areas.filter((a) => form.service_area_ids.includes(a.id)));

    export function reset(row: RepRow | null): void {
        form.clearErrors();
        form.name = row?.name ?? '';
        form.email = row?.email ?? '';
        form.phone = row?.phone ?? '';
        form.service_area_ids = row?.areas.map((a) => a.id) ?? [];
    }

    function toggle(id: number): void {
        form.service_area_ids = form.service_area_ids.includes(id)
            ? form.service_area_ids.filter((x) => x !== id)
            : full
              ? form.service_area_ids
              : [...form.service_area_ids, id];
    }

    /** Other reps already covering an area (excluding the one being edited). */
    function coveredBy(area: AreaOption): string {
        return area.reps.filter((name) => name !== rep?.name).join(', ');
    }

    function submit(event: SubmitEvent): void {
        event.preventDefault();
        const options = { preserveScroll: true, onSuccess: () => (open = false) };

        if (rep) {
            form.put(update.url(rep.uuid), options);
        } else {
            form.post(store.url(), options);
        }
    }

    const compact = 'lg:h-12 lg:rounded-[10px] lg:text-[15px]';
    const areaError = $derived(
        form.errors.service_area_ids ??
            Object.entries(form.errors).find(([key]) => key.startsWith('service_area_ids.'))?.[1],
    );
</script>

<Drawer bind:open title={editing ? 'Edit service rep' : 'Add service rep'}>
    <form id="rep-form" class="contents" onsubmit={submit} novalidate>
        <Field id="r-name" label="Full name" error={form.errors.name}>
            <TextInput id="r-name" size="md" class={compact} bind:value={form.name} autocomplete="off" invalid={!!form.errors.name} />
        </Field>

        <div class="grid gap-3.5 lg:grid-cols-[1.3fr_1fr] lg:gap-3">
            <Field id="r-email" label="Email" error={form.errors.email}>
                <TextInput id="r-email" type="email" size="md" class={compact} bind:value={form.email} autocomplete="off" invalid={!!form.errors.email} />
            </Field>
            <Field id="r-phone" label="Phone" error={form.errors.phone}>
                <TextInput id="r-phone" type="tel" inputmode="tel" mono size="md" class={compact} bind:value={form.phone} invalid={!!form.errors.phone} />
            </Field>
        </div>

        <fieldset class="flex flex-col gap-1.5" aria-describedby="r-areas-help">
            <div class="flex items-baseline justify-between">
                <legend class="text-[15px] font-semibold lg:text-sm">Service areas</legend>
                <span class="font-mono text-[13px] font-medium text-mut">{form.service_area_ids.length} of {MAX_AREAS} max</span>
            </div>

            <!-- Picked areas as removable chips -->
            <div
                class={[
                    'flex min-h-12 flex-wrap items-center gap-1.5 rounded-[10px] border-[1.5px] px-2.5 py-1.5',
                    areaError ? 'border-2 border-bad' : 'border-line',
                ]}
            >
                {#each picked as area (area.id)}
                    <span class="flex h-8 items-center gap-1 rounded-lg bg-soft pr-1 pl-2.5 text-[13px] font-bold text-brand">
                        {area.name}
                        <button
                            type="button"
                            class="flex size-6 items-center justify-center rounded-md hover:bg-brand/10"
                            aria-label="Remove {area.name}"
                            onclick={() => toggle(area.id)}
                        >
                            <Close class="size-4" />
                        </button>
                    </span>
                {:else}
                    <span class="pl-1 text-sm text-mut">Pick 1 to 3 areas below</span>
                {/each}
            </div>

            <div class="flex max-h-[280px] flex-col overflow-y-auto rounded-xl border border-line p-1.5 text-sm">
                {#each areas as area (area.id)}
                    {@const on = form.service_area_ids.includes(area.id)}
                    {@const others = coveredBy(area)}
                    <button
                        type="button"
                        role="checkbox"
                        aria-checked={on}
                        disabled={!on && full}
                        class={[
                            'flex min-h-10 items-center gap-2.5 rounded-lg px-2.5 text-left disabled:opacity-50',
                            on ? 'bg-sf2 font-bold' : 'hover:bg-sf2',
                        ]}
                        onclick={() => toggle(area.id)}
                    >
                        {#if on}
                            <CheckBox class="size-5 flex-none text-mid" />
                        {:else}
                            <CheckBoxOutlineBlank class="size-5 flex-none text-line" />
                        {/if}
                        <span class="flex-1">{area.name}</span>
                        {#if others}
                            <span class="text-xs font-normal text-mut">{others}</span>
                        {/if}
                    </button>
                {/each}
            </div>

            {#if areaError}
                <span class="text-[13px] font-semibold text-bad" role="alert">{areaError}</span>
            {:else}
                <span id="r-areas-help" class="text-[13px] text-mut">A rep sees only inspections whose service area is assigned here.</span>
            {/if}
        </fieldset>
    </form>

    {#snippet footer()}
        <Button variant="outline" size="md" class="hidden border-[1.5px] lg:inline-flex lg:h-12" onclick={() => (open = false)}>
            Cancel
        </Button>
        <Button type="submit" form="rep-form" block class="lg:h-12 lg:flex-1 lg:text-base" disabled={form.processing}>
            {#if editing}
                Save changes
            {:else}
                <Send />Create &amp; send login details
            {/if}
        </Button>
    {/snippet}
</Drawer>
