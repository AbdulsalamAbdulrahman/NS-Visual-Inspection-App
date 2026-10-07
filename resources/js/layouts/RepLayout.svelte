<script lang="ts">
    import { APP_TITLE } from '@/lib/brand';
    import { page } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import Avatar from '@/components/Avatar.svelte';
    import Logo from '@/components/Logo.svelte';
    import Toaster from '@/components/Toaster.svelte';
    import UserMenu from '@/components/UserMenu.svelte';
    import ExpandMore from '~icons/ms/expand-more';

    let { children }: { children: Snippet } = $props();

    const user = $derived(page.props.auth.user!);
</script>

<div class="flex min-h-dvh flex-col bg-bg">
    <!-- Tablet/desktop header (SR-D1, SR-T1). Phones use the page's MobileHeader. -->
    <header
        class="sticky top-0 z-30 hidden h-[68px] flex-none items-center gap-3.5 border-b border-line bg-sf px-8 md:flex"
    >
        <Logo size={40} />
        <div class="flex flex-col">
            <b class="text-base">Kaduna Electric</b>
            <span class="text-xs text-mut">{APP_TITLE} · Service rep</span>
        </div>
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

    <Toaster />
</div>
