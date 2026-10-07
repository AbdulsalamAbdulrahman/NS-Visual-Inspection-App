import type { Tone } from '@/components/StatusPill.svelte';

export type AccountStatus = 'invited' | 'active' | 'suspended';

/** Account status → pill tone (AD-05 / AD-06). */
export const accountTone: Record<AccountStatus, Tone> = {
    active: 'ok',
    invited: 'info',
    suspended: 'bad',
};

export type PaymentStatus = 'pending' | 'paid' | 'failed' | 'abandoned';

/** Payment status → pill tone (AD-09). */
export const paymentTone: Record<PaymentStatus, Tone> = {
    paid: 'ok',
    failed: 'bad',
    abandoned: 'imp',
    pending: 'muted',
};
