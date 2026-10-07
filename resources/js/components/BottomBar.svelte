<script lang="ts">
    import type { Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    type Props = {
        children: Snippet;
        class?: string;
    };

    let { children, class: className }: Props = $props();
</script>

<!-- Thumb-zone action bar: sticks to the bottom of the viewport on phones. -->
<div
    class={cn(
        'pb-safe sticky bottom-0 z-20 mt-auto flex-none border-t border-line bg-sf px-4 pt-3',
        className,
    )}
>
    {@render children()}
</div>
