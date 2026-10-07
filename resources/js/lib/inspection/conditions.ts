import type { Component } from 'svelte';
import type { CircuitCondition } from './types';
import Block from '~icons/ms/block';
import Build from '~icons/ms/build';
import CheckCircle from '~icons/ms/check-circle';
import PriorityHigh from '~icons/ms/priority-high';

/**
 * Circuit conditions run green → amber → orange → red, and always carry an
 * icon and a label so colour is never the only signal (01 Foundations).
 */
export const CONDITIONS: Record<
    CircuitCondition,
    { label: string; short: string; count: string; icon: Component; fg: string; bg: string; border: string }
> = {
    satisfactory: {
        label: 'Satisfactory',
        short: 'Satisfactory',
        count: 'Satisfactory',
        icon: CheckCircle,
        fg: 'text-ok',
        bg: 'bg-ok-bg',
        border: 'border-ok',
    },
    improvement_required: {
        label: 'Improvement required',
        short: 'Improvement required',
        count: 'Improvement',
        icon: Build,
        fg: 'text-imp',
        bg: 'bg-imp-bg',
        border: 'border-imp',
    },
    urgent_attention_required: {
        label: 'Urgent attention required',
        short: 'Urgent attention',
        count: 'Urgent',
        icon: PriorityHigh,
        fg: 'text-urg',
        bg: 'bg-urg-bg',
        border: 'border-urg',
    },
    non_compliant: {
        label: 'Does not comply with standard',
        short: 'Does not comply',
        count: 'Non-compliant',
        icon: Block,
        fg: 'text-bad',
        bg: 'bg-bad-bg',
        border: 'border-bad',
    },
};

export const CONDITION_ORDER: CircuitCondition[] = [
    'satisfactory',
    'improvement_required',
    'urgent_attention_required',
    'non_compliant',
];
