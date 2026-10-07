<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import Button from '@/components/Button.svelte';
    import NewPasswordFields, { passwordReady } from '@/components/form/NewPasswordFields.svelte';
    import AuthShell from '@/layouts/AuthShell.svelte';
    import { update } from '@/routes/password/first';

    let { firstName }: { firstName: string } = $props();

    let password = $state('');
    let confirmation = $state('');
</script>

<svelte:head>
    <title>Set your password · KENS</title>
</svelte:head>

<Form {...update.form()} class="contents">
    {#snippet children({ errors, processing })}
        <AuthShell eyebrow="First sign-in">
            <div class="flex flex-col gap-2">
                <h1 class="text-[26px] leading-[1.15] font-extrabold lg:text-[32px]">
                    <span class="lg:hidden">Welcome, {firstName}.<br />Set your own password.</span>
                    <span class="hidden lg:inline">Set your own password</span>
                </h1>
                <p class="text-base leading-[1.45] text-mut">
                    <span class="lg:hidden">
                        The password in your email was temporary. Choose a new one to continue.
                    </span>
                    <span class="hidden lg:inline">
                        Welcome, {firstName}. The password in your email was temporary.
                    </span>
                </p>
            </div>

            <NewPasswordFields bind:password bind:confirmation {errors} />

            {#snippet actions()}
                <Button
                    type="submit"
                    block
                    disabled={processing || !passwordReady(password, confirmation)}
                    class="lg:h-[52px] lg:rounded-xl lg:text-base"
                >
                    Save and continue
                </Button>
            {/snippet}
        </AuthShell>
    {/snippet}
</Form>
