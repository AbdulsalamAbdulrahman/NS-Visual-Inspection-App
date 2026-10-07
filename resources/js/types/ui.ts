export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type Flash = {
    toast?: {
        type: 'success' | 'info' | 'error';
        message: string;
    } | null;
    /** uuid of a row just created, tinted on the list (AD-06). */
    highlight?: string;
};
