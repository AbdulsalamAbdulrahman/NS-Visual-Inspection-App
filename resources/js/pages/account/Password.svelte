<script lang="ts" module>
    export { roleLayout as layout } from '@/lib/roleLayout';
</script>

<script lang="ts">
    import { Form, page } from '@inertiajs/svelte';
    import BottomBar from '@/components/BottomBar.svelte';
    import Button from '@/components/Button.svelte';
    import Field from '@/components/form/Field.svelte';
    import NewPasswordFields, { passwordReady } from '@/components/form/NewPasswordFields.svelte';
    import PasswordInput from '@/components/form/PasswordInput.svelte';
    import MobileHeader from '@/components/MobileHeader.svelte';
    import { shellClasses } from '@/lib/breakpoints';
    import { change } from '@/routes/password';
    import { show as profile } from '@/routes/profile';

    const shell = $derived(shellClasses(page.props.auth.user?.role));

    let password = $state('');
    let confirmation = $state('');
</script>

<svelte:head>
    <title>Change password · KENS</title>
</svelte:head>

<MobileHeader title="Change password" backHref={profile().url} class={shell.mobileOnly} />

<Form
    {...change.form()}
    resetOnSuccess
    onSuccess={() => {
        password = '';
        confirmation = '';
    }}
    class="flex flex-1 flex-col"
>
    {#snippet children({ errors, processing })}
        <div class="mx-auto flex w-full max-w-[560px] flex-1 flex-col gap-[18px] px-4 py-5 lg:py-10">
            <h1 class={[shell.desktopOnly, 'text-[28px] font-extrabold']}>Change password</h1>

            <Field id="current_password" label="Current password" error={errors.current_password}>
                <PasswordInput
                    id="current_password"
                    name="current_password"
                    autocomplete="current-password"
                    invalid={!!errors.current_password}
                    required
                />
            </Field>

            <NewPasswordFields bind:password bind:confirmation {errors} />

            <Button
                type="submit"
                class={['mt-2 self-start', shell.desktopFlex]}
                size="md"
                disabled={processing || !passwordReady(password, confirmation)}
            >
                Save new password
            </Button>
        </div>

        <BottomBar class={shell.mobileOnly}>
            <Button type="submit" block disabled={processing || !passwordReady(password, confirmation)}>
                Save new password
            </Button>
        </BottomBar>
    {/snippet}
</Form>
