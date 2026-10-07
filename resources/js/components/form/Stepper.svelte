<script lang="ts">
    import Add from '~icons/ms/add';
    import Remove from '~icons/ms/remove';

    type Props = {
        id: string;
        label: string;
        value: number | null;
        min?: number;
        max?: number;
    };

    let { id, label, value = $bindable(), min = 1, max = 8 }: Props = $props();

    function change(delta: number): void {
        const current = value ?? (delta > 0 ? min - 1 : min + 1);
        value = Math.min(max, Math.max(min, current + delta));
    }
</script>

<!-- −  4  +  (CF-06 "Number of poles") -->
<div class="flex items-center gap-1.5" role="group" aria-labelledby="{id}-label">
    <button
        type="button"
        class="flex size-12 items-center justify-center rounded-xl bg-sf2 text-ink disabled:opacity-40"
        aria-label="Fewer {label.toLowerCase()}"
        disabled={value !== null && value <= min}
        onclick={() => change(-1)}
    >
        <Remove class="size-6" />
    </button>
    <output id={id} class="w-11 text-center font-mono text-[20px] font-semibold" aria-live="polite">{value ?? '–'}</output>
    <button
        type="button"
        class="flex size-12 items-center justify-center rounded-xl bg-sf2 text-ink disabled:opacity-40"
        aria-label="More {label.toLowerCase()}"
        disabled={value !== null && value >= max}
        onclick={() => change(1)}
    >
        <Add class="size-6" />
    </button>
</div>
