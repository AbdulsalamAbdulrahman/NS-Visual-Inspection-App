<script lang="ts">
    import type { Attachment, Draft } from '@/lib/inspection/types';
    import AttachmentSection from './AttachmentSection.svelte';

    type Props = { draft: Draft; errors: Record<string, string> };

    let { draft = $bindable(), errors }: Props = $props();

    const added = (a: Attachment): void => {
        draft.attachments.push(a);
    };
    const removed = (uuid: string): void => {
        draft.attachments = draft.attachments.filter((a) => a.uuid !== uuid);
    };
</script>

<AttachmentSection
    inspectionUuid={draft.uuid}
    type="layout"
    title="Electrical layout & load schedule"
    hint="PDF or a photo of the drawing."
    required
    layout="files"
    max={3}
    attachments={draft.attachments}
    error={errors.layout}
    onadded={added}
    onremoved={removed}
/>

<AttachmentSection
    inspectionUuid={draft.uuid}
    type="photo"
    title="Photos of DB & earthing"
    hint="Use a GPS-stamp camera — coordinates must be visible in the photo."
    required
    layout="photos"
    max={12}
    attachments={draft.attachments}
    error={errors.photos}
    onadded={added}
    onremoved={removed}
/>

<AttachmentSection
    inspectionUuid={draft.uuid}
    type="calibration"
    title="Test equipment calibration certificate"
    hint="Optional · where applicable"
    layout="files"
    max={3}
    attachments={draft.attachments}
    onadded={added}
    onremoved={removed}
/>
