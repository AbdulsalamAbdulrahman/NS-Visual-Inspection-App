<script lang="ts" module>
    export type Choice<T> = {
        value: T;
        label: string;
        /** Picked colour: ok = green ✓ (Standard), bad = red ✕ (Not standard / No). */
        tone?: 'ok' | 'bad';
    };

    /** Yes / No for "Standard?" questions: No is a finding, shown in red (CF-06). */
    export const STANDARD_YES_NO: Choice<boolean>[] = [
        { value: true, label: 'Yes' },
        { value: false, label: 'No', tone: 'bad' },
    ];

    export const YES_NO: Choice<boolean>[] = [
        { value: true, label: 'Yes' },
        { value: false, label: 'No' },
    ];

    /** Standard / Not standard tiles (CF-07). */
    export const STANDARD: Choice<boolean>[] = [
        { value: true, label: 'Standard', tone: 'ok' },
        { value: false, label: 'Not standard', tone: 'bad' },
    ];
</script>

<script lang="ts" generics="T extends string | number | boolean">
    import Check from '~icons/ms/check';
    import Close from '~icons/ms/close';
    import { cn } from '@/lib/utils';

    type Props = {
        label: string;
        options: Choice<T>[];
        value: T | null;
        /**
         * chips: bordered tiles with a check when picked (CF-01, AM-07).
         * segmented: one pill track, picked = primary fill (Yes/No, CF-02).
         * responsive: chips on phones, segmented track from lg (admin drawers).
         */
        variant?: 'chips' | 'segmented' | 'responsive';
        /** Columns for chips on phones (segmented uses one per option). */
        columns?: number;
        /** Mono labels (voltages, earthing types). */
        mono?: boolean;
        /** 46–48 px controls inside cards instead of 52–56 px. */
        compact?: boolean;
        invalid?: boolean;
        describedBy?: string;
        id?: string;
        class?: string;
        onchange?: (value: T) => void;
    };

    let {
        label,
        options,
        value = $bindable(),
        variant = 'chips',
        columns = 2,
        mono = false,
        compact = false,
        invalid = false,
        describedBy,
        id,
        class: className,
        onchange,
    }: Props = $props();

    const segmented = $derived(variant === 'segmented');
    const responsive = $derived(variant === 'responsive');

    function pick(option: Choice<T>): void {
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

    function pickedClasses(option: Choice<T>): string {
        if (segmented) {
            return option.tone === 'bad' ? 'bg-bad-bg font-bold text-bad' : 'bg-pri font-bold text-on-pri';
        }

        return option.tone === 'ok'
            ? 'border-2 border-ok bg-ok-bg font-bold text-ok'
            : option.tone === 'bad'
              ? 'border-2 border-bad bg-bad-bg font-bold text-bad'
              : 'border-2 border-pri bg-soft font-bold text-brand';
    }
</script>

<div
    {id}
    role="radiogroup"
    aria-label={label}
    aria-invalid={invalid || undefined}
    aria-describedby={describedBy}
    style:--cols={segmented ? options.length : columns}
    style:--cols-lg={options.length}
    class={cn(
        'grid grid-cols-[repeat(var(--cols),minmax(0,1fr))]',
        segmented ? 'gap-1 rounded-[14px] bg-sf2 p-1' : compact ? 'gap-2' : 'gap-1.5',
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
            tabindex={on || ((value === null || value === undefined) && i === 0) ? 0 : -1}
            class={cn(
                'flex items-center justify-center gap-1 px-2 text-center transition-colors',
                compact ? 'min-h-12 text-[15px]' : segmented ? 'min-h-[52px] text-base' : 'min-h-14 text-base',
                mono && 'font-mono',
                segmented ? 'rounded-[10px] font-semibold text-ink' : 'rounded-xl border-[1.5px] border-line bg-sf font-semibold text-ink',
                on && pickedClasses(option),
                responsive && [
                    'lg:min-h-10 lg:rounded-[9px] lg:border-0 lg:text-sm',
                    on ? 'lg:bg-pri lg:text-on-pri' : 'lg:bg-transparent',
                ],
            )}
            onclick={() => pick(option)}
            onkeydown={(e) => onkeydown(e, i)}
        >
            {#if on && !segmented}
                {#if option.tone === 'bad'}
                    <Close class="size-[18px] flex-none" aria-hidden="true" />
                {:else}
                    <Check class={cn('size-[18px] flex-none', responsive && 'lg:hidden')} aria-hidden="true" />
                {/if}
            {/if}
            {option.label}
        </button>
    {/each}
</div>
