<script lang="ts">
    import { Dialog } from 'bits-ui';
    import type { Attachment } from '@/lib/inspection/types';
    import ChevronLeft from '~icons/ms/chevron-left';
    import ChevronRight from '~icons/ms/chevron-right';
    import Close from '~icons/ms/close';
    import Download from '~icons/ms/download';

    type Props = {
        open: boolean;
        photos: Attachment[];
        index: number;
    };

    let { open = $bindable(false), photos, index = $bindable(0) }: Props = $props();

    const photo = $derived(photos[index]);

    const stamp = $derived.by(() => {
        if (!photo) {
            return '';
        }

        const gps = photo.exifLat !== null && photo.exifLng !== null ? `GPS stamp ${photo.exifLat.toFixed(5)}, ${photo.exifLng.toFixed(5)}` : 'No GPS in photo';
        const taken = photo.takenAt
            ? new Date(photo.takenAt).toLocaleString('en-GB', { day: '2-digit', month: 'short', year: 'numeric', hour: '2-digit', minute: '2-digit' })
            : null;

        return [gps, taken].filter(Boolean).join(' · ');
    });

    function go(step: number): void {
        index = (index + step + photos.length) % photos.length;
    }

    function onkeydown(event: KeyboardEvent): void {
        if (!open) {
            return;
        }

        if (event.key === 'ArrowRight') {
            go(1);
        } else if (event.key === 'ArrowLeft') {
            go(-1);
        }
    }

    // Swipe on phones.
    let touchX = 0;
</script>

<svelte:window {onkeydown} />

<!-- AD-04: dark full-screen viewer with thumbnails; swipe on phones. -->
<Dialog.Root bind:open>
    <Dialog.Portal>
        <Dialog.Overlay class="fixed inset-0 z-50 bg-[rgba(5,12,8,.94)]" />
        <Dialog.Content class="fixed inset-0 z-50 flex flex-col text-white outline-none">
            {#if photo}
                <div class="flex min-h-[72px] items-center gap-3.5 px-4 pt-[env(safe-area-inset-top)] lg:px-7">
                    <div class="flex min-w-0 flex-1 flex-col">
                        <Dialog.Title class="truncate text-base font-bold">Photo {index + 1} of {photos.length}</Dialog.Title>
                        <span class="truncate font-mono text-[13px] font-medium opacity-75">{stamp}</span>
                    </div>
                    <a
                        href="{photo.url}?download=1"
                        class="flex h-11 items-center gap-1.5 rounded-xl bg-white/12 px-3.5 text-sm font-bold text-white no-underline hover:bg-white/20"
                    >
                        <Download class="size-5" /><span class="hidden sm:inline">Download</span>
                    </a>
                    <Dialog.Close class="flex size-11 items-center justify-center rounded-xl bg-white/12 hover:bg-white/20" aria-label="Close">
                        <Close class="size-6" />
                    </Dialog.Close>
                </div>

                <div
                    role="group"
                    aria-label="Photo {index + 1} of {photos.length}. Swipe to change."
                    class="flex min-h-0 flex-1 items-center justify-center gap-7 px-2 lg:px-7"
                    ontouchstart={(e) => (touchX = e.touches[0].clientX)}
                    ontouchend={(e) => {
                        const dx = e.changedTouches[0].clientX - touchX;
                        if (Math.abs(dx) > 50) go(dx < 0 ? 1 : -1);
                    }}
                >
                    {#if photos.length > 1}
                        <button type="button" class="hidden size-14 flex-none items-center justify-center rounded-full bg-white/12 hover:bg-white/20 lg:flex" aria-label="Previous photo" onclick={() => go(-1)}>
                            <ChevronLeft class="size-7" />
                        </button>
                    {/if}
                    <img src={photo.url} alt="Site photo {index + 1}" class="max-h-full max-w-full rounded-lg object-contain lg:max-w-[960px]" />
                    {#if photos.length > 1}
                        <button type="button" class="hidden size-14 flex-none items-center justify-center rounded-full bg-white/12 hover:bg-white/20 lg:flex" aria-label="Next photo" onclick={() => go(1)}>
                            <ChevronRight class="size-7" />
                        </button>
                    {/if}
                </div>

                {#if photos.length > 1}
                    <div class="pb-safe flex min-h-[110px] items-center justify-center gap-2.5 overflow-x-auto px-4">
                        {#each photos as p, i (p.uuid)}
                            <button
                                type="button"
                                class={['h-16 w-[84px] flex-none overflow-hidden rounded-lg', i === index ? 'ring-3 ring-lime' : 'opacity-60 hover:opacity-90']}
                                aria-label="Show photo {i + 1}"
                                aria-current={i === index}
                                onclick={() => (index = i)}
                            >
                                <img src={p.url} alt="" class="size-full object-cover" loading="lazy" />
                            </button>
                        {/each}
                    </div>
                {/if}
            {/if}
        </Dialog.Content>
    </Dialog.Portal>
</Dialog.Root>
