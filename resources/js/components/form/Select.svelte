<script lang="ts" generics="T extends string | number">
    import { fieldBox, type InputSize } from './inputClasses';
    import ExpandMore from '~icons/ms/expand-more';
    import { cn } from '@/lib/utils';

    type Props = {
        id: string;
        value: T | null;
        options: { value: T; label: string }[];
        placeholder: string;
        size?: InputSize;
        invalid?: boolean;
        describedBy?: string;
        class?: string;
        onchange?: (value: T | null) => void;
    };

    let {
        id,
        value = $bindable(),
        options,
        placeholder,
        size = 'lg',
        invalid = false,
        describedBy,
        class: className,
        onchange,
    }: Props = $props();

    function change(event: Event & { currentTarget: HTMLSelectElement }): void {
        const raw = event.currentTarget.value;
        const match = options.find((o) => String(o.value) === raw);
        value = match ? match.value : null;
        onchange?.(value);
    }
</script>

<!-- Native select: the phone's own picker is the most usable on site. -->
<div class={fieldBox(size, invalid, cn('relative', className))}>
    <select
        {id}
        value={value === null || value === undefined ? '' : String(value)}
        onchange={change}
        aria-invalid={invalid || undefined}
        aria-describedby={describedBy}
        class={['h-full w-full min-w-0 appearance-none bg-transparent pr-10 pl-3.5 outline-none focus-visible:outline-none', value === null || value === undefined ? 'text-mut' : 'text-ink']}
    >
        <option value="" disabled>{placeholder}</option>
        {#each options as option (option.value)}
            <option value={String(option.value)}>{option.label}</option>
        {/each}
    </select>
    <ExpandMore class="pointer-events-none absolute right-3 size-6 text-mut" aria-hidden="true" />
</div>
