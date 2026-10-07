<script lang="ts">
    import { cn } from '@/lib/utils';

    type Props = {
        initials: string;
        size?: 36 | 40 | 44 | 64;
        class?: string;
    };

    let { initials, size = 44, class: className }: Props = $props();

    const text = $derived(
        size === 64 ? 'text-[22px]' : size === 36 ? 'text-[13px]' : 'text-base',
    );
</script>

<span
    aria-hidden="true"
    class={cn(
        'inline-flex flex-none items-center justify-center rounded-full bg-soft font-extrabold text-brand',
        text,
        className,
    )}
    style:width="{size}px"
    style:height="{size}px"
>
    {initials}
</span>
