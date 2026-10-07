<script lang="ts" module>
    export type ButtonVariant = 'primary' | 'outline' | 'soft' | 'danger' | 'ghost';
    /**
     * xl: 60 px thumb-zone CTA · lg: 56 px contractor field control ·
     * md: 52 px desktop · sm: 44 px card action · xs: 40 px compact desktop.
     */
    export type ButtonSize = 'xl' | 'lg' | 'md' | 'sm' | 'xs';
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import type { Method } from '@inertiajs/core';
    import type { Snippet } from 'svelte';
    import type { ClassValue } from 'clsx';
    import type { HTMLButtonAttributes } from 'svelte/elements';
    import { cn } from '@/lib/utils';

    type Props = Omit<HTMLButtonAttributes, 'class'> & {
        variant?: ButtonVariant;
        size?: ButtonSize;
        /** Renders an Inertia Link instead of a button. */
        href?: string;
        /** With href: a plain link opening in a new tab (Google Maps, files). */
        external?: boolean;
        method?: Method;
        block?: boolean;
        class?: ClassValue;
        children: Snippet;
    };

    let {
        variant = 'primary',
        size = 'lg',
        href,
        external = false,
        method,
        block = false,
        class: className,
        children,
        type = 'button',
        ...rest
    }: Props = $props();

    const sizes: Record<ButtonSize, string> = {
        xl: 'h-[60px] rounded-2xl px-6 text-[18px] font-extrabold gap-2 [&_svg]:size-[26px]',
        lg: 'h-14 rounded-[14px] px-5 text-[17px] font-bold gap-2 [&_svg]:size-[22px]',
        md: 'h-[52px] rounded-xl px-5 text-base font-bold gap-2 [&_svg]:size-[22px]',
        sm: 'h-11 rounded-xl px-4 text-[15px] font-bold gap-1 [&_svg]:size-5',
        xs: 'h-10 rounded-[10px] px-3.5 text-sm font-bold gap-1 [&_svg]:size-[18px]',
    };

    const variants: Record<ButtonVariant, string> = {
        primary:
            'bg-pri text-on-pri hover:brightness-110 disabled:bg-sf2 disabled:text-mut disabled:hover:brightness-100',
        outline: 'border-2 border-line text-ink hover:bg-sf2',
        soft: 'bg-sf2 text-ink hover:brightness-95 dark:hover:brightness-125',
        danger: 'border-2 border-line text-bad hover:bg-bad-bg',
        ghost: 'text-ink hover:bg-sf2',
    };

    const classes = $derived(
        cn(
            'inline-flex select-none items-center justify-center whitespace-nowrap no-underline transition-[filter,background-color] disabled:cursor-not-allowed',
            sizes[size],
            variants[variant],
            block && 'w-full',
            className,
        ),
    );
</script>

{#if href && external}
    <a {href} target="_blank" rel="noopener noreferrer" class={classes}>
        {@render children()}
    </a>
{:else if href}
    <Link {href} {method} as={method && method !== 'get' ? 'button' : 'a'} class={classes}>
        {@render children()}
    </Link>
{:else}
    <button {type} class={classes} {...rest}>
        {@render children()}
    </button>
{/if}
