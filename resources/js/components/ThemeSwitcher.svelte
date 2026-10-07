<script lang="ts">
    import { appearanceLabels, themeState } from '@/lib/theme.svelte';
    import type { Appearance } from '@/types';
    import { cn } from '@/lib/utils';

    type Props = { class?: string };

    let { class: className }: Props = $props();

    const theme = themeState();
    const options: Appearance[] = ['light', 'dark', 'system'];
</script>

<!-- Light / Dark / Auto segmented control (CH-03). -->
<div
    role="radiogroup"
    aria-label="Theme"
    class={cn('grid grid-cols-3 gap-0.5 rounded-[10px] bg-sf2 p-[3px] text-[13px] font-semibold', className)}
>
    {#each options as option (option)}
        <button
            type="button"
            role="radio"
            aria-checked={theme.appearance === option}
            class={[
                'min-h-9 rounded-lg px-2.5',
                theme.appearance === option
                    ? 'bg-sf text-ink shadow-[0_1px_2px_rgba(0,0,0,.12)]'
                    : 'text-mut hover:text-ink',
            ]}
            onclick={() => theme.update(option)}
        >
            {appearanceLabels[option]}
        </button>
    {/each}
</div>
