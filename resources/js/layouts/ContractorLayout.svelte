<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import Avatar from '@/components/Avatar.svelte';
    import Logo from '@/components/Logo.svelte';
    import Toaster from '@/components/Toaster.svelte';
    import { currentPath } from '@/lib/currentUrl.svelte';
    import inspections from '@/routes/inspections';
    import { show as profile } from '@/routes/profile';

    let { children }: { children: Snippet } = $props();

    const user = $derived(page.props.auth.user!);
    const current = currentPath();

    const nav = [
        { label: 'My inspections', href: inspections.index.url() },
        { label: 'Profile', href: profile.url() },
    ];
</script>

<div class="flex min-h-dvh flex-col bg-bg">
    <!-- Desktop top bar (CK-01). On phones each page draws its own MobileHeader. -->
    <header
        class="sticky top-0 z-30 hidden h-[68px] flex-none items-center gap-3.5 border-b border-line bg-sf px-8 lg:flex"
    >
        <Logo size={40} />
        <div class="flex flex-col">
            <b class="text-base">Kaduna Electric</b>
            <span class="text-xs text-mut">Visual Site Inspection</span>
        </div>
        <nav class="ml-10 flex gap-1 text-[15px] font-semibold" aria-label="Main">
            {#each nav as item (item.href)}
                {@const active = current.isActive(item.href)}
                <Link
                    href={item.href}
                    aria-current={active ? 'page' : undefined}
                    class={[
                        'rounded-[10px] px-3.5 py-2.5 no-underline',
                        active ? 'bg-soft text-brand' : 'text-mut hover:bg-sf2 hover:text-ink',
                    ]}
                >
                    {item.label}
                </Link>
            {/each}
        </nav>
        <Link href={profile()} class="ml-auto flex items-center gap-2.5 text-ink no-underline">
            <span class="flex flex-col items-end">
                <b class="text-sm">{user.name}</b>
                <span class="text-xs text-mut">{user.subtitle}</span>
            </span>
            <Avatar initials={user.initials} size={40} />
        </Link>
    </header>

    <main class="flex flex-1 flex-col">
        {@render children()}
    </main>

    <Toaster />
</div>
