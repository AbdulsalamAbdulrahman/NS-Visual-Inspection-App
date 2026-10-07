<script lang="ts">
    import { Dialog } from 'bits-ui';
    import Button from '@/components/Button.svelte';
    import Field from '@/components/form/Field.svelte';
    import NumberInput from '@/components/form/NumberInput.svelte';
    import Select from '@/components/form/Select.svelte';
    import TextInput from '@/components/form/TextInput.svelte';
    import { CONDITIONS, CONDITION_ORDER } from '@/lib/inspection/conditions';
    import type { Circuit, Option } from '@/lib/inspection/types';
    import Close from '~icons/ms/close';
    import Delete from '~icons/ms/delete';
    import RadioButtonChecked from '~icons/ms/radio-button-checked';
    import RadioButtonUnchecked from '~icons/ms/radio-button-unchecked';

    type Props = {
        open: boolean;
        /** Working copy being edited (null while closed). */
        circuit: Circuit | null;
        /** "C5" */
        label: string;
        isNew: boolean;
        descriptions: Option[];
        onsave: (circuit: Circuit) => void;
        onremove: (uuid: string) => void;
    };

    let { open = $bindable(false), circuit, label, isNew, descriptions, onsave, onremove }: Props = $props();

    let tried = $state(false);

    const conditionMissing = $derived(tried && !circuit?.condition);

    function save(): void {
        if (!circuit) {
            return;
        }

        // Condition is the one thing a card can't do without.
        if (!circuit.condition) {
            tried = true;

            return;
        }

        tried = false;
        onsave(circuit);
        open = false;
    }
</script>

<Dialog.Root bind:open onOpenChange={(o) => !o && (tried = false)}>
    <Dialog.Portal>
        <Dialog.Overlay class="fixed inset-0 z-40 bg-scrim" />
        <Dialog.Content
            class="fixed inset-x-0 top-[max(48px,env(safe-area-inset-top))] bottom-0 z-50 flex flex-col rounded-t-3xl bg-sf text-ink outline-none lg:inset-x-auto lg:top-1/2 lg:bottom-auto lg:left-1/2 lg:max-h-[90dvh] lg:w-[520px] lg:-translate-x-1/2 lg:-translate-y-1/2 lg:rounded-3xl"
        >
            {#if circuit}
                <div class="flex justify-center pt-2.5 pb-1 lg:hidden" aria-hidden="true">
                    <div class="h-[5px] w-10 rounded-full bg-line"></div>
                </div>
                <div class="flex items-center pt-1 pr-2 pb-2 pl-5">
                    <div class="flex flex-1 flex-col">
                        <span class="font-mono text-xs font-semibold text-mut">{label}</span>
                        <Dialog.Title class="text-[20px] font-bold">{isNew ? 'New circuit' : 'Edit circuit'}</Dialog.Title>
                    </div>
                    <Dialog.Close class="flex size-12 items-center justify-center rounded-xl hover:bg-sf2" aria-label="Close">
                        <Close class="size-6" />
                    </Dialog.Close>
                </div>

                <div class="flex flex-1 flex-col gap-3.5 overflow-y-auto px-5 py-1">
                    <Field id="circuit-description" label="Circuit description">
                        <Select
                            id="circuit-description"
                            options={descriptions}
                            placeholder="Choose what this circuit feeds"
                            bind:value={circuit.description}
                        />
                        {#if circuit.description === 'other'}
                            <TextInput
                                id="circuit-description-other"
                                aria-label="Describe the circuit"
                                placeholder="e.g. Perimeter security lights"
                                bind:value={circuit.description_other}
                            />
                        {/if}
                    </Field>

                    <div class="grid grid-cols-2 gap-2.5">
                        <Field id="circuit-rating" label="Rating">
                            <NumberInput id="circuit-rating" unit="A" bind:value={circuit.rating_a} />
                        </Field>
                        <Field id="circuit-conductor" label="Conductor">
                            <NumberInput id="circuit-conductor" unit="mm²" bind:value={circuit.conductor_mm2} />
                        </Field>
                    </div>

                    <fieldset class="flex flex-col gap-2">
                        <legend class="mb-2 text-[15px] font-semibold">Condition <span class="text-bad" aria-hidden="true">*</span></legend>
                        <div class="flex flex-col gap-1.5" role="radiogroup" aria-label="Condition" aria-invalid={conditionMissing || undefined}>
                            {#each CONDITION_ORDER as key (key)}
                                {@const c = CONDITIONS[key]}
                                {@const on = circuit.condition === key}
                                <button
                                    type="button"
                                    role="radio"
                                    aria-checked={on}
                                    class={[
                                        'flex min-h-[50px] items-center gap-2.5 rounded-xl px-3.5 text-left text-[15px]',
                                        on ? ['border-2 font-bold', c.border, c.bg] : 'border-[1.5px] border-line font-semibold',
                                    ]}
                                    onclick={() => circuit && (circuit.condition = key)}
                                >
                                    <c.icon class={['size-6 flex-none', c.fg]} aria-hidden="true" />
                                    <span class="flex-1">{c.label}</span>
                                    {#if on}
                                        <RadioButtonChecked class={['size-6', c.fg]} aria-hidden="true" />
                                    {:else}
                                        <RadioButtonUnchecked class="size-6 text-line" aria-hidden="true" />
                                    {/if}
                                </button>
                            {/each}
                        </div>
                        {#if conditionMissing}
                            <span class="text-sm font-semibold text-bad" role="alert">Choose a condition for this circuit.</span>
                        {/if}
                    </fieldset>

                    <Field id="circuit-observation">
                        {#snippet labelAside()}
                            <label for="circuit-observation" class="text-[15px] font-semibold">
                                Observation <span class="font-normal text-mut">(optional)</span>
                            </label>
                        {/snippet}
                        <textarea
                            id="circuit-observation"
                            bind:value={circuit.observation}
                            rows="2"
                            maxlength="1000"
                            placeholder="e.g. two fittings without covers"
                            class="min-h-14 w-full rounded-xl border-[1.5px] border-line bg-sf p-3.5 text-base outline-none focus:border-2 focus:border-pri"
                        ></textarea>
                    </Field>
                </div>

                <div class="pb-safe flex gap-2.5 border-t border-line px-4 pt-3">
                    {#if !isNew}
                        <Button variant="ghost" class="px-4 text-base text-bad" onclick={() => circuit && (onremove(circuit.uuid), (open = false))}>
                            <Delete />Remove
                        </Button>
                    {/if}
                    <Button block class="flex-1" onclick={save}>Save circuit</Button>
                </div>
            {/if}
        </Dialog.Content>
    </Dialog.Portal>
</Dialog.Root>
