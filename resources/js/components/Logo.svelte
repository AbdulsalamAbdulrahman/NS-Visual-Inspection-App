<script lang="ts">
    import { cn } from '@/lib/utils';

    type Props = {
        /** Tile size in px (designs use 40, 44, 56, 64, 72). */
        size?: number;
        /** Draw the hairline ring used on light surfaces. */
        ring?: boolean;
        class?: string;
    };

    let { size = 40, ring = true, class: className }: Props = $props();

    // Corner radius scales with the tile like the designs (40→11, 56→14, 72→18).
    const radius = $derived(Math.round(size * 0.25));
</script>

<span
    class={cn(
        'inline-flex flex-none items-center justify-center overflow-hidden bg-white',
        ring && 'shadow-[0_0_0_1px_var(--line)]',
        className,
    )}
    style:width="{size}px"
    style:height="{size}px"
    style:border-radius="{radius}px"
>
    <img
        src="/images/ke-logo.png"
        alt="Kaduna Electric"
        width={size}
        height={size}
        class="size-full object-contain"
    />
</span>
