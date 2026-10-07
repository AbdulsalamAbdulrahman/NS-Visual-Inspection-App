<script lang="ts">
    import type { HTMLInputAttributes } from 'svelte/elements';
    import { bareInput, fieldBox, type InputSize } from './inputClasses';
    import { cn } from '@/lib/utils';

    type Props = Omit<HTMLInputAttributes, 'size' | 'class'> & {
        id: string;
        value?: string | number | null;
        size?: InputSize;
        invalid?: boolean;
        /** Unit shown after the value (mm², A, Ω, ft): mono 18 px reading style (01 Foundations). */
        unit?: string;
        /** Mono numerals at the field's own size (phone, registration numbers). */
        mono?: boolean;
        class?: string;
    };

    let {
        id,
        value = $bindable(),
        size = 'lg',
        invalid = false,
        unit,
        mono = false,
        class: className,
        ...rest
    }: Props = $props();

</script>

<div class={fieldBox(size, invalid, className)}>
    <input
        {id}
        bind:value
        aria-invalid={invalid || undefined}
        aria-describedby={invalid ? `${id}-error` : undefined}
        class={cn(bareInput, (mono || unit) && 'font-mono font-medium', unit && 'text-[18px]')}
        {...rest}
    />
    {#if unit}
        <span class="pr-3.5 font-mono text-sm font-medium text-mut" aria-hidden="true">{unit}</span>
    {/if}
</div>
