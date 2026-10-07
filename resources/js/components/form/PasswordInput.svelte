<script lang="ts">
    import type { HTMLInputAttributes } from 'svelte/elements';
    import { bareInput, fieldBox, type InputSize } from './inputClasses';
    import Visibility from '~icons/ms/visibility';
    import VisibilityOff from '~icons/ms/visibility-off';

    type Props = Omit<HTMLInputAttributes, 'size' | 'class' | 'type'> & {
        id: string;
        value?: string;
        size?: InputSize;
        invalid?: boolean;
        class?: string;
    };

    let {
        id,
        value = $bindable(''),
        size = 'lg',
        invalid = false,
        class: className,
        ...rest
    }: Props = $props();

    let visible = $state(false);
</script>

<div class={fieldBox(size, invalid, `pr-1.5 ${className ?? ''}`)}>
    <input
        {id}
        type={visible ? 'text' : 'password'}
        bind:value
        aria-invalid={invalid || undefined}
        aria-describedby={invalid ? `${id}-error` : undefined}
        class={bareInput}
        {...rest}
    />
    <button
        type="button"
        class="flex size-11 flex-none items-center justify-center rounded-lg text-mut hover:bg-sf2"
        aria-label={visible ? 'Hide password' : 'Show password'}
        aria-pressed={visible}
        onclick={() => (visible = !visible)}
    >
        {#if visible}
            <VisibilityOff class="size-[22px]" />
        {:else}
            <Visibility class="size-[22px]" />
        {/if}
    </button>
</div>
