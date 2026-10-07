<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import { onMount, type Snippet } from 'svelte';
    import type { Component } from 'svelte';
    import ArrowBack from '~icons/ms/arrow-back';
    import Close from '~icons/ms/close';
    import IosShare from '~icons/ms/ios-share';
    import Print from '~icons/ms/print';

    type Props = {
        /** Desktop toolbar title, e.g. "Inspection report". */
        title: string;
        /** e.g. "KE-NSD-2026-000123 · A4 · 2 pages". */
        subtitle: string;
        backUrl: string;
        pageCount: number;
        /** `?pdf=1`: open the print dialog once fonts and images have loaded. */
        autoPrint?: boolean;
        share?: { title: string; url: string } | null;
        /** Switch to the other printout (certificate ↔ full report). */
        alternate?: { label: string; href: string; icon: Component } | null;
        /** The A4 sheets: each root element gets `class="print-sheet paper"`. */
        children: Snippet;
    };

    let { title, subtitle, backUrl, pageCount, autoPrint = false, share = null, alternate = null, children }: Props = $props();

    const A4_WIDTH = 794;

    let stageWidth = $state(0);
    let isPhone = $state(false);
    let activePage = $state(0);
    let stage: HTMLDivElement | undefined = $state();

    // Phones show one sheet at a time (PR-03); desktop fits the sheet to the window.
    const zoom = $derived(stageWidth === 0 ? 1 : Math.min(1, (stageWidth - (isPhone ? 40 : 64)) / A4_WIDTH));
    const canShare = $derived(share !== null && typeof navigator !== 'undefined' && typeof navigator.share === 'function');

    onMount(() => {
        const query = window.matchMedia('(max-width: 767px)');
        const update = () => (isPhone = query.matches);
        update();
        query.addEventListener('change', update);

        if (autoPrint) {
            void printWhenReady();
        }

        return () => query.removeEventListener('change', update);
    });

    /** Fonts and images (QR, signatures) must be loaded or they print blank. */
    async function printWhenReady(): Promise<void> {
        await document.fonts.ready;
        await Promise.all(
            Array.from(document.images)
                .filter((img) => !img.complete)
                .map(
                    (img) =>
                        new Promise<void>((resolve) => {
                            img.addEventListener('load', () => resolve(), { once: true });
                            img.addEventListener('error', () => resolve(), { once: true });
                        }),
                ),
        );
        window.print();
    }

    function onScroll(): void {
        if (isPhone && stage) {
            activePage = Math.round(stage.scrollLeft / Math.max(1, stage.clientWidth));
        }
    }

    async function shareLink(): Promise<void> {
        if (share) {
            await navigator.share({ title: share.title, url: share.url }).catch(() => {});
        }
    }
</script>

<div class="flex h-dvh flex-col bg-[#E4E8E2] text-ink max-md:bg-[#1A221D] max-md:text-[#EEF4EF] print:block print:h-auto print:bg-white">
    <!-- Desktop toolbar -->
    <header class="print-chrome hidden h-16 flex-none items-center gap-3 border-b border-line bg-sf px-6 md:flex">
        <Link href={backUrl} class="flex h-10 items-center gap-1.5 rounded-lg px-2.5 text-sm font-semibold text-ink no-underline hover:bg-sf2">
            <ArrowBack class="size-5" />Back
        </Link>
        <div class="flex min-w-0 flex-col">
            <b class="truncate text-[15px]">{title}</b>
            <span class="truncate font-mono text-xs text-mut">{subtitle}</span>
        </div>
        <div class="ml-auto flex items-center gap-2">
            {#if alternate}
                {@const Icon = alternate.icon}
                <Link href={alternate.href} class="flex h-10 items-center gap-1.5 rounded-[10px] border-[1.5px] border-line bg-sf px-3.5 text-sm font-bold text-ink no-underline hover:bg-sf2">
                    <Icon class="size-5" />{alternate.label}
                </Link>
            {/if}
            <button type="button" class="flex h-10 items-center gap-1.5 rounded-[10px] bg-pri px-4 text-sm font-bold text-on-pri" onclick={() => window.print()}>
                <Print class="size-5" />Print / Save as PDF
            </button>
        </div>
    </header>

    <!-- Phone header (PR-03) -->
    <header class="print-chrome flex h-14 flex-none items-center gap-1 px-2 pt-[env(safe-area-inset-top)] md:hidden">
        <Link href={backUrl} class="flex size-12 items-center justify-center rounded-xl text-inherit" aria-label="Close preview">
            <Close class="size-6" />
        </Link>
        <div class="flex min-w-0 flex-1 flex-col">
            <b class="text-base">Print preview</b>
            <span class="truncate font-mono text-xs text-[#A2B3A8]">{subtitle}</span>
        </div>
        {#if alternate}
            {@const Icon = alternate.icon}
            <Link href={alternate.href} class="flex size-12 items-center justify-center rounded-xl text-inherit" aria-label={alternate.label}>
                <Icon class="size-6" />
            </Link>
        {/if}
    </header>

    <div
        bind:this={stage}
        bind:clientWidth={stageWidth}
        onscroll={onScroll}
        class="print-stage flex min-h-0 flex-1 snap-x snap-mandatory overflow-x-auto overflow-y-auto [scrollbar-width:none] md:snap-none md:flex-col md:items-center md:gap-8 md:overflow-x-hidden md:py-8"
        style:--sheet-zoom={zoom}
    >
        {@render children()}
    </div>

    <!-- Phone: page dots + actions -->
    <div class="print-chrome flex-none md:hidden">
        {#if pageCount > 1}
            <div class="flex items-center justify-center gap-2.5 py-2.5 font-mono text-[13px] text-[#A2B3A8]" aria-live="polite">
                {#each Array.from({ length: pageCount }, (_, i) => i) as i (i)}
                    <span class={['size-2 rounded-full', i === activePage ? 'bg-[#7BB43B]' : 'bg-[#3A4A41]']}></span>
                {/each}
                <span>{activePage + 1} / {pageCount} · swipe</span>
            </div>
        {/if}
        <div class={['grid gap-2.5 border-t border-[#283A31] bg-[#111C16] px-4 pt-3 pb-[calc(12px+env(safe-area-inset-bottom))]', canShare ? 'grid-cols-2' : 'grid-cols-1']}>
            {#if canShare}
                <button type="button" class="flex h-14 items-center justify-center gap-1.5 rounded-[14px] border-2 border-[#283A31] text-[15px] font-bold" onclick={shareLink}>
                    <IosShare class="size-5" />Share link
                </button>
            {/if}
            <button type="button" class="flex h-14 items-center justify-center gap-1.5 rounded-[14px] bg-[#8CC84B] text-[15px] font-bold text-[#062012]" onclick={() => window.print()}>
                <Print class="size-5" />Print / Save PDF
            </button>
        </div>
    </div>
</div>

<style>
    /* Each sheet: A4 at 96 dpi, scaled to fit on screen; one page per sheet in print. */
    .print-stage :global(.print-sheet) {
        width: 794px;
        height: 1123px;
        flex: none;
        overflow: hidden;
        zoom: var(--sheet-zoom, 1);
        box-shadow: 0 20px 50px rgba(10, 30, 18, 0.18);
    }

    @media (max-width: 767px) {
        .print-stage {
            gap: 40px;
            align-items: center;
            padding: 12px 20px;
        }

        .print-stage :global(.print-sheet) {
            scroll-snap-align: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }
    }
</style>
