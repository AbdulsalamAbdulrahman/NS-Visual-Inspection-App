<script lang="ts">
    import { Link, page } from '@inertiajs/svelte';
    import type { Snippet } from 'svelte';
    import Logo from '@/components/Logo.svelte';
    import { APP_TITLE } from '@/lib/brand';
    import { home, login, policy } from '@/routes';
    import Login from '~icons/ms/login';

    let { children }: { children: Snippet } = $props();

    const operator = $derived(page.props.operator);
    const year = new Date().getFullYear();

    const sections = [
        { href: '#about', label: 'About' },
        { href: '#how-it-works', label: 'How it works' },
        { href: '#fees', label: 'Fees' },
        { href: '#contact', label: 'Contact' },
    ];
</script>

<div class="flex min-h-dvh flex-col bg-bg text-ink">
    <header class="sticky top-0 z-20 border-b border-line bg-sf/95 backdrop-blur">
        <div class="mx-auto flex h-16 w-full max-w-6xl items-center gap-3 px-4">
            <Link href={home()} class="flex min-w-0 items-center gap-2.5">
                <Logo size={40} />
                <span class="flex min-w-0 flex-col leading-tight">
                    <b class="text-[17px] font-extrabold">KENS</b>
                    <span class="truncate text-xs text-mut">Kaduna Electric · New Service Department</span>
                </span>
            </Link>
            <nav aria-label="Sections" class="ml-auto hidden items-center gap-1 md:flex">
                {#each sections as section (section.href)}
                    <a
                        href={home().url + section.href}
                        class="rounded-lg px-3 py-2 text-[15px] font-semibold text-mut hover:bg-bg hover:text-ink"
                    >
                        {section.label}
                    </a>
                {/each}
            </nav>
            <Link
                href={login()}
                class="ml-auto flex h-11 flex-none items-center gap-1.5 rounded-xl bg-pri px-4 font-bold text-on-pri md:ml-2"
            >
                <Login class="size-5" aria-hidden="true" />Sign in
            </Link>
        </div>
    </header>

    <main class="flex-1">
        {@render children()}
    </main>

    <footer class="border-t border-line bg-sf">
        <div class="mx-auto grid w-full max-w-6xl gap-8 px-4 py-10 md:grid-cols-[1.4fr_1fr_1fr]">
            <div class="flex flex-col gap-2">
                <b class="text-base">KENS · {APP_TITLE}</b>
                <p class="text-sm leading-relaxed text-mut">
                    Operated by {operator.name} (RC {operator.rc}) for the New Service Department of Kaduna Electric.
                    Payments are processed by Monnify.
                </p>
            </div>
            <div class="flex flex-col gap-2 text-sm">
                <b class="mono-caps text-xs text-mut">Policies</b>
                <Link href={policy('terms')} class="hover:underline">Terms of service</Link>
                <Link href={policy('privacy')} class="hover:underline">Privacy policy</Link>
            </div>
            <div class="flex flex-col gap-2 text-sm">
                <b class="mono-caps text-xs text-mut">Contact</b>
                {#each operator.phones as phone (phone)}
                    <a href="tel:{phone.replace(/[^\d+]/g, '')}" class="font-mono hover:underline">{phone}</a>
                {/each}
                <a href="mailto:{operator.email}" class="break-all hover:underline">{operator.email}</a>
                <span class="text-mut">{operator.address}</span>
            </div>
        </div>
        <div class="border-t border-line">
            <p class="mx-auto w-full max-w-6xl px-4 py-4 text-xs text-mut">
                © {year} {operator.name}. All rights reserved.
            </p>
        </div>
    </footer>
</div>
