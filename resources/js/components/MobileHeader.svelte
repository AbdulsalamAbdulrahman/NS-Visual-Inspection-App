<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import Logo from '@/components/Logo.svelte';
    import ArrowBack from '~icons/ms/arrow-back';
    import { cn } from '@/lib/utils';

    type Props = {
        title: string;
        subtitle?: string;
        /** When set, shows a back arrow instead of the logo (CH-03 style). */
        backHref?: string;
        /** Right-hand slot, usually the avatar / profile menu trigger. */
        trailing?: Snippet;
        /** Extra rows under the title (search field, area chips). */
        children?: Snippet;
        class?: string;
    };

    let { title, subtitle, backHref, trailing, children, class: className }: Props = $props();
</script>

<header
    class={cn(
        'sticky top-0 z-20 flex-none border-b border-line bg-sf pt-[env(safe-area-inset-top)]',
        className,
    )}
>
    {#if backHref}
        <div class="flex h-14 items-center gap-1 px-2">
            <Link
                href={backHref}
                class="flex size-12 items-center justify-center rounded-xl text-ink hover:bg-sf2"
                aria-label="Back"
            >
                <ArrowBack class="size-[22px]" />
            </Link>
            <h1 class="flex-1 truncate text-[18px] font-bold">{title}</h1>
            {@render trailing?.()}
        </div>
    {:else}
        <div class="flex items-center gap-3 px-4 pt-3 pb-3.5">
            <Logo size={40} />
            <div class="flex min-w-0 flex-1 flex-col">
                <h1 class="truncate text-[20px] leading-tight font-bold">{title}</h1>
                {#if subtitle}
                    <span class="truncate text-[13px] text-mut">{subtitle}</span>
                {/if}
            </div>
            {@render trailing?.()}
        </div>
    {/if}

    {#if children}
        <div class="flex flex-col gap-3 px-4 pb-3.5">
            {@render children()}
        </div>
    {/if}
</header>
