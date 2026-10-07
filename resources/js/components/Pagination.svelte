<script lang="ts" module>
    /** Laravel paginator meta as serialised by ResourceCollection. */
    export type PageMeta = {
        current_page: number;
        last_page: number;
        from: number | null;
        to: number | null;
        total: number;
    };

    export type PageLinks = {
        prev: string | null;
        next: string | null;
    };
</script>

<script lang="ts">
    import { Link } from '@inertiajs/svelte';
    import ChevronLeft from '~icons/ms/chevron-left';
    import ChevronRight from '~icons/ms/chevron-right';

    type Props = {
        meta: PageMeta;
        links: PageLinks;
        noun?: string;
    };

    let { meta, links, noun = 'results' }: Props = $props();

    const button =
        'flex h-11 items-center gap-1 rounded-xl border-[1.5px] border-line bg-sf px-3.5 text-sm font-bold text-ink no-underline hover:bg-sf2';
</script>

{#if meta.last_page > 1}
    <nav class="flex items-center justify-between gap-3 py-2" aria-label="Pagination">
        <span class="font-mono text-[13px] text-mut">
            {meta.from}–{meta.to} of {meta.total} {noun}
        </span>
        <div class="flex gap-2">
            {#if links.prev}
                <Link href={links.prev} preserveScroll class={button} rel="prev">
                    <ChevronLeft class="size-5" />Previous
                </Link>
            {/if}
            {#if links.next}
                <Link href={links.next} preserveScroll class={button} rel="next">
                    Next<ChevronRight class="size-5" />
                </Link>
            {/if}
        </div>
    </nav>
{/if}
