<script lang="ts" module>
    export { default as layout } from '@/layouts/AdminLayout.svelte';
</script>

<script lang="ts">
    import { Link, page, router } from '@inertiajs/svelte';
    import type { Component } from 'svelte';
    import { signOut } from '@/lib/offline/signout';
    import { show as profile } from '@/routes/profile';
    import BadgeIcon from '~icons/ms/badge';
    import ChevronRight from '~icons/ms/chevron-right';
    import Logout from '~icons/ms/logout';
    import MapIcon from '~icons/ms/map';
    import Payments from '~icons/ms/payments';
    import Person from '~icons/ms/person';
    import Tune from '~icons/ms/tune';
    import WorkspacePremium from '~icons/ms/workspace-premium';

    type Item = { label: string; href: string; icon: Component; meta?: string | number };

    /** Sidebar counts and the current fee label, shared on admin responses. */
    const nav = $derived(
        (page.props.adminNav as { reps?: number; areas?: number; fee?: string } | undefined) ?? {},
    );

    const manage: Item[] = $derived([
        { label: 'Service reps', href: '/admin/reps', icon: BadgeIcon, meta: nav.reps },
        { label: 'Service areas', href: '/admin/areas', icon: MapIcon, meta: nav.areas },
        { label: 'Payments', href: '/admin/payments', icon: Payments },
        { label: 'Fee settings', href: '/admin/fees', icon: Tune, meta: nav.fee },
        { label: 'Certificate signatory', href: '/admin/certificate', icon: WorkspacePremium },
    ]);
</script>

<svelte:head>
    <title>More · KENS</title>
</svelte:head>

<!-- AM-08: phone-only menu for the sections that don't fit in the bottom tabs. -->
<header class="sticky top-0 z-20 border-b border-line bg-sf px-4 pt-[calc(12px+env(safe-area-inset-top))] pb-3.5">
    <h1 class="text-[20px] font-bold">More</h1>
</header>

<div class="flex flex-col gap-3.5 p-4">
    <nav class="flex flex-col rounded-2xl border border-line bg-sf" aria-label="Manage">
        {#each manage as item (item.href)}
            <Link
                href={item.href}
                class="flex h-[60px] items-center gap-3.5 border-b border-line pr-3 pl-4 text-ink no-underline last:border-b-0 hover:bg-sf2"
            >
                <item.icon class="size-[22px] text-brand" />
                <b class="flex-1 text-base">{item.label}</b>
                {#if item.meta !== undefined}
                    <span class="font-mono text-[13px] font-medium text-mut">{item.meta}</span>
                {/if}
                <ChevronRight class="size-[22px] text-mut" />
            </Link>
        {/each}
    </nav>

    <div class="flex flex-col rounded-2xl border border-line bg-sf">
        <Link
            href={profile()}
            class="flex h-[60px] items-center gap-3.5 border-b border-line pr-3 pl-4 text-ink no-underline hover:bg-sf2"
        >
            <Person class="size-[22px] text-mut" />
            <b class="flex-1 text-base">Profile &amp; password</b>
            <ChevronRight class="size-[22px] text-mut" />
        </Link>
        <button
            type="button"
            class="flex h-[60px] items-center gap-3.5 pr-3 pl-4 text-left text-bad hover:bg-bad-bg"
            onclick={() => signOut()}
        >
            <Logout class="size-[22px]" />
            <b class="flex-1 text-base">Sign out</b>
        </button>
    </div>
</div>
