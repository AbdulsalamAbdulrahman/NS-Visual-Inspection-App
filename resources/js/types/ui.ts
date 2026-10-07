export type Appearance = 'light' | 'dark' | 'system';
export type ResolvedAppearance = 'light' | 'dark';

export type Flash = {
    toast?: {
        type: 'success' | 'info' | 'error';
        message: string;
    } | null;
};
