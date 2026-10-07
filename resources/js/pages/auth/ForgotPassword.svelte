<script lang="ts">
    import { Link, useForm } from '@inertiajs/svelte';
    import { onDestroy } from 'svelte';
    import Button from '@/components/Button.svelte';
    import Field from '@/components/form/Field.svelte';
    import TextInput from '@/components/form/TextInput.svelte';
    import NsdContactCard from '@/components/NsdContactCard.svelte';
    import AuthShell from '@/layouts/AuthShell.svelte';
    import { login } from '@/routes';
    import { email } from '@/routes/password';
    import ArrowBack from '~icons/ms/arrow-back';
    import MarkEmailRead from '~icons/ms/mark-email-read';
    import Refresh from '~icons/ms/refresh';

    const RESEND_AFTER = 60;

    const form = useForm({ email: '' });

    let sentTo = $state<string | null>(null);
    let secondsLeft = $state(0);
    let timer: ReturnType<typeof setInterval> | undefined;

    const countdown = $derived(`${Math.floor(secondsLeft / 60)}:${String(secondsLeft % 60).padStart(2, '0')}`);

    function startCountdown(): void {
        clearInterval(timer);
        secondsLeft = RESEND_AFTER;
        timer = setInterval(() => {
            secondsLeft -= 1;

            if (secondsLeft <= 0) {
                clearInterval(timer);
            }
        }, 1000);
    }

    function send(event?: SubmitEvent): void {
        event?.preventDefault();
        form.post(email.url(), {
            preserveScroll: true,
            onSuccess: () => {
                sentTo = form.email;
                startCountdown();
            },
        });
    }

    onDestroy(() => clearInterval(timer));
</script>

<svelte:head>
    <title>Forgot password · KENS</title>
</svelte:head>

<form onsubmit={send} class="contents" novalidate>
    <AuthShell>
        {#snippet top()}
            <Link
                href={login()}
                class="flex size-12 items-center justify-center rounded-xl text-ink hover:bg-sf2"
                aria-label="Back to sign in"
            >
                <ArrowBack class="size-[22px]" />
            </Link>
        {/snippet}

        {#if sentTo}
            <div class="flex size-16 items-center justify-center rounded-[18px] bg-soft text-brand">
                <MarkEmailRead class="size-[34px]" />
            </div>
            <div class="flex flex-col gap-2" role="status">
                <h1 class="text-[26px] font-extrabold lg:text-[32px]">Check your inbox</h1>
                <p class="text-base leading-normal text-mut">
                    If <b class="text-ink">{sentTo}</b> has an account, a reset link is on its way. It
                    expires in 30 minutes.
                </p>
            </div>
            <Button
                variant="outline"
                size="lg"
                block
                class="text-base"
                disabled={secondsLeft > 0 || form.processing}
                onclick={() => send()}
            >
                <Refresh />{secondsLeft > 0 ? `Resend link · ${countdown}` : 'Resend link'}
            </Button>
        {:else}
            <div class="flex flex-col gap-2">
                <h1 class="text-[26px] font-extrabold lg:text-[32px]">Forgot password</h1>
                <p class="text-base leading-normal text-mut">
                    Enter the email your login details were sent to. We'll email you a link to choose a new
                    password.
                </p>
            </div>
            <Field id="email" label="Email" error={form.errors.email}>
                <TextInput
                    id="email"
                    name="email"
                    type="email"
                    autocomplete="email"
                    inputmode="email"
                    placeholder="name@company.ng"
                    bind:value={form.email}
                    invalid={!!form.errors.email}
                    required
                />
            </Field>
        {/if}

        <NsdContactCard />

        {#snippet actions()}
            {#if sentTo}
                <Button href={login().url} block class="lg:h-[52px] lg:rounded-xl lg:text-base">
                    Back to sign in
                </Button>
            {:else}
                <Button type="submit" block disabled={form.processing} class="lg:h-[52px] lg:rounded-xl lg:text-base">
                    {form.processing ? 'Sending…' : 'Send reset link'}
                </Button>
            {/if}
        {/snippet}
    </AuthShell>
</form>
