<script lang="ts">
    import { Dialog } from 'bits-ui';
    import type { Snippet } from 'svelte';
    import Close from '~icons/ms/close';

    type Props = {
        open: boolean;
        title: string;
        /** Optional line under the title for screen readers and context. */
        description?: string;
        children: Snippet;
        /** Footer actions (Cancel + primary). */
        footer?: Snippet;
        onOpenChange?: (open: boolean) => void;
    };

    let { open = $bindable(false), title, description, children, footer, onOpenChange }: Props = $props();
</script>

<!--
    Desktop: 500 px panel from the right over a scrim (AD-05 / AD-07).
    Phone: full-screen sheet with close on the left and the action pinned to the bottom (AM-07).
-->
<Dialog.Root bind:open {onOpenChange}>
    <Dialog.Portal>
        <Dialog.Overlay class="fixed inset-0 z-40 hidden bg-scrim lg:block" />
        <Dialog.Content
            class="fixed inset-0 z-50 flex flex-col bg-sf text-ink outline-none lg:inset-y-0 lg:right-0 lg:left-auto lg:w-[500px] lg:shadow-[-20px_0_50px_rgba(0,0,0,.2)]"
        >
            <div
                class="flex h-14 flex-none items-center gap-1 border-b border-line px-2 pt-[env(safe-area-inset-top)] lg:h-[72px] lg:flex-row-reverse lg:pr-4 lg:pl-7"
            >
                <Dialog.Close
                    class="flex size-12 items-center justify-center rounded-xl text-ink hover:bg-sf2 lg:size-11"
                    aria-label="Close"
                >
                    <Close class="size-6" />
                </Dialog.Close>
                <Dialog.Title class="flex-1 text-[18px] font-bold lg:text-[20px]">{title}</Dialog.Title>
            </div>
            {#if description}
                <Dialog.Description class="sr-only">{description}</Dialog.Description>
            {/if}

            <div class="flex flex-1 flex-col gap-3.5 overflow-y-auto px-5 py-[18px] lg:gap-4 lg:px-7 lg:py-6">
                {@render children()}
            </div>

            {#if footer}
                <div
                    class="pb-safe flex flex-none items-center gap-2.5 border-t border-line px-4 pt-3 lg:h-20 lg:px-7 lg:py-0"
                >
                    {@render footer()}
                </div>
            {/if}
        </Dialog.Content>
    </Dialog.Portal>
</Dialog.Root>
