<script lang="ts" module>
    /** Mirrors Password::defaults() in AppServiceProvider. */
    export const passwordChecks = [
        { label: '8+ characters', test: (v: string) => v.length >= 8 },
        { label: 'A number', test: (v: string) => /\d/.test(v) },
        { label: 'Upper & lower case', test: (v: string) => /[a-z]/.test(v) && /[A-Z]/.test(v) },
        { label: 'A symbol', test: (v: string) => /[^A-Za-z0-9]/.test(v) },
    ];

    export function passwordReady(password: string, confirmation: string): boolean {
        return passwordChecks.every((c) => c.test(password)) && password === confirmation;
    }
</script>

<script lang="ts">
    import Field from './Field.svelte';
    import PasswordInput from './PasswordInput.svelte';
    import Check from '~icons/ms/check';
    import CheckCircle from '~icons/ms/check-circle';
    import RadioButtonUnchecked from '~icons/ms/radio-button-unchecked';

    type Props = {
        password: string;
        confirmation: string;
        errors: Partial<Record<string, string>>;
        size?: 'lg' | 'md';
        label?: string;
    };

    let {
        password = $bindable(''),
        confirmation = $bindable(''),
        errors,
        size = 'lg',
        label = 'New password',
    }: Props = $props();

    const matches = $derived(confirmation.length > 0 && password === confirmation);
</script>

<Field id="password" {label} error={errors.password}>
    <PasswordInput
        id="password"
        name="password"
        autocomplete="new-password"
        {size}
        bind:value={password}
        invalid={!!errors.password}
        required
    />
</Field>

<ul class="grid grid-cols-2 gap-x-3 gap-y-2.5 text-sm font-semibold" aria-label="Password requirements">
    {#each passwordChecks as check (check.label)}
        {@const ok = check.test(password)}
        <li class={['flex items-center gap-1.5', ok ? 'text-ok' : 'text-mut']}>
            {#if ok}
                <CheckCircle class="size-[18px] flex-none" aria-hidden="true" />
            {:else}
                <RadioButtonUnchecked class="size-[18px] flex-none" aria-hidden="true" />
            {/if}
            <span>{check.label}<span class="sr-only">{ok ? ' — done' : ' — not yet'}</span></span>
        </li>
    {/each}
</ul>

<Field id="password_confirmation" label="Confirm new password" error={errors.password_confirmation}>
    <PasswordInput
        id="password_confirmation"
        name="password_confirmation"
        autocomplete="new-password"
        placeholder="Re-enter password"
        {size}
        bind:value={confirmation}
        required
    />
    {#if matches}
        <span class="flex items-center gap-1.5 text-sm font-semibold text-ok">
            <Check class="size-[18px]" />Passwords match
        </span>
    {/if}
</Field>
