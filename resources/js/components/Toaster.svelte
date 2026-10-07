<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { onMount } from 'svelte';
    import CheckCircle from '~icons/ms/check-circle';
    import ErrorIcon from '~icons/ms/error';
    import Info from '~icons/ms/info';
    import type { Flash } from '@/types';

    type Toast = NonNullable<Flash['toast']>;

    let toast = $state<Toast | null>(null);
    let timer: ReturnType<typeof setTimeout> | undefined;

    function dismiss(): void {
        clearTimeout(timer);
        toast = null;
    }

    onMount(() =>
        router.on('flash', (event) => {
            const data = event.detail.flash?.toast;

            if (!data) {
                return;
            }

            clearTimeout(timer);
            toast = data;
            timer = setTimeout(dismiss, 6000);
        }),
    );
</script>

<!-- Dark snackbar from AM-08 / AD-06; sits above bottom tabs and action bars. -->
<div
    class="pointer-events-none fixed inset-x-4 bottom-[calc(104px+env(safe-area-inset-bottom))] z-50 flex justify-center lg:inset-x-auto lg:right-8 lg:bottom-8"
    aria-live="polite"
>
    {#if toast}
        <div
            role="status"
            class="pointer-events-auto flex min-h-14 w-full max-w-[420px] items-center gap-3 rounded-[14px] bg-ink py-2.5 pr-2 pl-4 text-sm text-bg shadow-[0_12px_30px_rgba(0,0,0,.3)]"
        >
            {#if toast.type === 'error'}
                <ErrorIcon class="size-[22px] flex-none text-bad-bg" />
            {:else if toast.type === 'info'}
                <Info class="size-[22px] flex-none text-info-bg" />
            {:else}
                <CheckCircle class="size-[22px] flex-none text-ok-bg" />
            {/if}
            <span class="flex-1 leading-snug">{toast.message}</span>
            <button
                type="button"
                class="h-10 rounded-lg px-2.5 font-bold hover:bg-white/10"
                onclick={dismiss}
            >
                OK
            </button>
        </div>
    {/if}
</div>
