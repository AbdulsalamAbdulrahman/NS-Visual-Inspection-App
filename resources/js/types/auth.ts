export type Role = 'admin' | 'contractor' | 'rep';

/** The signed-in user as shared on every Inertia response (see UserResource). */
export type AuthUser = {
    uuid: string;
    name: string;
    email: string;
    role: Role;
    initials: string;
    /** Role line under the name, e.g. "Contractor · Cat A" or "NSD Admin". */
    subtitle: string;
    /** Short licence label for contractors ("Cat A"), null for staff. */
    badge: string | null;
    /** Service area names (reps only). */
    areas: string[];
};

export type Auth = {
    user: AuthUser | null;
};
