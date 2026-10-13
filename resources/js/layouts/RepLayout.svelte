<script lang="ts">
    import { APP_TITLE } from '@/lib/brand';
    import { Link, page } from '@inertiajs/svelte';
    import type { Component, Snippet } from 'svelte';
    import Avatar from '@/components/Avatar.svelte';
    import Logo from '@/components/Logo.svelte';
    import Toaster from '@/components/Toaster.svelte';
    import UserMenu from '@/components/UserMenu.svelte';
    import { currentPath } from '@/lib/currentUrl.svelte';
    import AssignmentInd from '~icons/ms/assignment-ind';
    import ExpandMore from '~icons/ms/expand-more';
    import Inventory from '~icons/ms/inventory-2';

    let { children }: { children: Snippet } = $props();

    const user = $derived(page.props.auth.user!);
    const current = currentPath();

    const nav: { label: string; href: string; icon: Component }[] = [
        { label: 'Jobs', href: '/rep/jobs', icon: AssignmentInd },
        { label: 'Inspections', href: '/rep/inspections', icon: Inventory },
    ];
</script>

<div class="flex min-h-dvh flex-col bg-bg pb-[calc(62px+env(safe-area-inset-bottom))] md:pb-0">
    <!-- Tablet/desktop header (SR-D1, SR-T1). Phones use the page's MobileHeader. -->
    <header
        class="sticky top-0 z-30 hidden h-[68px] flex-none items-center gap-3.5 border-b border-line bg-sf px-8 md:flex"
    >
        <Logo size={40} />
        <div class="flex flex-col">
            <b class="text-base">Kaduna Electric</b>
            <span class="text-xs text-mut">{APP_TITLE} · Service rep</span>
        </div>
        <nav class="ml-6 flex h-full items-stretch gap-1" aria-label="Service rep">
            {#each nav as item (item.href)}
                {@const active = current.isActive(item.href)}
                <Link
                    href={item.href}
                    aria-current={active ? 'page' : undefined}
                    class={[
                        'flex items-center gap-2 border-b-[3px] px-3 text-[15px] no-underline',
                        active ? 'border-brand font-bold text-ink' : 'border-transparent font-semibold text-mut hover:text-ink',
                    ]}
                >
                    <item.icon class="size-5" />{item.label}
                </Link>
            {/each}
        </nav>
        <UserMenu
            {user}
            triggerClass="ml-auto flex items-center gap-2.5 rounded-full border border-line py-1 pr-2 pl-1 hover:bg-sf2"
        >
            {#snippet trigger()}
                <Avatar initials={user.initials} size={36} />
                <b class="text-sm">{user.name}</b>
                <ExpandMore class="size-5 text-mut" />
            {/snippet}
        </UserMenu>
    </header>

    <main class="flex flex-1 flex-col">
        {@render children()}
    </main>

    <!-- Phone bottom tabs -->
    <nav class="pb-safe fixed inset-x-0 bottom-0 z-30 border-t border-line bg-sf md:hidden" aria-label="Service rep">
        <div class="grid h-[62px] grid-cols-2 text-xs font-semibold">
            {#each nav as item (item.href)}
                {@const active = current.isActive(item.href)}
                <Link
                    href={item.href}
                    aria-current={active ? 'page' : undefined}
                    class={['flex flex-col items-center justify-center gap-[3px] no-underline', active ? 'font-extrabold text-brand' : 'text-mut']}
                >
                    <span class={['flex h-[30px] items-center justify-center rounded-full', active && 'w-14 bg-soft']}>
                        <item.icon class="size-[22px]" />
                    </span>
                    {item.label}
                </Link>
            {/each}
        </div>
    </nav>

    <Toaster />
</div>
