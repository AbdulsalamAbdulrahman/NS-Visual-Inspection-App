<script lang="ts">
    import { Dialog } from 'bits-ui';
    import type { Snippet } from 'svelte';

    type Props = {
        open: boolean;
        /** Accessible name; shown as the bold heading. */
        title: string;
        /** Mono sub-line under the title (e.g. "Cat B · NEMSA/B/2024/0377"). */
        subtitle?: string;
        /** Hide the title row (confirmation steps draw their own). */
        bare?: boolean;
        children: Snippet;
        onOpenChange?: (open: boolean) => void;
    };

    let { open = $bindable(false), title, subtitle, bare = false, children, onOpenChange }: Props = $props();
</script>

<!-- Phone bottom sheet with a grab handle (AM-05, AM-06, AM-03). -->
<Dialog.Root bind:open {onOpenChange}>
    <Dialog.Portal>
        <Dialog.Overlay class="fixed inset-0 z-40 bg-scrim" />
        <Dialog.Content
            class="pb-safe fixed inset-x-0 bottom-0 z-50 flex max-h-[90dvh] flex-col overflow-y-auto rounded-t-3xl bg-sf text-ink outline-none"
        >
            <div class="flex justify-center pt-2.5 pb-1" aria-hidden="true">
                <div class="h-[5px] w-10 rounded-full bg-line"></div>
            </div>
            {#if bare}
                <Dialog.Title class="sr-only">{title}</Dialog.Title>
            {:else}
                <div class="flex flex-col gap-0.5 border-b border-line px-5 pt-2 pb-3">
                    <Dialog.Title class="text-[17px] font-bold">{title}</Dialog.Title>
                    {#if subtitle}
                        <span class="font-mono text-[13px] font-medium text-mut">{subtitle}</span>
                    {/if}
                </div>
            {/if}
            {@render children()}
        </Dialog.Content>
    </Dialog.Portal>
</Dialog.Root>
