<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { onDestroy } from 'svelte';
    import Search from '~icons/ms/search';
    import { cn } from '@/lib/utils';

    type Props = {
        value: string;
        placeholder: string;
        /** Query-string key; the page reloads with it after typing stops. */
        param?: string;
        /** "filled" = grey field inside phone headers; "outlined" = desktop toolbar. */
        tone?: 'filled' | 'outlined';
        /** Props to reload (keeps the rest of the page cached). */
        only?: string[];
        class?: string;
    };

    let {
        value,
        placeholder,
        param = 'search',
        tone = 'outlined',
        only,
        class: className,
    }: Props = $props();

    // Local copy seeded once, so the server echo never overwrites what's being typed.
    // svelte-ignore state_referenced_locally
    let term = $state(value);
    let timer: ReturnType<typeof setTimeout> | undefined;

    function search(): void {
        clearTimeout(timer);
        timer = setTimeout(() => {
            const url = new URL(window.location.href);
            const next = term.trim();

            if (next) {
                url.searchParams.set(param, next);
            } else {
                url.searchParams.delete(param);
            }

            url.searchParams.delete('page');
            router.get(url.pathname + url.search, {}, { preserveState: true, preserveScroll: true, replace: true, only });
        }, 350);
    }

    onDestroy(() => clearTimeout(timer));
</script>

<label
    class={cn(
        'flex h-12 items-center gap-2 rounded-xl px-3 text-[15px] text-mut focus-within:text-ink',
        tone === 'filled' ? 'bg-sf2' : 'h-11 border-[1.5px] border-line bg-sf focus-within:border-pri',
        className,
    )}
>
    <Search class="size-[22px] flex-none" aria-hidden="true" />
    <span class="sr-only">{placeholder}</span>
    <input
        type="search"
        bind:value={term}
        oninput={search}
        {placeholder}
        class="h-full min-w-0 flex-1 bg-transparent text-ink outline-none placeholder:text-mut focus-visible:outline-none"
        autocomplete="off"
        enterkeyhint="search"
    />
</label>
