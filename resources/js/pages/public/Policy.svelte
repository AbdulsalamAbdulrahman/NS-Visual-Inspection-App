<script module lang="ts">
    export { default as layout } from '@/layouts/PublicLayout.svelte';
</script>

<script lang="ts">
    type Props = {
        page: 'terms' | 'privacy' | 'refunds';
        /** The company that developed and runs KENS for NSD; named in the terms only. */
        operator: { name: string; rc: string };
    };

    let { page, operator }: Props = $props();

    const contact = 'the New Service Department of Kaduna Electric or your nearest Kaduna Electric area office';

    const UPDATED = '17 November 2026';

    type Section = { heading: string; paragraphs: string[] };

    const policies = $derived<Record<Props['page'], { title: string; intro: string; sections: Section[] }>>({
        terms: {
            title: 'Terms of service',
            intro: `These terms apply to everyone who uses KENS (kens.buildingelectcert.com.ng), the building electrical inspection platform of the New Service Department of Kaduna Electric. KENS was developed by ${operator.name} (RC ${operator.rc}), which operates it for the department and collects the inspection fees.`,
            sections: [
                {
                    heading: 'The service',
                    paragraphs: [
                        'KENS lets licensed Building Electrical Inspectors submit inspection reports for buildings applying for a new electricity connection, pay the official inspection fee, and receive a certificate once the report is approved. Anyone can check a certificate on the verify page.',
                    ],
                },
                {
                    heading: 'Accounts',
                    paragraphs: [
                        'Accounts are created by Kaduna Electric\'s New Service Department for registered inspectors, reviewers and staff. There is no public sign-up. You must keep your password private and tell us at once if you think someone else has used your account.',
                        'An account may be suspended if its licence or registration lapses, or if it is used to submit false information.',
                    ],
                },
                {
                    heading: 'Inspection reports',
                    paragraphs: [
                        'Inspectors are responsible for the accuracy of the reports, readings, photos and documents they submit. A report must describe a real inspection carried out by the inspector on the building named in it.',
                        'Payment submits a report for review. It does not guarantee approval: the Inspection Review Committee and Kaduna Electric may ask for changes, and a certificate is issued only after final approval.',
                    ],
                },
                {
                    heading: 'Fees and payment',
                    paragraphs: [
                        'Inspection fees are set by Kaduna Electric per building type, size and supply, and are published on the home page. The amount due is shown before payment, including any payment processing charge. The fee in force on the day of payment applies.',
                        `Payments are processed by Monnify (Moniepoint). The fee is collected by the platform operator and settled to the parties involved in the inspection and review. KENS does not see or store card details.`,
                        `A corrected report sent back for review after changes are requested is not charged again. For questions about a payment, contact ${contact}.`,
                    ],
                },
                {
                    heading: 'Certificates',
                    paragraphs: [
                        'A certificate confirms that the inspection report was reviewed and approved. It is not a guarantee against faults that were not visible or not reported at the time of inspection. Altering or copying a certificate is not allowed; its number and QR code can be checked on the verify page.',
                    ],
                },
                {
                    heading: 'Liability',
                    paragraphs: [
                        'KENS is provided with reasonable care but is not guaranteed to be available at all times. Neither Kaduna Electric nor the platform operator is liable for losses caused by inaccurate information submitted by users, or by events outside their reasonable control. Nothing in these terms limits liability that cannot be limited under Nigerian law.',
                    ],
                },
                {
                    heading: 'Law and changes',
                    paragraphs: [
                        'These terms are governed by the laws of the Federal Republic of Nigeria. We may update them; the date below shows the latest version, and continued use means you accept it.',
                    ],
                },
            ],
        },
        privacy: {
            title: 'Privacy policy',
            intro: 'This policy explains how KENS, the building electrical inspection platform of the New Service Department of Kaduna Electric, handles personal data, in line with the Nigeria Data Protection Act 2023.',
            sections: [
                {
                    heading: 'What we collect',
                    paragraphs: [
                        'Inspectors and staff: name, email address, phone number, licence and registration details, documents uploaded at onboarding, signature and bank account details for fee settlement.',
                        'Buildings and owners: the owner\'s name, phone number and email address, the building\'s address and GPS location, photos and test readings recorded during the inspection.',
                        'Payments: amounts, dates and payment references from Monnify. Card details are entered on Monnify\'s secure page and never reach KENS.',
                    ],
                },
                {
                    heading: 'How we use it',
                    paragraphs: [
                        'To run the inspection, payment, review and certification process; to send receipts, reports and certificates by email; to settle fees to the parties involved; to let the public verify a certificate; and to meet legal and regulatory duties.',
                    ],
                },
                {
                    heading: 'Who we share it with',
                    paragraphs: [
                        'Kaduna Electric\'s New Service Department and service representatives, the Inspection Review Committee, the company that operates KENS for the department, NEMSA where the law requires it, and Monnify for payments and settlements. Personal data is not sold.',
                        'The public verify page shows only the certificate status, dates, service area and masked names.',
                    ],
                },
                {
                    heading: 'How long we keep it',
                    paragraphs: [
                        'Inspection reports, certificates and payment records are kept for as long as Kaduna Electric needs them as connection and financial records, and as the law requires. Account data is kept while the account is active.',
                    ],
                },
                {
                    heading: 'Security',
                    paragraphs: [
                        'Data is sent over encrypted connections. Uploaded files are stored privately and shown only to signed-in users with the right role, or through signed links emailed to the building owner.',
                    ],
                },
                {
                    heading: 'Your rights',
                    paragraphs: [
                        `You can ask to see, correct or delete your personal data, or object to its use, subject to the records that must be kept by law. Contact ${contact}.`,
                    ],
                },
            ],
        },
        refunds: {
            title: 'Refund policy',
            intro: 'This policy covers inspection fees paid through KENS.',
            sections: [
                {
                    heading: 'When we refund',
                    paragraphs: [
                        'You get a full refund if you were charged twice for the same inspection, if you were charged but no ticket number was issued, or if you were charged more than the published fee because of a system error.',
                    ],
                },
                {
                    heading: 'When we don\'t',
                    paragraphs: [
                        'The fee pays for the review of the inspection report. Once a report has been submitted and a ticket issued, the fee is not refunded because the report was sent back for changes or not approved. A corrected report can be resubmitted at no extra cost.',
                    ],
                },
                {
                    heading: 'How to ask',
                    paragraphs: [
                        `Contact ${contact} within 30 days of the payment, with the ticket number or payment reference and the reason. We reply within 5 working days.`,
                        'Approved refunds are paid back to the original payment method within 14 working days. The payment processing charge is refunded with the fee when the error was ours.',
                    ],
                },
            ],
        },
    });

    const policy = $derived(policies[page]);
</script>

<svelte:head>
    <title>{policy.title} · KENS</title>
</svelte:head>

<article class="mx-auto flex w-full max-w-3xl flex-col gap-6 px-4 py-12 md:py-16">
    <header class="flex flex-col gap-2">
        <h1 class="text-[32px] leading-tight font-extrabold tracking-[-0.01em]">{policy.title}</h1>
        <p class="text-sm text-mut">Last updated {UPDATED}</p>
        <p class="text-base leading-relaxed">{policy.intro}</p>
    </header>

    {#each policy.sections as section (section.heading)}
        <section class="flex flex-col gap-2">
            <h2 class="text-xl font-bold">{section.heading}</h2>
            {#each section.paragraphs as paragraph, index (index)}
                <p class="text-base leading-relaxed text-mut">{paragraph}</p>
            {/each}
        </section>
    {/each}

</article>
