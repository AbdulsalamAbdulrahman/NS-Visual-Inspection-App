<script lang="ts">
    import type { Component, Snippet } from 'svelte';
    import { cn } from '@/lib/utils';

    type Props = {
        icon: Component<{ class?: string }>;
        title: string;
        body?: string;
        /** "brand" = soft green tile (CH-02); "muted" = grey tile (SR-M2). */
        tone?: 'brand' | 'muted';
        children?: Snippet;
        class?: string;
    };

    let { icon: Icon, title, body, tone = 'brand', children, class: className }: Props = $props();
</script>

<div class={cn('flex flex-1 flex-col items-center justify-center gap-4 p-6 text-center', className)}>
    <div
        class={[
            'flex size-[88px] items-center justify-center rounded-3xl',
            tone === 'brand' ? 'bg-soft text-brand' : 'bg-sf2 text-mut',
        ]}
    >
        <Icon class="size-11" />
    </div>
    <b class="text-[22px] leading-tight text-balance">{title}</b>
    {#if body}
        <p class="max-w-[300px] text-base leading-normal text-pretty text-mut">{body}</p>
    {/if}
    {@render children?.()}
</div>
