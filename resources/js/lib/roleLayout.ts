import AdminLayout from '@/layouts/AdminLayout.svelte';
import ContractorLayout from '@/layouts/ContractorLayout.svelte';
import RepLayout from '@/layouts/RepLayout.svelte';
import type { Auth } from '@/types';

/**
 * Layout resolver for pages every role can open (profile, password).
 * Inertia calls a one-argument arrow function with the page props.
 */
export const roleLayout = (props: { auth: Auth }) => {
    switch (props.auth.user?.role) {
        case 'admin':
            return AdminLayout;
        case 'rep':
            return RepLayout;
        default:
            return ContractorLayout;
    }
};
