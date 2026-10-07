<script lang="ts" module>
    export type Tone = 'ok' | 'info' | 'bad' | 'imp' | 'urg' | 'muted';
</script>

<script lang="ts">
    import { cn } from '@/lib/utils';

    type Props = {
        tone: Tone;
        label: string;
        class?: string;
    };

    let { tone, label, class: className }: Props = $props();

    const tones: Record<Tone, string> = {
        ok: 'bg-ok-bg text-ok',
        info: 'bg-info-bg text-info',
        bad: 'bg-bad-bg text-bad',
        imp: 'bg-imp-bg text-imp',
        urg: 'bg-urg-bg text-urg',
        muted: 'bg-sf2 text-mut',
    };
</script>

<!-- The label always carries the meaning; colour only reinforces it. -->
<span class={cn('inline-flex items-center rounded-full px-2.5 py-1 text-xs leading-none font-bold', tones[tone], className)}>
    {label}
</span>
