<script lang="ts">
    import { bareInput, fieldBox, type InputSize } from './inputClasses';
    import { cn } from '@/lib/utils';

    type Props = {
        id: string;
        value: number | null;
        /** Unit after the value: mm², A, Ω, ft (01 Foundations reading style). */
        unit?: string;
        /** Whole numbers only (counts). */
        integer?: boolean;
        size?: InputSize;
        invalid?: boolean;
        /** Amber border for an out-of-range reading (soft warning, CF-05). */
        warn?: boolean;
        placeholder?: string;
        describedBy?: string;
        class?: string;
        inputClass?: string;
    };

    let {
        id,
        value = $bindable(),
        unit,
        integer = false,
        size = 'lg',
        invalid = false,
        warn = false,
        placeholder,
        describedBy,
        class: className,
        inputClass,
    }: Props = $props();

    // What the user typed, so "2." or "0.0" survive while typing.
    let text = $state(value === null || value === undefined ? '' : String(value));

    function parse(raw: string): number | null {
        const clean = raw.replace(',', '.').trim();

        if (clean === '') {
            return null;
        }

        const n = integer ? Number.parseInt(clean, 10) : Number.parseFloat(clean);

        return Number.isFinite(n) && n >= 0 ? n : null;
    }

    function oninput(event: Event & { currentTarget: HTMLInputElement }): void {
        const allowed = integer ? /[^\d]/g : /[^\d.,]/g;
        text = event.currentTarget.value.replace(allowed, '');
        event.currentTarget.value = text;
        value = parse(text);
    }
</script>

<div class={fieldBox(size, invalid, cn(warn && !invalid && 'border-2 border-imp', className))}>
    <input
        {id}
        type="text"
        inputmode={integer ? 'numeric' : 'decimal'}
        autocomplete="off"
        value={text}
        {oninput}
        {placeholder}
        aria-invalid={invalid || undefined}
        aria-describedby={describedBy}
        class={cn(bareInput, 'font-mono text-[18px] font-medium', inputClass)}
    />
    {#if unit}
        <span class="pr-3.5 font-mono text-[15px] font-medium text-mut" aria-hidden="true">{unit}</span>
    {/if}
</div>
