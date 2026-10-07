<script lang="ts" module>
    export { default as layout } from '@/layouts/ContractorLayout.svelte';
</script>

<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import Avatar from '@/components/Avatar.svelte';
    import BottomBar from '@/components/BottomBar.svelte';
    import Button from '@/components/Button.svelte';
    import EmptyState from '@/components/EmptyState.svelte';
    import MobileHeader from '@/components/MobileHeader.svelte';
    import AddCircle from '~icons/ms/add-circle';
    import AssignmentAdd from '~icons/ms/assignment-add';

    const user = $derived(page.props.auth.user!);

    const steps = ['Fill sections A–D on site', 'Pay the inspection fee', 'Get your ticket number'];
</script>

<svelte:head>
    <title>My inspections · KENS</title>
</svelte:head>

<MobileHeader
    title="My inspections"
    subtitle={user.badge ? `${user.name} · ${user.badge}` : user.name}
    class="lg:hidden"
>
    {#snippet trailing()}
        <Link href="/profile" aria-label="Profile" class="rounded-full">
            <Avatar initials={user.initials} />
        </Link>
    {/snippet}
</MobileHeader>

<div class="mx-auto flex w-full max-w-[1344px] flex-1 flex-col lg:px-12 lg:py-8">
    <div class="hidden items-end gap-4 lg:flex">
        <div class="flex flex-col gap-1">
            <h1 class="text-[32px] font-extrabold tracking-[-0.02em]">My inspections</h1>
            <span class="text-[15px] text-mut">No inspections yet</span>
        </div>
        <Button size="md" class="ml-auto font-extrabold" href="#">
            <AddCircle />Start new inspection
        </Button>
    </div>

    <EmptyState
        icon={AssignmentAdd}
        title="No inspections yet"
        body="Start your first inspection at the property. Your progress saves as you go — even without network."
    >
        <ol class="mt-2 flex w-full max-w-[300px] flex-col gap-2 text-left">
            {#each steps as step, i (step)}
                <li class="flex items-center gap-2.5 text-[15px]">
                    <span class="flex size-7 flex-none items-center justify-center rounded-full bg-sf2 font-mono text-[13px] font-semibold">
                        {i + 1}
                    </span>
                    {step}
                </li>
            {/each}
        </ol>
    </EmptyState>
</div>

<BottomBar class="lg:hidden">
    <Button size="xl" block href="#"><AddCircle />Start new inspection</Button>
</BottomBar>
