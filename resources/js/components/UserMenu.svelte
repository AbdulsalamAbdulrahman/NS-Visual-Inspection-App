<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { DropdownMenu } from 'bits-ui';
    import type { Snippet } from 'svelte';
    import { appearanceLabels, themeState } from '@/lib/theme.svelte';
    import { logout } from '@/routes';
    import { edit as editPassword } from '@/routes/password';
    import { show as profile } from '@/routes/profile';
    import Contrast from '~icons/ms/contrast';
    import Key from '~icons/ms/key';
    import Logout from '~icons/ms/logout';
    import Person from '~icons/ms/person';
    import type { Appearance, AuthUser } from '@/types';

    type Props = {
        user: AuthUser;
        /** The trigger content (avatar, pill, sidebar card). */
        trigger: Snippet;
        triggerClass?: string;
        align?: 'start' | 'end';
        side?: 'top' | 'bottom';
        /** Show a "Profile" entry (admin); reps only change password. */
        showProfile?: boolean;
    };

    let {
        user,
        trigger,
        triggerClass,
        align = 'end',
        side = 'bottom',
        showProfile = false,
    }: Props = $props();

    const theme = themeState();
    const next: Record<Appearance, Appearance> = {
        light: 'dark',
        dark: 'system',
        system: 'light',
    };

    const itemClass =
        'flex h-[50px] cursor-pointer items-center gap-3 rounded-[10px] px-3 outline-none data-highlighted:bg-sf2 [&_svg]:size-[22px]';
</script>

<DropdownMenu.Root>
    <DropdownMenu.Trigger class={triggerClass} aria-label="Account menu">
        {@render trigger()}
    </DropdownMenu.Trigger>
    <DropdownMenu.Portal>
        <DropdownMenu.Content
            {align}
            {side}
            sideOffset={8}
            class="z-50 w-[290px] overflow-hidden rounded-[18px] bg-sf text-ink shadow-[0_18px_50px_rgba(0,0,0,.25)] outline-none"
        >
            <div class="flex flex-col gap-2 border-b border-line p-4">
                <b class="text-base">{user.name}</b>
                <span class="truncate text-[13px] text-mut">{user.email}</span>
                {#if user.areas.length > 0}
                    <div class="flex flex-wrap gap-1.5">
                        {#each user.areas as area (area)}
                            <span class="rounded-full bg-soft px-[9px] py-[3px] text-xs font-bold text-brand">
                                {area}
                            </span>
                        {/each}
                    </div>
                {/if}
            </div>
            <div class="flex flex-col p-1.5 text-[15px] font-semibold">
                {#if showProfile}
                    <DropdownMenu.Item class={itemClass} onSelect={() => router.visit(profile.url())}>
                        <Person />Profile
                    </DropdownMenu.Item>
                {/if}
                <DropdownMenu.Item class={itemClass} onSelect={() => router.visit(editPassword.url())}>
                    <Key />Change password
                </DropdownMenu.Item>
                <DropdownMenu.Item
                    class={itemClass}
                    closeOnSelect={false}
                    onSelect={() => theme.update(next[theme.appearance])}
                >
                    <Contrast />Theme: {appearanceLabels[theme.appearance]}
                </DropdownMenu.Item>
                <DropdownMenu.Item
                    class={[itemClass, 'text-bad']}
                    onSelect={() => router.post(logout.url())}
                >
                    <Logout />Sign out
                </DropdownMenu.Item>
            </div>
        </DropdownMenu.Content>
    </DropdownMenu.Portal>
</DropdownMenu.Root>
