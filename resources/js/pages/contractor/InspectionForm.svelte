<script lang="ts">
    import { router } from '@inertiajs/svelte';
    import { onDestroy, tick } from 'svelte';
    import Button from '@/components/Button.svelte';
    import CircuitsStep from '@/components/inspection/CircuitsStep.svelte';
    import PayPanel from '@/components/inspection/PayPanel.svelte';
    import ReviewStep from '@/components/inspection/ReviewStep.svelte';
    import SaveStatusChip from '@/components/inspection/SaveStatusChip.svelte';
    import StepA from '@/components/inspection/StepA.svelte';
    import StepAttachments from '@/components/inspection/StepAttachments.svelte';
    import StepB1 from '@/components/inspection/StepB1.svelte';
    import StepB2 from '@/components/inspection/StepB2.svelte';
    import StepB3 from '@/components/inspection/StepB3.svelte';
    import StepC from '@/components/inspection/StepC.svelte';
    import StepD from '@/components/inspection/StepD.svelte';
    import Toaster from '@/components/Toaster.svelte';
    import { Autosave, draftPayload } from '@/lib/inspection/autosave.svelte';
    import { missingItems } from '@/lib/inspection/checklist';
    import { STEP_COUNT, STEPS, backLabel, nextLabel } from '@/lib/inspection/steps';
    import type { Draft, FormOptions, Inspector } from '@/lib/inspection/types';
    import { index as home, pay as payPage } from '@/routes/inspections';
    import { store as payStore } from '@/routes/inspections/pay';
    import ArrowBack from '~icons/ms/arrow-back';
    import ArrowForward from '~icons/ms/arrow-forward';
    import Check from '~icons/ms/check';
    import Close from '~icons/ms/close';
    import CloudOff from '~icons/ms/cloud-off';
    import ErrorIcon from '~icons/ms/error';
    import Lock from '~icons/ms/lock';
    import Payments from '~icons/ms/payments';
    import Save from '~icons/ms/save';

    type Props = {
        inspection: Draft;
        step: number;
        areas: { id: number; name: string }[];
        options: FormOptions;
        inspector: Inspector;
        fee: { amount: string; effectiveFrom: string } | null;
    };

    let { inspection, step: initialStep, areas, options, inspector, fee }: Props = $props();

    // The form owns its working copy; the server is updated in the background.
    // svelte-ignore state_referenced_locally
    let draft = $state<Draft>($state.snapshot(inspection) as Draft);
    // svelte-ignore state_referenced_locally
    let step = $state(initialStep);
    /** New signature PNG to send (undefined = unchanged, null = cleared). */
    let signature = $state<string | null | undefined>(undefined);
    /** Steps where the contractor tapped Next with gaps (CF-03). */
    let checked = $state<Set<number>>(new Set());
    let online = $state(typeof navigator === 'undefined' ? true : navigator.onLine);
    let leaving = $state(false);

    // svelte-ignore state_referenced_locally
    const saver = new Autosave(inspection.uuid, inspection.updated_at, (response) => {
        if (signature !== undefined) {
            draft.signature_url = response.signature_url;
            signature = undefined;
        }
    });
    // svelte-ignore state_referenced_locally
    saver.prime(draftPayload($state.snapshot(inspection) as Draft));

    const activeAreas = $derived(new Set(areas.map((a) => a.id)));
    const missing = $derived(missingItems(draft, activeAreas));
    const meta = $derived(STEPS[step - 1]);
    const isReview = $derived(step === STEP_COUNT);
    const showErrors = $derived(isReview || checked.has(step));
    const stepMissing = $derived(missing.filter((m) => m.step === step));

    /** Field-level messages for the current step (CF-03 copy where designed). */
    const errors = $derived.by(() => {
        if (!showErrors) {
            return {} as Record<string, string>;
        }

        const copy: Record<string, string> = {
            form74_no: 'Enter the Form 74 number from the application',
            service_area_id: 'Choose the area office for this property',
            gps: 'Capture GPS while standing at the property',
            layout: 'Add the electrical layout & load schedule',
            photos: 'Add at least one photo of the DB & earthing',
            signature: 'Sign here to attest the report',
        };

        return Object.fromEntries(
            missing.filter((m) => m.step === step).map((m) => [m.key, copy[m.key] ?? 'Required']),
        ) as Record<string, string>;
    });

    const title = $derived(isReview ? 'Review inspection' : 'New inspection');
    const subtitle = $derived(draft.owner_name?.trim() || 'Draft · not yet named');
    const desktopSubtitle = $derived([draft.owner_name?.trim(), draft.form74_no?.trim()].filter(Boolean).join(' · ') || 'Draft · not yet named');

    const descriptions: Record<number, string> = {
        1: 'Who and where this inspection is for.',
        2: "Minimums are shown as hints; out-of-range readings warn but don't block.",
        3: 'Circuit breaker and cut-out fuse on the incoming supply.',
        4: 'Tick what you saw at the mains position.',
        5: 'One row per circuit. Condition is required for each.',
        6: 'Earthing arrangement, boards, cable and wiring method.',
        7: 'Layout drawing, site photos and the calibration certificate.',
        8: 'Your licence details are added from your profile.',
        9: '',
    };

    // Background save whenever the draft, step or signature changes.
    $effect(() => {
        const payload: Record<string, unknown> = { ...draftPayload($state.snapshot(draft) as Draft), current_step: step };

        if (signature !== undefined) {
            payload.signature = signature;
        }

        saver.schedule(payload);
    });

    onDestroy(() => saver.destroy());

    async function goTo(target: number): Promise<void> {
        step = Math.min(Math.max(target, 1), STEP_COUNT);
        await tick();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    /** Next: flag the step's gaps once (CF-03); a second tap continues anyway. */
    function next(): void {
        if (stepMissing.length > 0 && !checked.has(step)) {
            checked = new Set([...checked, step]);
            window.scrollTo({ top: 0, behavior: 'smooth' });

            return;
        }

        void goTo(step + 1);
    }

    function back(): void {
        if (step === 1) {
            void leave();
        } else {
            void goTo(step - 1);
        }
    }

    async function saveNow(): Promise<void> {
        await saver.flush();
    }

    async function leave(): Promise<void> {
        leaving = true;
        await saver.flush();
        router.visit(home.url());
    }

    let paying = $state(false);
    const payDisabled = $derived(missing.length > 0 || !fee || !online || paying);
    const payLabel = $derived(
        missing.length > 0
            ? `Fix ${missing.length} item${missing.length === 1 ? '' : 's'} to pay`
            : !online
              ? 'Connect to pay'
              : null,
    );

    /** Payment always needs network. Desktop pays from the panel; phones go to CP-01 first. */
    async function pay(desktop: boolean): Promise<void> {
        paying = true;
        const saved = await saver.flush();

        if (!saved && saver.status !== 'saved') {
            paying = false;

            return;
        }

        if (desktop) {
            router.post(payStore.url(draft.uuid), {}, { onFinish: () => (paying = false) });
        } else {
            router.visit(payPage.url(draft.uuid));
        }
    }

    function setSignature(dataUrl: string | null): void {
        signature = dataUrl;
        draft.signature_url = dataUrl;
    }

    const progress = $derived(Math.round((step / STEP_COUNT) * 100));
</script>

<svelte:window ononline={() => (online = true)} onoffline={() => (online = false)} />

<svelte:head>
    <title>{title} · KENS</title>
</svelte:head>

{#snippet stepBody()}
    {#if showErrors && !isReview && stepMissing.length > 0}
        <!-- CF-03 -->
        <div class="flex items-start gap-2.5 rounded-[14px] bg-bad-bg px-4 py-3.5" role="alert">
            <ErrorIcon class="size-6 flex-none text-bad" />
            <div class="flex flex-col gap-1">
                <b class="text-base text-bad">{stepMissing.length} field{stepMissing.length === 1 ? '' : 's'} need{stepMissing.length === 1 ? 's' : ''} attention</b>
                <span class="text-sm leading-[1.45]">
                    {stepMissing.map((m) => m.label).join(' · ')}. You can still Save draft, or
                    <button type="button" class="font-bold text-brand underline" onclick={() => goTo(step + 1)}>continue anyway</button> and finish later.
                </span>
            </div>
        </div>
    {/if}

    {#if step === 1}
        <StepA bind:draft {options} {areas} {errors} />
    {:else if step === 2}
        <StepB1 bind:draft {errors} />
    {:else if step === 3}
        <StepB2 bind:draft {errors} />
    {:else if step === 4}
        <StepB3 bind:draft {options} {errors} />
    {:else if step === 5}
        {#if errors.circuits}
            <span class="flex items-center gap-1 text-sm font-semibold text-bad"><ErrorIcon class="size-[18px]" />Add at least one circuit.</span>
        {/if}
        <CircuitsStep bind:circuits={draft.circuits} descriptions={options.circuitDescription} {showErrors} />
    {:else if step === 6}
        <StepC bind:draft {options} {errors} />
    {:else if step === 7}
        <StepAttachments bind:draft {errors} />
    {:else if step === 8}
        <StepD bind:draft {inspector} {errors} onsignature={setSignature} />
    {:else}
        <div class="flex flex-col gap-2.5 lg:grid lg:grid-cols-[minmax(0,1fr)_420px] lg:items-start lg:gap-8">
            <div class="flex flex-col gap-2.5 lg:gap-3.5">
                <ReviewStep {draft} {options} {areas} {inspector} {missing} onedit={goTo} />
            </div>
            <!-- CK-03 payment panel -->
            <div class="hidden lg:sticky lg:top-0 lg:block">
                <PayPanel {fee}>
                    {#snippet action()}
                        <Button block class="font-extrabold" disabled={payDisabled} onclick={() => pay(true)}>
                            <Lock />{payLabel ?? (paying ? 'Opening Monnify…' : 'Pay with Monnify & submit')}
                        </Button>
                    {/snippet}
                </PayPanel>
            </div>
        </div>
    {/if}
{/snippet}

<div class="flex min-h-dvh flex-col bg-bg lg:h-dvh">
    <!-- Phone header: close/back, title, save status, step progress (CF-01) -->
    <header class="sticky top-0 z-20 flex-none bg-sf pt-[env(safe-area-inset-top)] lg:hidden">
        <div class="flex flex-col gap-2.5 border-b border-line px-3 pt-1 pb-3">
            <div class="flex items-center gap-1">
                <button
                    type="button"
                    class="flex size-12 items-center justify-center rounded-xl hover:bg-sf2"
                    aria-label={step === 1 ? 'Close and go to my inspections' : 'Previous step'}
                    onclick={back}
                    disabled={leaving}
                >
                    {#if step === 1}<Close class="size-6" />{:else}<ArrowBack class="size-6" />{/if}
                </button>
                <div class="flex min-w-0 flex-1 flex-col">
                    <b class="truncate text-[17px]">{title}</b>
                    <span class="truncate text-[13px] text-mut">{subtitle}</span>
                </div>
                <span class="pr-2"><SaveStatusChip status={saver.status} savedAt={saver.savedAt} /></span>
            </div>
            <div class="flex flex-col gap-2 px-2">
                <div class="flex justify-between text-sm">
                    <b>{meta.label}</b>
                    <span class="font-mono text-mut">{step} / {STEP_COUNT}</span>
                </div>
                <div class="h-1.5 rounded-full bg-sf2" role="progressbar" aria-valuenow={step} aria-valuemin={1} aria-valuemax={STEP_COUNT} aria-label="Form progress">
                    <div class="h-full rounded-full bg-mid transition-[width]" style:width="{progress}%"></div>
                </div>
            </div>
        </div>
        {#if !online || saver.status === 'offline'}
            <!-- CF-10 -->
            <div class="flex items-center gap-2.5 bg-info-bg px-4 py-2.5" role="status">
                <CloudOff class="size-6 flex-none text-info" />
                <span class="flex-1 text-sm leading-[1.4]"><b>No network.</b> Draft is saved on this phone and will sync when you're back online.</span>
            </div>
        {/if}
    </header>

    <!-- Desktop top bar (CK-02) -->
    <header class="hidden h-[68px] flex-none items-center gap-3.5 border-b border-line bg-sf px-6 lg:flex">
        <button type="button" class="flex size-11 items-center justify-center rounded-[10px] hover:bg-sf2" aria-label="Close and go to my inspections" onclick={leave} disabled={leaving}>
            <Close class="size-6" />
        </button>
        <div class="flex min-w-0 flex-col">
            <b class="text-base">{title}</b>
            <span class="truncate text-[13px] text-mut">{desktopSubtitle}</span>
        </div>
        <span class="ml-auto"><SaveStatusChip status={saver.status} savedAt={saver.savedAt} long /></span>
        <Button variant="outline" size="sm" class="border-[1.5px] text-[15px]" onclick={saveNow}><Save />Save draft</Button>
    </header>
    {#if !online || saver.status === 'offline'}
        <div class="hidden items-center gap-2.5 bg-info-bg px-6 py-2.5 lg:flex" role="status">
            <CloudOff class="size-6 text-info" />
            <span class="text-sm"><b>No network.</b> Draft is saved on this device and will sync when you're back online.</span>
        </div>
    {/if}

    <div class="flex flex-1 flex-col lg:grid lg:min-h-0 lg:grid-cols-[300px_1fr]">
        <!-- Desktop step rail -->
        <nav class="hidden flex-col gap-1 border-r border-line bg-sf px-4 py-6 lg:flex" aria-label="Form steps">
            <div class="flex flex-col gap-2 px-3 pb-3.5">
                <div class="flex justify-between text-[13px] text-mut"><span>Progress</span><span class="font-mono">{step} / {STEP_COUNT}</span></div>
                <div class="h-1.5 rounded-full bg-sf2"><div class="h-full rounded-full bg-mid" style:width="{progress}%"></div></div>
            </div>
            {#each STEPS as s (s.n)}
                {@const current = s.n === step}
                {@const done = s.n < STEP_COUNT && !missing.some((m) => m.step === s.n)}
                {@const flagged = checked.has(s.n) && missing.some((m) => m.step === s.n)}
                <button
                    type="button"
                    aria-current={current ? 'step' : undefined}
                    class={['flex h-12 items-center gap-3 rounded-xl px-3 text-left', current ? 'bg-soft' : 'hover:bg-sf2']}
                    onclick={() => goTo(s.n)}
                >
                    <span
                        class={[
                            'flex size-7 flex-none items-center justify-center rounded-full font-mono text-xs font-semibold',
                            current ? 'bg-pri text-on-pri' : done ? 'bg-mid text-white' : 'bg-sf2 text-mut',
                        ]}
                    >
                        {#if done && !current}<Check class="size-4" aria-label="complete" />{:else}{s.n}{/if}
                    </span>
                    <span class={['flex-1 text-[15px]', current ? 'font-bold text-brand' : done ? 'font-medium text-ink' : 'font-medium text-mut']}>{s.label}</span>
                    {#if flagged}<ErrorIcon class="size-[18px] text-bad" aria-label="needs attention" />{/if}
                </button>
            {/each}
            {#if fee}
                <div class="mt-auto flex items-center justify-between rounded-xl bg-sf2 px-3 py-3.5">
                    <span class="text-sm text-mut">Then</span>
                    <b class="flex items-center gap-1.5 text-sm"><Payments class="size-[18px]" />Pay {fee.amount}</b>
                </div>
            {/if}
        </nav>

        <main class="flex flex-1 flex-col lg:min-h-0">
            <div class="flex flex-1 flex-col gap-[18px] px-5 py-5 lg:overflow-y-auto lg:px-10 lg:py-8">
                <div class="hidden flex-col gap-1 lg:flex">
                    {#if !isReview}<span class="mono-caps text-[13px] text-brand">{meta.eyebrow}</span>{/if}
                    <h1 class="text-[28px] font-extrabold tracking-[-0.01em]">{meta.title}</h1>
                    {#if descriptions[step]}<span class="text-[15px] text-mut">{descriptions[step]}</span>{/if}
                </div>
                <div class="flex w-full max-w-[720px] flex-col gap-[18px] {step === 5 || isReview ? 'lg:max-w-none' : ''}">
                    {@render stepBody()}
                </div>
            </div>

            <!-- Desktop footer -->
            <div class="hidden h-20 flex-none items-center gap-3 border-t border-line bg-sf px-10 lg:flex">
                {#if step > 1}
                    <Button variant="outline" size="md" class="h-12 border-[1.5px] text-[15px]" onclick={() => goTo(step - 1)}>
                        <ArrowBack />{backLabel(step)}
                    </Button>
                {/if}
                {#if !isReview}
                    <Button size="md" class="ml-auto h-12 px-[22px] text-[15px]" onclick={next}>
                        {nextLabel(step)}<ArrowForward />
                    </Button>
                {/if}
            </div>
        </main>
    </div>

    <!-- Phone thumb-zone footer -->
    <div class="pb-safe sticky bottom-0 z-20 flex gap-2.5 border-t border-line bg-sf px-4 pt-3 lg:hidden">
        <Button variant="outline" class="px-4 text-base" onclick={saveNow}><Save />{isReview ? 'Save' : 'Save draft'}</Button>
        {#if isReview}
            <Button block class="flex-1 text-base" disabled={payDisabled} onclick={() => pay(false)}>
                <Lock />{payLabel ?? `Pay ${fee?.amount.replace(/\.00$/, '') ?? ''}`}
            </Button>
        {:else}
            <Button block class="flex-1" onclick={next}>
                {nextLabel(step, true)}<ArrowForward />
            </Button>
        {/if}
    </div>
</div>

<Toaster />
