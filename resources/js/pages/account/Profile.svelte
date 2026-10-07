<script lang="ts" module>
    export { roleLayout as layout } from '@/lib/roleLayout';
</script>

<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import Avatar from '@/components/Avatar.svelte';
    import Button from '@/components/Button.svelte';
    import MobileHeader from '@/components/MobileHeader.svelte';
    import ThemeSwitcher from '@/components/ThemeSwitcher.svelte';
    import { shellClasses } from '@/lib/breakpoints';
    import { home, logout } from '@/routes';
    import admin from '@/routes/admin';
    import { edit as editPassword } from '@/routes/password';
    import BadgeIcon from '~icons/ms/badge';
    import ChevronRight from '~icons/ms/chevron-right';
    import Contrast from '~icons/ms/contrast';
    import Key from '~icons/ms/key';
    import Logout from '~icons/ms/logout';
    import MapIcon from '~icons/ms/map';

    type Profile = {
        name: string;
        email: string;
        phone: string | null;
        initials: string;
        role: 'admin' | 'contractor' | 'rep';
        roleLabel: string;
        licence: {
            category: string;
            regNo: string;
            corenNo: string | null;
            firmName: string | null;
        } | null;
        areas: string[];
    };

    let { profile }: { profile: Profile } = $props();

    const shell = $derived(shellClasses(profile.role));

    const licenceRows = $derived(
        profile.licence
            ? [
                  { label: 'NEMSA category', value: profile.licence.category, mono: false },
                  { label: 'NEMSA reg. no.', value: profile.licence.regNo, mono: true },
                  { label: 'COREN no.', value: profile.licence.corenNo, mono: true },
                  { label: 'Firm', value: profile.licence.firmName, mono: false },
              ].filter((row) => row.value)
            : [],
    );
</script>

<svelte:head>
    <title>Profile · KENS</title>
</svelte:head>

<!-- Admins reach Profile from the More tab (AM-08), so back returns there. -->
<MobileHeader
    title="Profile"
    backHref={profile.role === 'admin' ? admin.more.url() : home().url}
    class={shell.mobileOnly}
/>

<div class="mx-auto flex w-full max-w-[640px] flex-col gap-4 px-4 py-5 lg:py-10">
    <h1 class={[shell.desktopOnly, 'text-[28px] font-extrabold']}>Profile</h1>

    <div class="flex items-center gap-3.5 px-1">
        <Avatar initials={profile.initials} size={64} />
        <div class="flex min-w-0 flex-col gap-0.5">
            <b class="text-[20px]">{profile.name}</b>
            <span class="truncate text-sm text-mut">{profile.email}</span>
            {#if profile.phone}
                <span class="font-mono text-sm font-medium text-mut">{profile.phone}</span>
            {/if}
        </div>
    </div>

    {#if profile.licence}
        <section class="flex flex-col rounded-2xl border border-line bg-sf" aria-labelledby="licence-heading">
            <h2
                id="licence-heading"
                class="mono-caps flex items-center gap-1.5 border-b border-line px-4 py-3 tracking-[0.05em] text-mut"
            >
                <BadgeIcon class="size-4" />Licence
            </h2>
            <dl>
                {#each licenceRows as row (row.label)}
                    <div class="flex justify-between gap-4 border-b border-line px-4 py-3 last:border-b-0">
                        <dt class="text-mut">{row.label}</dt>
                        <dd class={row.mono ? 'font-mono text-[15px] font-medium' : 'text-right font-bold'}>
                            {row.value}
                        </dd>
                    </div>
                {/each}
            </dl>
        </section>
        <p class="px-1 text-sm leading-[1.45] text-mut">
            Licence details are managed by Kaduna Electric. Contact the New Service Department to update them.
        </p>
    {/if}

    {#if profile.areas.length > 0}
        <section class="flex flex-col gap-2.5 rounded-2xl border border-line bg-sf p-4" aria-label="Service areas">
            <h2 class="mono-caps flex items-center gap-1.5 text-mut"><MapIcon class="size-4" />Service areas</h2>
            <div class="flex flex-wrap gap-1.5">
                {#each profile.areas as area (area)}
                    <span class="rounded-full bg-soft px-2.5 py-1 text-[13px] font-bold text-brand">{area}</span>
                {/each}
            </div>
            <p class="text-sm text-mut">Wrong areas? Ask the NSD admin to update your assignment.</p>
        </section>
    {/if}

    <div class="flex flex-col rounded-2xl border border-line bg-sf">
        <Link
            href={editPassword()}
            class="flex h-14 items-center gap-3 border-b border-line pr-3 pl-4 text-ink no-underline hover:bg-sf2"
        >
            <Key class="size-[22px] text-mut" />
            <b class="flex-1 text-base">Change password</b>
            <ChevronRight class="size-[22px] text-mut" />
        </Link>
        <div class="flex items-center gap-3 py-2.5 pr-3 pl-4">
            <Contrast class="size-[22px] text-mut" />
            <b class="flex-1 text-base">Theme</b>
            <ThemeSwitcher />
        </div>
    </div>

    <Button variant="danger" block onclick={() => router.post(logout.url())}>
        <Logout />Sign out
    </Button>
</div>
