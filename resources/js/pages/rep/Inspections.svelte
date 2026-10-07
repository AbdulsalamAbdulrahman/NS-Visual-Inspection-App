<script lang="ts" module>
    export { default as layout } from '@/layouts/RepLayout.svelte';
</script>

<script lang="ts">
    import { page } from '@inertiajs/svelte';
    import Avatar from '@/components/Avatar.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import MobileHeader from '@/components/MobileHeader.svelte';
    import UserMenu from '@/components/UserMenu.svelte';
    import Inbox from '~icons/ms/inbox';

    const user = $derived(page.props.auth.user!);

    const areaList = $derived(
        user.areas.length > 1
            ? `${user.areas.slice(0, -1).join(', ')} or ${user.areas.at(-1)}`
            : (user.areas[0] ?? 'your areas'),
    );
</script>

<svelte:head>
    <title>Inspections · KENS</title>
</svelte:head>

<MobileHeader title="Inspections" subtitle={user.name} class="md:hidden">
    {#snippet trailing()}
        <UserMenu {user} triggerClass="rounded-full">
            {#snippet trigger()}
                <Avatar initials={user.initials} />
            {/snippet}
        </UserMenu>
    {/snippet}
</MobileHeader>

<EmptyState
    icon={Inbox}
    tone="muted"
    title="No submissions in {areaList} yet"
    body="Paid inspections for your areas will appear here as soon as contractors submit them."
>
    <span class="mt-1.5 text-sm text-mut">Wrong areas? Ask the NSD admin to update your assignment.</span>
</EmptyState>
