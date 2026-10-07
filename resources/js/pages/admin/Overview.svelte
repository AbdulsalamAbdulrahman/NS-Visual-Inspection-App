<script lang="ts" module>
    export { default as layout } from '@/layouts/AdminLayout.svelte';
</script>

<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import Avatar from '@/components/Avatar.svelte';
    import MobileHeader from '@/components/MobileHeader.svelte';
    import UserMenu from '@/components/UserMenu.svelte';

    const user = $derived(page.props.auth.user!);
</script>

<svelte:head>
    <title>Overview · KENS</title>
</svelte:head>

<MobileHeader title="Overview" subtitle="All areas" class="lg:hidden">
    {#snippet trailing()}
        <UserMenu {user} showProfile triggerClass="rounded-full">
            {#snippet trigger()}
                <Avatar initials={user.initials} />
            {/snippet}
        </UserMenu>
    {/snippet}
</MobileHeader>

<div class="flex flex-col gap-6 p-4 lg:px-10 lg:py-8">
    <h1 class="hidden text-[28px] font-extrabold lg:block">Overview</h1>
</div>
