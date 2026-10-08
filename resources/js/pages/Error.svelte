<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import Logo from '@/components/Logo.svelte';
    import { APP_TITLE } from '@/lib/brand';
    import { home } from '@/routes';

    let { status }: { status: 403 | 404 | 429 | 500 | 503 } = $props();

    const copy = {
        403: { title: "You don't have access to this page", body: 'It belongs to another account or service area. If you think you should see it, contact the New Service Department.' },
        404: { title: 'Page not found', body: 'The link may be wrong, or the page has been removed.' },
        429: { title: 'Too many attempts', body: 'Please wait a minute and try again.' },
        500: { title: 'Something went wrong on our side', body: "It's been logged. Try again in a moment; your drafts are saved." },
        503: { title: 'Back shortly', body: 'The app is being updated. Please try again in a minute.' },
    } as const;

    const text = $derived(copy[status] ?? copy[500]);
    const nsd = $derived(page.props.nsd);
</script>

<svelte:head>
    <title>{text.title}</title>
</svelte:head>

<main class="flex min-h-dvh items-center justify-center bg-bg px-4 py-10 text-ink">
    <div class="flex w-full max-w-md flex-col gap-5 rounded-3xl border border-line bg-sf p-7 shadow-[0_20px_50px_rgba(10,30,18,.08)]">
        <div class="flex items-center gap-3">
            <Logo size={44} />
            <div class="flex flex-col">
                <b class="text-[15px]">Kaduna Electric</b>
                <span class="text-xs text-mut">{APP_TITLE}</span>
            </div>
        </div>
        <div class="flex flex-col gap-2">
            <span class="font-mono text-sm font-semibold text-brand">Error {status}</span>
            <h1 class="text-[24px] leading-tight font-extrabold">{text.title}</h1>
            <p class="text-[15px] text-mut">{text.body}</p>
        </div>
        <Link href={home.url()} class="flex h-12 items-center justify-center rounded-xl bg-pri font-bold text-on-pri no-underline">Go to my home page</Link>
        {#if nsd?.phone || nsd?.email}
            <p class="text-[13px] text-mut">
                New Service Department:
                {#if nsd.phone}<a href="tel:{nsd.phone.replace(/\s+/g, '')}" class="font-semibold">{nsd.phone}</a>{/if}
                {#if nsd.phone && nsd.email} · {/if}
                {#if nsd.email}<a href="mailto:{nsd.email}" class="font-semibold">{nsd.email}</a>{/if}
            </p>
        {/if}
    </div>
</main>
