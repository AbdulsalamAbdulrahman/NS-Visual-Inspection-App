<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import Button from '@/components/Button.svelte';
    import ContractorDocuments, { type ContractorDocumentsData } from '@/components/account/ContractorDocuments.svelte';
    import AuthShell from '@/layouts/AuthShell.svelte';
    import { signOut } from '@/lib/offline/signout';
    import { home } from '@/routes';

    type Props = { firstName: string; profile: { documents: ContractorDocumentsData } };

    let { firstName, profile }: Props = $props();

    const complete = $derived(profile.documents.status.missing.length === 0);
</script>

<svelte:head>
    <title>Complete your profile · KENS</title>
</svelte:head>

<AuthShell eyebrow="First sign-in">
    <div class="flex flex-col gap-2">
        <h1 class="text-[26px] leading-[1.15] font-extrabold lg:text-[32px]">Complete your profile</h1>
        <p class="text-base leading-[1.45] text-mut">
            Welcome, {firstName}. Before you can take jobs, add your licence copy and dates, passport photograph and signature.
        </p>
    </div>

    <ContractorDocuments documents={profile.documents} />

    {#snippet actions()}
        <Button size="xl" block disabled={!complete} onclick={() => router.visit(home.url())}>Continue</Button>
        <button type="button" class="min-h-14 text-base font-semibold text-mut" onclick={signOut}>Sign out</button>
    {/snippet}
</AuthShell>
