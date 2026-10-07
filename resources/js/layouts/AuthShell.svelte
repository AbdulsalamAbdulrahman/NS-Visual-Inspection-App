<script lang="ts">
    import { APP_TITLE } from '@/lib/brand';
    import type { Snippet } from 'svelte';
    import Logo from '@/components/Logo.svelte';

    type Props = {
        /**
         * "hero": sign-in screen with the green band header on phones (AU-01).
         * "plain": small logo row on phones (AU-03 / AU-05). Desktop is always split (AU-D1).
         */
        variant?: 'hero' | 'plain';
        /** Mono caps label next to the small logo in the plain variant. */
        eyebrow?: string;
        /** Optional row above the content on phones (back button). */
        top?: Snippet;
        /** Pinned to the bottom thumb zone on phones; flows after content on desktop. */
        actions?: Snippet;
        children: Snippet;
    };

    let { variant = 'plain', eyebrow, top, actions, children }: Props = $props();
</script>

<div class="min-h-dvh bg-bg lg:grid lg:grid-cols-[1fr_560px]">
    <!-- Brand panel: phone hero (sign-in only) and desktop left half -->
    <div
        class={[
            'relative flex-col overflow-hidden bg-hero text-white',
            variant === 'hero' ? 'flex h-[min(320px,38dvh)] pt-[env(safe-area-inset-top)]' : 'hidden',
            'lg:flex lg:h-auto lg:min-h-dvh lg:justify-between lg:p-16',
        ]}
    >
        <!-- Lime bands at the logo's 26° angle -->
        <div
            class="absolute top-[58%] -left-10 h-[34px] w-[140%] -rotate-[26deg] bg-lime lg:top-[58%] lg:-left-20 lg:h-16 lg:w-[1100px]"
        ></div>
        <div
            class="absolute top-[78%] left-[120px] h-[34px] w-[140%] -rotate-[26deg] bg-leaf lg:top-[71%] lg:left-[180px] lg:h-16 lg:w-[1100px]"
        ></div>

        <div class="relative flex flex-col gap-4 px-7 pt-7 lg:flex-row lg:items-center lg:p-0">
            <span class="lg:hidden"><Logo size={72} ring={false} /></span>
            <span class="hidden lg:inline-flex"><Logo size={64} ring={false} /></span>
            <div class="flex flex-col gap-1 lg:gap-0.5">
                <b class="text-[26px] font-extrabold tracking-[-0.01em] lg:text-[22px] lg:font-bold">
                    Kaduna Electric
                </b>
                <span class="mono-caps text-[13px] opacity-85">New Service Department</span>
            </div>
        </div>

        <div class="relative mb-[220px] hidden max-w-[560px] flex-col gap-4 lg:flex">
            <div class="text-[48px] leading-[1.04] font-extrabold tracking-[-0.03em]">
                {APP_TITLE}
            </div>
            <div class="text-[19px] leading-normal opacity-90">
                Contractor inspection reports, NSD review and certificates for new service connections.
            </div>
        </div>
    </div>

    <!-- Form panel -->
    <div
        class={[
            'flex min-h-[calc(100dvh-min(320px,38dvh))] flex-col bg-bg lg:min-h-dvh lg:justify-center lg:bg-sf lg:p-16',
            variant !== 'hero' && 'min-h-dvh pt-[env(safe-area-inset-top)]',
        ]}
    >
        {#if top}
            <div class="flex h-14 items-center px-2 lg:hidden">{@render top()}</div>
        {/if}

        <div class="flex flex-1 flex-col gap-[18px] px-6 pt-5 lg:flex-none lg:gap-[22px] lg:p-0">
            {#if variant === 'plain'}
                <div class="flex items-center gap-3 lg:hidden">
                    <Logo size={44} />
                    {#if eyebrow}
                        <span class="mono-caps text-mut">{eyebrow}</span>
                    {/if}
                </div>
            {/if}
            {#if eyebrow}
                <span class="mono-caps hidden text-brand lg:block">{eyebrow}</span>
            {/if}
            {@render children()}
        </div>

        {#if actions}
            <div
                class="pb-safe sticky bottom-0 flex flex-col gap-3 bg-bg px-6 pt-4 lg:static lg:mt-[22px] lg:bg-transparent lg:p-0"
            >
                {@render actions()}
            </div>
        {/if}
    </div>
</div>
