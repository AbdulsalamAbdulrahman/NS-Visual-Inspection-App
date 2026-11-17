<script module lang="ts">
    export { default as layout } from '@/layouts/PublicLayout.svelte';
</script>

<script lang="ts">
    import { Link, router } from '@inertiajs/svelte';
    import { APP_TITLE } from '@/lib/brand';
    import { login, verify } from '@/routes';
    import Assignment from '~icons/ms/assignment';
    import Engineering from '~icons/ms/engineering';
    import FactCheck from '~icons/ms/fact-check';
    import Payments from '~icons/ms/payments';
    import QrCode2 from '~icons/ms/qr-code-2';
    import ReceiptLong from '~icons/ms/receipt-long';
    import Search from '~icons/ms/search';
    import Verified from '~icons/ms/verified';

    type FeeRow = { building: string; size: string; single: string | null; three: string | null };

    type Props = {
        fees: { purpose: string; rows: FeeRow[] }[];
        /** "1.5%, capped at ₦2,000", or null when no charge is added. */
        charge: string | null;
    };

    let { fees, charge }: Props = $props();


    let ticket = $state('');

    function check(event: SubmitEvent): void {
        event.preventDefault();
        const value = ticket.trim().toUpperCase();

        if (value) {
            router.visit(verify.url(value));
        }
    }

    const steps = [
        {
            icon: Engineering,
            title: 'Inspect on site',
            body: 'A licensed Building Electrical Inspector inspects the building\'s wiring and fills the inspection report on the phone, with photos, readings and the building\'s location.',
        },
        {
            icon: Payments,
            title: 'Pay the official fee',
            body: 'The inspector pays the official inspection fee online by card, bank transfer or USSD. Payment submits the report and issues a ticket number. The building owner gets a receipt by email.',
        },
        {
            icon: FactCheck,
            title: 'Review',
            body: 'The Inspection Review Committee checks the report against the required standards, then Kaduna Electric\'s New Service Department gives final approval or asks for changes.',
        },
        {
            icon: Verified,
            title: 'Certificate',
            body: 'An approved building gets a certificate with a QR code. Anyone can check it on this website before the building is connected to the grid.',
        },
    ];

    const services = [
        {
            icon: Assignment,
            title: 'Digital inspection reports',
            body: 'Building details, circuits, test readings, photos and the inspector\'s signature in one report, filled on site even without a network connection.',
        },
        {
            icon: ReceiptLong,
            title: 'Fee payment and receipts',
            body: 'Official fees set by building type and size, paid securely through Monnify. Each payment gets a ticket number and an emailed receipt.',
        },
        {
            icon: FactCheck,
            title: 'Review and approval',
            body: 'Reports go to the Inspection Review Committee and Kaduna Electric for review. Reports that need corrections are sent back and can be resubmitted at no extra cost.',
        },
        {
            icon: QrCode2,
            title: 'Certificate verification',
            body: 'Every certificate carries a QR code and number that anyone can check online to confirm it is genuine.',
        },
    ];

    const audiences = [
        {
            title: 'Building Electrical Inspectors',
            body: 'Licensed inspectors registered with Kaduna Electric\'s New Service Department. Accounts are created by the department; there is no public sign-up.',
        },
        {
            title: 'Building owners',
            body: 'Owners applying for a new electricity connection receive the payment receipt, the inspection report and, once approved, the certificate by email.',
        },
        {
            title: 'Kaduna Electric and reviewers',
            body: 'The New Service Department, service representatives and the Inspection Review Committee review, approve and track inspections in one place.',
        },
    ];
</script>

<svelte:head>
    <title>KENS · Building electrical inspection and certification</title>
    <meta
        name="description"
        content="KENS is the online platform for building electrical inspection reports, official inspection fee payment and certificates for new electricity connections on Kaduna Electric's network."
    />
</svelte:head>

<!-- Hero -->
<section class="relative overflow-hidden bg-hero text-white">
    <div
        class="absolute top-[62%] -left-10 h-10 w-[160%] -rotate-[26deg] bg-lime md:top-[55%] md:h-16"
        aria-hidden="true"
    ></div>
    <div
        class="absolute top-[82%] left-[140px] h-10 w-[160%] -rotate-[26deg] bg-leaf md:top-[72%] md:h-16"
        aria-hidden="true"
    ></div>
    <div class="relative mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 pt-12 pb-40 md:pt-20 md:pb-56">
        <span class="mono-caps text-[13px] opacity-85">Kaduna Electric · New Service Department</span>
        <h1 class="max-w-3xl text-[34px] leading-[1.08] font-extrabold tracking-[-0.02em] md:text-[52px]">
            Building electrical inspection and certification for new connections
        </h1>
        <p class="max-w-2xl text-[17px] leading-relaxed opacity-90 md:text-[19px]">
            KENS ({APP_TITLE}) is where licensed Building Electrical Inspectors submit inspection reports, pay the
            official inspection fee and get a certificate that anyone can verify, before a building is connected to
            the grid.
        </p>
        <div class="flex flex-wrap gap-3">
            <Link
                href={login()}
                class="flex h-14 items-center rounded-xl bg-white px-6 text-base font-bold text-hero"
            >
                Inspector and staff sign in
            </Link>
            <a
                href="#verify"
                class="flex h-14 items-center rounded-xl border-2 border-white/70 px-6 text-base font-bold text-white"
            >
                Verify a certificate
            </a>
        </div>
    </div>
</section>

<!-- About -->
<section id="about" class="mx-auto grid w-full max-w-6xl scroll-mt-20 gap-8 px-4 py-14 md:grid-cols-[1fr_1fr] md:py-20">
    <div class="flex flex-col gap-4">
        <span class="mono-caps text-xs text-brand">About KENS</span>
        <h2 class="text-[28px] leading-tight font-extrabold tracking-[-0.01em]">
            Safe wiring before every new connection
        </h2>
        <p class="text-base leading-relaxed text-mut">
            Before Kaduna Electric connects a new building, its electrical installation must be inspected by a licensed
            Building Electrical Inspector. KENS replaces the paper process: the report is filled on site, the official
            fee is paid online, and the report is reviewed and certified in one traceable flow.
        </p>
        <p class="text-base leading-relaxed text-mut">
            KENS belongs to the New Service Department of Kaduna Electric. Inspection fees are paid online through
            Monnify and settled to the parties involved in the inspection and review.
        </p>
    </div>
    <div class="flex flex-col gap-3">
        {#each audiences as audience (audience.title)}
            <div class="flex flex-col gap-1 rounded-2xl border border-line bg-sf p-5">
                <b class="text-base">{audience.title}</b>
                <p class="text-[15px] leading-relaxed text-mut">{audience.body}</p>
            </div>
        {/each}
    </div>
</section>

<!-- How it works -->
<section id="how-it-works" class="scroll-mt-20 border-y border-line bg-sf">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-14 md:py-20">
        <div class="flex flex-col gap-2">
            <span class="mono-caps text-xs text-brand">How it works</span>
            <h2 class="text-[28px] leading-tight font-extrabold tracking-[-0.01em]">From inspection to certificate</h2>
        </div>
        <ol class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            {#each steps as step, index (step.title)}
                {@const Icon = step.icon}
                <li class="flex flex-col gap-3 rounded-2xl border border-line bg-bg p-5">
                    <div class="flex items-center gap-3">
                        <span class="flex size-11 items-center justify-center rounded-xl bg-pri text-on-pri">
                            <Icon class="size-6" aria-hidden="true" />
                        </span>
                        <span class="font-mono text-sm text-mut">Step {index + 1}</span>
                    </div>
                    <b class="text-lg">{step.title}</b>
                    <p class="text-[15px] leading-relaxed text-mut">{step.body}</p>
                </li>
            {/each}
        </ol>
    </div>
</section>

<!-- Services -->
<section class="mx-auto flex w-full max-w-6xl flex-col gap-8 px-4 py-14 md:py-20">
    <div class="flex flex-col gap-2">
        <span class="mono-caps text-xs text-brand">Services</span>
        <h2 class="text-[28px] leading-tight font-extrabold tracking-[-0.01em]">What KENS provides</h2>
    </div>
    <div class="grid gap-4 md:grid-cols-2">
        {#each services as service (service.title)}
            {@const Icon = service.icon}
            <div class="flex gap-4 rounded-2xl border border-line bg-sf p-5">
                <Icon class="size-8 flex-none text-brand" aria-hidden="true" />
                <div class="flex flex-col gap-1">
                    <b class="text-base">{service.title}</b>
                    <p class="text-[15px] leading-relaxed text-mut">{service.body}</p>
                </div>
            </div>
        {/each}
    </div>
</section>

<!-- Fees -->
<section id="fees" class="scroll-mt-20 border-y border-line bg-sf">
    <div class="mx-auto flex w-full max-w-6xl flex-col gap-6 px-4 py-14 md:py-20">
        <div class="flex flex-col gap-2">
            <span class="mono-caps text-xs text-brand">Fees</span>
            <h2 class="text-[28px] leading-tight font-extrabold tracking-[-0.01em]">Official inspection fees</h2>
            <p class="max-w-3xl text-base leading-relaxed text-mut">
                The fee is charged per structure and depends on the building type, its size and the supply (single or
                three phase). The exact amount is shown before payment.
                {#if charge}A payment processing charge of {charge} is added at checkout.{/if}
            </p>
        </div>

        {#if fees.length === 0}
            <p class="rounded-2xl border border-line bg-bg p-5 text-mut">
                Fees are being updated. The current fee is shown before payment.
            </p>
        {:else}
            <div class="flex flex-col gap-3">
                {#each fees as group (group.purpose)}
                    <details class="group rounded-2xl border border-line bg-bg" open={group === fees[0]}>
                        <summary
                            class="flex min-h-14 cursor-pointer items-center justify-between gap-3 px-5 text-base font-bold"
                        >
                            {group.purpose}
                            <span class="text-sm font-normal text-mut group-open:hidden">Show fees</span>
                            <span class="hidden text-sm font-normal text-mut group-open:inline">Hide</span>
                        </summary>
                        <div class="overflow-x-auto px-2 pb-3 md:px-5">
                            <table class="w-full min-w-[460px] text-left text-[15px]">
                                <thead class="text-xs text-mut">
                                    <tr class="border-b border-line">
                                        <th scope="col" class="py-2 pr-3 font-semibold">Building</th>
                                        <th scope="col" class="py-2 pr-3 font-semibold">Size</th>
                                        <th scope="col" class="py-2 pr-3 text-right font-semibold">Single phase</th>
                                        <th scope="col" class="py-2 text-right font-semibold">Three phase</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {#each group.rows as row (row.building + row.size)}
                                        <tr class="border-b border-line last:border-0">
                                            <td class="py-2.5 pr-3">{row.building}</td>
                                            <td class="py-2.5 pr-3 text-mut">{row.size}</td>
                                            <td class="py-2.5 pr-3 text-right font-mono tabular-nums">
                                                {#if row.single}{row.single}{:else}<span class="text-mut" aria-label="Not offered">—</span>{/if}
                                            </td>
                                            <td class="py-2.5 text-right font-mono tabular-nums">
                                                {#if row.three}{row.three}{:else}<span class="text-mut" aria-label="Not offered">—</span>{/if}
                                            </td>
                                        </tr>
                                    {/each}
                                </tbody>
                            </table>
                        </div>
                    </details>
                {/each}
            </div>
            <p class="text-sm text-mut">
                Amounts are per structure. “—” means the option isn't offered for that building. Fees are set by Kaduna
                Electric and may change; the amount in force on the day of payment applies.
            </p>
        {/if}
    </div>
</section>

<!-- Verify -->
<section id="verify" class="mx-auto flex w-full max-w-6xl scroll-mt-20 flex-col gap-5 px-4 py-14 md:py-20">
    <div class="flex flex-col gap-2">
        <span class="mono-caps text-xs text-brand">Verify</span>
        <h2 class="text-[28px] leading-tight font-extrabold tracking-[-0.01em]">Check a certificate</h2>
        <p class="max-w-2xl text-base leading-relaxed text-mut">
            Enter the number printed on the certificate, or scan its QR code with your phone camera.
        </p>
    </div>
    <form class="flex max-w-xl gap-2" onsubmit={check}>
        <label class="sr-only" for="ticket">Certificate number</label>
        <input
            id="ticket"
            bind:value={ticket}
            class="h-14 min-w-0 flex-1 rounded-xl border-[1.5px] border-line bg-sf px-3.5 font-mono text-[15px] uppercase outline-none focus:border-pri"
            placeholder="KE-NSD-2026-000123"
            autocomplete="off"
            autocapitalize="characters"
            spellcheck="false"
        />
        <button type="submit" class="flex h-14 items-center gap-1.5 rounded-xl bg-pri px-5 font-bold text-on-pri">
            <Search class="size-5" aria-hidden="true" />Check
        </button>
    </form>
</section>
