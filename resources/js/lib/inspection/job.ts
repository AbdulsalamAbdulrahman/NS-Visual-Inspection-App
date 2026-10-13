import type { ReviewStatus } from '@/lib/inspection/report';
import type { Tone } from '@/components/StatusPill.svelte';

/** A job a rep created (JobResource). */
export type Job = {
    uuid: string;
    status: 'assigned' | 'draft' | 'submitted';
    progress: JobProgress;
    progressLabel: string;
    ticketNo: string | null;
    review: ReviewStatus | null;
    reviewLabel: string | null;
    ownerName: string;
    ownerPhone: string | null;
    ownerEmail: string | null;
    address: string | null;
    propertyAddress: string | null;
    areaId: number | null;
    area: string | null;
    purpose: string | null;
    buildingType: string | null;
    building: string | null;
    form74No: string | null;
    form74LastNo: string | null;
    form74: string | null;
    structures: number;
    contractorUuid: string;
    contractor: string;
    assignedBy: string | null;
    assignedAt: string | null;
};

export type JobProgress = 'not_started' | 'in_progress' | 'submitted';

export const PROGRESS_TONE: Record<JobProgress, Tone> = {
    not_started: 'muted',
    in_progress: 'info',
    submitted: 'ok',
};
