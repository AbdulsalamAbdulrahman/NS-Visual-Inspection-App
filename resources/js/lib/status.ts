import type { Tone } from '@/components/StatusPill.svelte';

export type AccountStatus = 'invited' | 'active' | 'suspended';

/** Account status → pill tone (AD-05 / AD-06). */
export const accountTone: Record<AccountStatus, Tone> = {
    active: 'ok',
    invited: 'info',
    suspended: 'bad',
};
