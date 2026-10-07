<script lang="ts">
    import { CONDITIONS } from '@/lib/inspection/conditions';
    import type { CircuitCondition } from '@/lib/inspection/types';
    import { cn } from '@/lib/utils';

    type Props = {
        condition: CircuitCondition;
        /** "short" for tables (CK-02); "full" on phone cards (CF-08). */
        length?: 'full' | 'short';
        class?: string;
    };

    let { condition, length = 'full', class: className }: Props = $props();

    const c = $derived(CONDITIONS[condition]);
</script>

<span
    class={cn(
        'inline-flex items-center gap-1.5 self-start rounded-full py-[5px] pr-2.5 pl-[7px] text-[13px] font-bold',
        c.bg,
        c.fg,
        className,
    )}
>
    <c.icon class="size-[17px] flex-none" aria-hidden="true" />
    {length === 'short' ? c.short : c.label}
</span>
