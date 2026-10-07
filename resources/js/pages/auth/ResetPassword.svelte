<script lang="ts">
    import { Form } from '@inertiajs/svelte';
    import Button from '@/components/Button.svelte';
    import NewPasswordFields, { passwordReady } from '@/components/form/NewPasswordFields.svelte';
    import AuthShell from '@/layouts/AuthShell.svelte';
    import { update } from '@/routes/password';

    let { email, token }: { email: string; token: string } = $props();

    let password = $state('');
    let confirmation = $state('');
</script>

<svelte:head>
    <title>Choose a new password · KENS</title>
</svelte:head>

<Form {...update.form()} transform={(data) => ({ ...data, token, email })} class="contents">
    {#snippet children({ errors, processing })}
        <AuthShell eyebrow="Reset password">
            <div class="flex flex-col gap-2">
                <h1 class="text-[26px] leading-[1.15] font-extrabold lg:text-[32px]">Choose a new password</h1>
                <p class="text-base leading-[1.45] text-mut">For <b class="text-ink">{email}</b></p>
            </div>

            {#if errors.email}
                <!-- Expired or already-used link -->
                <div role="alert" class="rounded-[14px] bg-bad-bg px-4 py-3 text-[15px] font-semibold text-bad">
                    {errors.email} Request a new link from the sign-in screen.
                </div>
            {/if}

            <NewPasswordFields bind:password bind:confirmation {errors} />

            {#snippet actions()}
                <Button
                    type="submit"
                    block
                    disabled={processing || !passwordReady(password, confirmation)}
                    class="lg:h-[52px] lg:rounded-xl lg:text-base"
                >
                    Save password
                </Button>
            {/snippet}
        </AuthShell>
    {/snippet}
</Form>
