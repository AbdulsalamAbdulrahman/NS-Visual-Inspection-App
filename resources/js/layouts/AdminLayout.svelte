<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import type { Component, Snippet } from 'svelte';
    import Avatar from '@/components/Avatar.svelte';
    import Logo from '@/components/Logo.svelte';
    import Toaster from '@/components/Toaster.svelte';
    import UserMenu from '@/components/UserMenu.svelte';
    import { APP_TITLE } from '@/lib/brand';
    import { currentPath } from '@/lib/currentUrl.svelte';
    import Assignment from '~icons/ms/assignment';
    import Badge from '~icons/ms/badge';
    import Engineering from '~icons/ms/engineering';
    import MapIcon from '~icons/ms/map';
    import Menu from '~icons/ms/menu';
    import Payments from '~icons/ms/payments';
    import SpaceDashboard from '~icons/ms/space-dashboard';
    import Tune from '~icons/ms/tune';
    import UnfoldMore from '~icons/ms/unfold-more';
    import WorkspacePremium from '~icons/ms/workspace-premium';

    let { children }: { children: Snippet } = $props();

    type NavItem = {
        label: string;
        href: string;
        icon: Component;
        countKey?: 'inspections' | 'contractors' | 'reps' | 'areas';
        exact?: boolean;
    };

    const user = $derived(page.props.auth.user!);
    /** Sidebar counts, shared only on admin responses. */
    const counts = $derived(
        (page.props.adminNav as Partial<Record<string, number>> | undefined) ?? {},
    );
    const current = currentPath();
    /** Reports waiting for NSD review: shown instead of the total so the queue stands out. */
    const pendingReview = $derived(counts.pendingReview ?? 0);

    const sidebar: NavItem[] = [
        { label: 'Overview', href: '/admin', icon: SpaceDashboard, exact: true },
        { label: 'Inspections', href: '/admin/inspections', icon: Assignment, countKey: 'inspections' },
        { label: 'Contractors', href: '/admin/contractors', icon: Engineering, countKey: 'contractors' },
        { label: 'Service reps', href: '/admin/reps', icon: Badge, countKey: 'reps' },
        { label: 'Service areas', href: '/admin/areas', icon: MapIcon, countKey: 'areas' },
        { label: 'Payments', href: '/admin/payments', icon: Payments },
        { label: 'Fee settings', href: '/admin/fees', icon: Tune },
        { label: 'Certificate', href: '/admin/certificate', icon: WorkspacePremium },
    ];

    // Phone bottom tabs (AM-01). Everything else lives under "More" (AM-08).
    const tabs: NavItem[] = [
        sidebar[0],
        sidebar[1],
        sidebar[2],
        { label: 'More', href: '/admin/more', icon: Menu },
    ];

    function tabActive(item: NavItem): boolean {
        if (item.href === '/admin/more') {
            return (
                current.isActive('/admin/more') ||
                current.isActive('/profile') ||
                sidebar.slice(3).some((s) => current.isActive(s.href))
            );
        }

        return current.isActive(item.href, item.exact);
    }
</script>

<div class="min-h-dvh bg-bg lg:grid lg:grid-cols-[248px_1fr]">
    <!-- Desktop sidebar ("Admin Sidebar" design) -->
    <aside
        class="sticky top-0 hidden h-dvh flex-col gap-1 border-r border-line bg-sf px-3.5 py-5 lg:flex"
    >
        <div class="flex items-center gap-2.5 px-2 pb-5">
            <Logo size={40} />
            <div class="flex min-w-0 flex-col">
                <b class="text-[15px]">Kaduna Electric</b>
                <span class="text-xs leading-snug text-mut">{APP_TITLE} · Admin</span>
            </div>
        </div>

        <nav class="flex flex-col gap-1" aria-label="Admin">
            {#each sidebar as item (item.href)}
                {@const active = current.isActive(item.href, item.exact)}
                {@const count = item.countKey ? counts[item.countKey] : undefined}
                <Link
                    href={item.href}
                    aria-current={active ? 'page' : undefined}
                    class={[
                        'flex h-11 items-center gap-3 rounded-[10px] px-3 text-[15px] no-underline',
                        active
                            ? 'bg-soft font-bold text-brand'
                            : 'font-medium text-ink hover:bg-sf2',
                    ]}
                >
                    <item.icon class="size-[21px] flex-none" />
                    <span class="flex-1">{item.label}</span>
                    {#if item.countKey === 'inspections' && pendingReview > 0}
                        <span class="rounded-full bg-acc px-2 py-0.5 font-mono text-xs font-semibold text-[#062012]" title="{pendingReview} waiting for review">{pendingReview} to review</span>
                    {:else if count !== undefined}
                        <span class="font-mono text-xs font-medium text-mut">{count}</span>
                    {/if}
                </Link>
            {/each}
        </nav>

        <UserMenu
            {user}
            side="top"
            align="start"
            showProfile
            triggerClass="mt-auto flex w-full items-center gap-2.5 rounded-xl bg-sf2 p-3 text-left"
        >
            {#snippet trigger()}
                <Avatar initials={user.initials} size={36} />
                <span class="flex min-w-0 flex-1 flex-col">
                    <b class="truncate text-sm">{user.name}</b>
                    <span class="text-xs text-mut">{user.subtitle}</span>
                </span>
                <UnfoldMore class="size-5 flex-none text-mut" />
            {/snippet}
        </UserMenu>
    </aside>

    <div class="flex min-h-dvh min-w-0 flex-col pb-[calc(62px+env(safe-area-inset-bottom))] lg:pb-0">
        {@render children()}
    </div>

    <!-- Phone bottom tabs -->
    <nav
        class="pb-safe fixed inset-x-0 bottom-0 z-30 border-t border-line bg-sf lg:hidden"
        aria-label="Admin"
    >
        <div class="grid h-[62px] grid-cols-4 text-xs font-semibold text-mut">
            {#each tabs as item (item.href)}
                {@const active = tabActive(item)}
                <Link
                    href={item.href}
                    aria-current={active ? 'page' : undefined}
                    class={[
                        'flex flex-col items-center justify-center gap-[3px] no-underline',
                        active ? 'font-extrabold text-brand' : 'text-mut',
                    ]}
                >
                    <span
                        class={[
                            'relative flex h-[30px] items-center justify-center rounded-full',
                            active && 'w-14 bg-soft',
                        ]}
                    >
                        <item.icon class="size-[22px]" />
                        {#if item.countKey === 'inspections' && pendingReview > 0}
                            <span class="absolute -top-1 left-[calc(50%+4px)] min-w-[18px] rounded-full bg-acc px-1 text-center font-mono text-[11px] leading-[18px] font-semibold text-[#062012]">
                                {pendingReview}<span class="sr-only"> to review</span>
                            </span>
                        {/if}
                    </span>
                    {item.label}
                </Link>
            {/each}
        </div>
    </nav>

    <Toaster />
</div>
