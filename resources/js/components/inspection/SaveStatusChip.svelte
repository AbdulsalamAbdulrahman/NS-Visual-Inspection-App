<script lang="ts">
    import type { SaveStatus } from '@/lib/inspection/autosave.svelte';
    import { clock } from '@/lib/format';
    import CloudDone from '~icons/ms/cloud-done';
    import ErrorIcon from '~icons/ms/error';
    import PhoneAndroid from '~icons/ms/phone-android';
    import Sync from '~icons/ms/sync';

    type Props = { status: SaveStatus; savedAt: Date | null; long?: boolean };

    let { status, savedAt, long = false }: Props = $props();
</script>

<!-- "Saved 10:42" · "Saving…" · "On device" (CF-01, CF-02, CF-10) -->
<span
    class={[
        'flex flex-none items-center gap-1 text-[13px] font-semibold lg:text-sm',
        status === 'saved' && 'text-ok',
        (status === 'saving' || status === 'pending') && 'text-mut',
        status === 'offline' && 'text-info',
        (status === 'error' || status === 'signin') && 'text-bad',
    ]}
    role="status"
    aria-live="polite"
>
    {#if status === 'saving' || status === 'pending'}
        <Sync class="size-[18px] motion-safe:animate-spin" />Saving…
    {:else if status === 'offline'}
        <PhoneAndroid class="size-[18px]" />On device
    {:else if status === 'signin'}
        <ErrorIcon class="size-[18px]" />Sign in to sync
    {:else if status === 'error'}
        <ErrorIcon class="size-[18px]" />Not saved
    {:else}
        <CloudDone class="size-[18px]" />{long ? 'All changes saved' : 'Saved'}{savedAt ? (long ? ` · ${clock(savedAt)}` : ` ${clock(savedAt)}`) : ''}
    {/if}
</span>
