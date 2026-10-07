<script lang="ts">
    import type { Snippet } from 'svelte';
    import ErrorIcon from '~icons/ms/error';
    import Warning from '~icons/ms/warning';
    import { cn } from '@/lib/utils';

    type Props = {
        /** id of the control, wired to the label and described-by text. */
        id: string;
        label?: string;
        /** Optional element at the right of the label row (e.g. "Forgot password?"). */
        labelAside?: Snippet;
        hint?: string;
        /** Blocking error (red, icon). Takes precedence over warning/hint. */
        error?: string | null;
        /** Soft, non-blocking warning for out-of-range readings. */
        warning?: string | null;
        class?: string;
        children: Snippet;
    };

    let {
        id,
        label,
        labelAside,
        hint,
        error,
        warning,
        class: className,
        children,
    }: Props = $props();
</script>

<div class={cn('flex min-w-0 flex-col gap-1.5', className)}>
    {#if label || labelAside}
        <div class="flex items-baseline justify-between gap-3">
            {#if label}
                <label for={id} class="text-[15px] font-semibold">{label}</label>
            {/if}
            {@render labelAside?.()}
        </div>
    {/if}

    {@render children()}

    {#if error}
        <span id="{id}-error" class="flex items-center gap-1 text-[13px] font-semibold text-bad">
            <ErrorIcon class="size-4 flex-none" />{error}
        </span>
    {:else if warning}
        <span id="{id}-warning" class="flex items-center gap-1 text-[13px] font-semibold text-imp">
            <Warning class="size-4 flex-none" />{warning}
        </span>
    {:else if hint}
        <span id="{id}-hint" class="text-[13px] text-mut">{hint}</span>
    {/if}
</div>
