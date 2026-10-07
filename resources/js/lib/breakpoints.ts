import type { Role } from '@/types';

/**
 * Where each role's shell switches from phone headers to the desktop chrome:
 * reps get the top bar from tablet width (SR-T1, md); contractor and admin
 * shells switch at lg. Literal class strings so Tailwind picks them up.
 */
export function shellClasses(role: Role | undefined) {
    return role === 'rep'
        ? { mobileOnly: 'md:hidden', desktopOnly: 'hidden md:block', desktopFlex: 'hidden md:inline-flex' }
        : { mobileOnly: 'lg:hidden', desktopOnly: 'hidden lg:block', desktopFlex: 'hidden lg:inline-flex' };
}
