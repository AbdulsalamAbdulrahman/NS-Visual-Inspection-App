<script lang="ts" generics="T extends string | boolean">
    import Check from '~icons/ms/check';
    import { cn } from '@/lib/utils';

    type Option = { value: T; label: string };

    type Props = {
        label: string;
        options: Option[];
        value: T | null;
        /**
         * chips: bordered tiles with a check when picked (CF-01, AM-07).
         * segmented: one pill track, picked = primary fill (Yes/No, AD-05).
         * responsive: chips on phones, segmented track from lg (admin drawers).
         */
        variant?: 'chips' | 'segmented' | 'responsive';
        /** Columns for chips on phones (segmented uses one per option). */
        columns?: number;
        invalid?: boolean;
        describedBy?: string;
        class?: string;
        onchange?: (value: T) => void;
    };

    let {
        label,
        options,
        value = $bindable(),
        variant = 'chips',
        columns = 2,
        invalid = false,
        describedBy,
        class: className,
        onchange,
    }: Props = $props();

    const segmented = $derived(variant === 'segmented');
    const responsive = $derived(variant === 'responsive');

    function pick(option: Option): void {
        value = option.value;
        onchange?.(option.value);
    }

    // Radio-group keyboard pattern: arrows move and select.
    function onkeydown(event: KeyboardEvent, index: number): void {
        const step = ({ ArrowRight: 1, ArrowDown: 1, ArrowLeft: -1, ArrowUp: -1 } as Record<string, number>)[event.key];

        if (!step) {
            return;
        }

        event.preventDefault();
        const next = (index + step + options.length) % options.length;
        pick(options[next]);
        ((event.currentTarget as HTMLElement).parentElement?.children[next] as HTMLElement | undefined)?.focus();
    }
</script>

<div
    role="radiogroup"
    aria-label={label}
    aria-invalid={invalid || undefined}
    aria-describedby={describedBy}
    style:--cols={segmented ? options.length : columns}
    style:--cols-lg={options.length}
    class={cn(
        'grid grid-cols-[repeat(var(--cols),minmax(0,1fr))]',
        segmented ? 'gap-1 rounded-[14px] bg-sf2 p-1' : 'gap-1.5',
        responsive && 'lg:grid-cols-[repeat(var(--cols-lg),minmax(0,1fr))] lg:gap-1 lg:rounded-xl lg:bg-sf2 lg:p-1',
        invalid && 'rounded-[14px] outline-2 outline-offset-2 outline-bad',
        className,
    )}
>
    {#each options as option, i (String(option.value))}
        {@const on = value === option.value}
        <button
            type="button"
            role="radio"
            aria-checked={on}
            tabindex={on || (value === null && i === 0) ? 0 : -1}
            class={cn(
                'flex min-h-12 items-center justify-center gap-1 px-3 text-center text-base transition-colors',
                segmented
                    ? ['rounded-[10px] font-semibold', on ? 'bg-pri font-bold text-on-pri' : 'text-ink']
                    : [
                          'rounded-xl border-[1.5px] font-semibold',
                          on ? 'border-2 border-pri bg-soft font-bold text-brand' : 'border-line bg-sf text-ink',
                      ],
                responsive && [
                    'lg:min-h-10 lg:rounded-[9px] lg:border-0 lg:text-sm',
                    on ? 'lg:bg-pri lg:text-on-pri' : 'lg:bg-transparent',
                ],
            )}
            onclick={() => pick(option)}
            onkeydown={(e) => onkeydown(e, i)}
        >
            {#if on && !segmented}
                <Check class={cn('size-[18px] flex-none', responsive && 'lg:hidden')} aria-hidden="true" />
            {/if}
            {option.label}
        </button>
    {/each}
</div>
