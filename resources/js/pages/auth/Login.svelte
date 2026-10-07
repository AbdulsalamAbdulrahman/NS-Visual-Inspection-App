<script lang="ts">
    import { Form, Link, page } from '@inertiajs/svelte';
    import Button from '@/components/Button.svelte';
    import Field from '@/components/form/Field.svelte';
    import PasswordInput from '@/components/form/PasswordInput.svelte';
    import TextInput from '@/components/form/TextInput.svelte';
    import AuthShell from '@/layouts/AuthShell.svelte';
    import { store } from '@/routes/login';
    import { request } from '@/routes/password';
    import Call from '~icons/ms/call';
    import GppBad from '~icons/ms/gpp-bad';

    type Props = {
        status?: string | null;
        /** Set when a suspended user was signed out mid-session. */
        suspended?: boolean;
    };

    let { status = null, suspended = false }: Props = $props();

    const nsdTel = $derived(`tel:${page.props.nsd.phone.replace(/[^\d+]/g, '')}`);
</script>

<svelte:head>
    <title>Sign in · KENS</title>
</svelte:head>

<Form {...store.form()} resetOnFailure={['password']} class="contents">
    {#snippet children({ errors, processing })}
        {@const isSuspended = suspended || !!errors.suspended}
        <AuthShell variant={isSuspended ? 'plain' : 'hero'}>
            <div class="flex flex-col gap-1.5 lg:gap-2">
                <h1 class="text-[26px] font-extrabold lg:text-[32px]">Sign in</h1>
                {#if !isSuspended}
                    <p class="text-base leading-[1.45] text-mut">
                        Use the login details emailed to you by Kaduna Electric.
                    </p>
                {/if}
            </div>

            {#if isSuspended}
                <!-- AU-05 -->
                <div role="alert" class="flex items-start gap-3 rounded-[14px] bg-bad-bg p-4 text-bad">
                    <GppBad class="size-[26px] flex-none" />
                    <div class="flex flex-col gap-1.5 text-ink">
                        <b class="text-base text-bad">This account is suspended</b>
                        <span class="text-[15px] leading-[1.45]">
                            You can't sign in, start inspections or pay fees until the New Service Department
                            reactivates your account.
                        </span>
                        <a href={nsdTel} class="mt-0.5 flex items-center gap-1.5 text-[15px] font-bold text-bad no-underline hover:underline">
                            <Call class="size-[18px]" />Contact NSD admin
                        </a>
                    </div>
                </div>
            {/if}

            {#if status}
                <div class="rounded-[14px] bg-ok-bg px-4 py-3 text-[15px] font-semibold text-ok" role="status">
                    {status}
                </div>
            {/if}

            <Field id="email" label="Email" error={errors.email}>
                <TextInput
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    inputmode="email"
                    placeholder="name@company.ng"
                    required
                    invalid={!!errors.email}
                    class="lg:h-[52px] lg:text-base"
                />
            </Field>

            <Field id="password" label="Password" error={errors.password}>
                {#snippet labelAside()}
                    <Link href={request()} class="hidden text-sm font-bold no-underline hover:underline lg:inline">
                        Forgot password?
                    </Link>
                {/snippet}
                <PasswordInput
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    placeholder="Password"
                    required
                    invalid={!!errors.password}
                    class="lg:h-[52px] lg:text-base"
                />
            </Field>

            <input type="hidden" name="remember" value="1" />

            <Link
                href={request()}
                class="self-start py-1.5 text-[15px] font-bold no-underline hover:underline lg:hidden"
            >
                Forgot password?
            </Link>

            {#snippet actions()}
                <Button type="submit" size="lg" block disabled={processing} class="lg:h-[52px] lg:rounded-xl lg:text-base">
                    {processing ? 'Signing in…' : 'Sign in'}
                </Button>
                <p class="text-center text-[13px] text-mut lg:text-left lg:text-sm">
                    <span class="lg:hidden">Need an account? Contact the New Service Department.</span>
                    <span class="hidden lg:inline">
                        Accounts are created by the New Service Department. Need access? Contact your admin.
                    </span>
                </p>
            {/snippet}
        </AuthShell>
    {/snippet}
</Form>
