<script lang="ts">
    import { onDestroy } from 'svelte';
    import Button from '@/components/Button.svelte';
    import { LIMITS } from '@/lib/inspection/checklist';
    import { clock, formatLat, formatLng } from '@/lib/format';
    import CheckCircle from '~icons/ms/check-circle';
    import ErrorIcon from '~icons/ms/error';
    import LocationDisabled from '~icons/ms/location-disabled';
    import MyLocation from '~icons/ms/my-location';
    import Refresh from '~icons/ms/refresh';
    import WrongLocation from '~icons/ms/wrong-location';

    type Props = {
        lat: number | null;
        lng: number | null;
        accuracy: number | null;
        capturedAt: string | null;
        /** Error shown on Next / Review (CF-03). */
        error?: string | null;
    };

    let {
        lat = $bindable(),
        lng = $bindable(),
        accuracy = $bindable(),
        capturedAt = $bindable(),
        error = null,
    }: Props = $props();

    /** How long to keep improving the fix before settling. */
    const MAX_WAIT_MS = 20_000;
    /** Stop early once the fix is this good. */
    const EXCELLENT_M = 8;

    type Phase = 'idle' | 'capturing' | 'denied' | 'unavailable' | 'low' | 'captured';

    let phase = $state<Phase>(lat !== null && lng !== null ? 'captured' : 'idle');
    let live = $state<GeolocationPosition | null>(null);
    let best = $state<GeolocationPosition | null>(null);
    let showHelp = $state(false);

    let watchId: number | null = null;
    let timer: ReturnType<typeof setTimeout> | undefined;

    function stop(): void {
        if (watchId !== null) {
            navigator.geolocation.clearWatch(watchId);
            watchId = null;
        }

        clearTimeout(timer);
    }

    function accept(position: GeolocationPosition): void {
        lat = Number(position.coords.latitude.toFixed(7));
        lng = Number(position.coords.longitude.toFixed(7));
        accuracy = Math.round(position.coords.accuracy * 10) / 10;
        capturedAt = new Date(position.timestamp).toISOString();
        phase = 'captured';
    }

    function settle(): void {
        stop();

        if (!best) {
            phase = 'unavailable';
        } else if (best.coords.accuracy <= LIMITS.goodGpsAccuracyM) {
            accept(best);
        } else {
            phase = 'low';
        }
    }

    function capture(): void {
        if (!('geolocation' in navigator)) {
            phase = 'unavailable';

            return;
        }

        stop();
        live = null;
        best = null;
        showHelp = false;
        phase = 'capturing';

        watchId = navigator.geolocation.watchPosition(
            (position) => {
                live = position;

                if (!best || position.coords.accuracy < best.coords.accuracy) {
                    best = position;
                }

                if (position.coords.accuracy <= EXCELLENT_M) {
                    settle();
                }
            },
            (err) => {
                stop();
                phase = err.code === err.PERMISSION_DENIED ? 'denied' : best ? 'low' : 'unavailable';
            },
            { enableHighAccuracy: true, maximumAge: 0, timeout: MAX_WAIT_MS },
        );

        timer = setTimeout(settle, MAX_WAIT_MS);
    }

    function cancel(): void {
        stop();
        phase = lat !== null ? 'captured' : 'idle';
    }

    onDestroy(stop);

    const accuracyGood = $derived(accuracy !== null && accuracy <= LIMITS.goodGpsAccuracyM);
</script>

<div class="flex flex-col gap-2.5">
    {#if phase === 'idle'}
        <Button
            variant="outline"
            block
            class={['text-base text-brand', error && 'border-bad']}
            onclick={capture}
            aria-describedby={error ? 'gps-error' : undefined}
        >
            <MyLocation />Capture location
        </Button>
    {:else if phase === 'capturing'}
        <!-- CF-04 · 1 capturing -->
        <div class="flex items-center gap-3.5 rounded-2xl border-[1.5px] border-line bg-sf p-4" role="status" aria-live="polite">
            <span class="size-9 flex-none animate-spin rounded-full border-4 border-sf2 border-t-mid motion-reduce:animate-none" aria-hidden="true"></span>
            <div class="flex flex-1 flex-col gap-1">
                <b class="text-base">Finding your location…</b>
                <span class="text-sm leading-[1.4] text-mut">
                    Stand outside, near the meter point.
                    {#if live}Current fix <span class="font-mono">±{Math.round(live.coords.accuracy)} m</span>{/if}
                </span>
            </div>
            <button type="button" class="min-h-11 px-1 text-[15px] font-bold text-brand" onclick={cancel}>Cancel</button>
        </div>
    {:else if phase === 'denied'}
        <!-- CF-04 · 3 permission denied -->
        <div class="flex flex-col gap-2.5 rounded-2xl bg-bad-bg p-4" role="alert">
            <div class="flex items-start gap-2.5">
                <LocationDisabled class="size-6 flex-none text-bad" />
                <div class="flex flex-col gap-1">
                    <b class="text-base text-bad">Location access is blocked</b>
                    <span class="text-sm leading-[1.45]">
                        Allow location for this app in your phone's settings. A GPS fix is required to submit.
                    </span>
                </div>
            </div>
            {#if showHelp}
                <ul class="list-disc space-y-1 rounded-xl bg-sf p-3 pl-7 text-sm leading-[1.45]">
                    <li><b>Android (Chrome):</b> tap the lock or ⓘ by the address → Permissions → Location → Allow.</li>
                    <li><b>iPhone (Safari):</b> Settings → Privacy &amp; Security → Location Services → Safari Websites → While Using.</li>
                    <li>Make sure the phone's Location / GPS is switched on, then try again.</li>
                </ul>
            {/if}
            <div class="flex gap-2">
                <Button variant="ghost" size="md" class="h-12 flex-1 bg-sf text-[15px]" onclick={() => (showHelp = !showHelp)}>
                    How to allow
                </Button>
                <Button size="md" class="h-12 flex-1 text-[15px]" onclick={capture}>Try again</Button>
            </div>
        </div>
    {:else if phase === 'unavailable'}
        <div class="flex flex-col gap-2.5 rounded-2xl bg-bad-bg p-4" role="alert">
            <div class="flex items-start gap-2.5">
                <LocationDisabled class="size-6 flex-none text-bad" />
                <div class="flex flex-col gap-1">
                    <b class="text-base text-bad">Couldn't get a GPS fix</b>
                    <span class="text-sm leading-[1.45]">Check that Location is on, move outside, then try again.</span>
                </div>
            </div>
            <Button size="md" class="h-12 text-[15px]" onclick={capture}>Try again</Button>
        </div>
    {:else if phase === 'low' && best}
        <!-- CF-04 · 4 low accuracy -->
        <div class="flex flex-col gap-2.5 rounded-2xl bg-imp-bg p-4" role="alert">
            <div class="flex items-start gap-2.5">
                <WrongLocation class="size-6 flex-none text-imp" />
                <div class="flex flex-col gap-1">
                    <b class="text-base text-imp">Low accuracy · <span class="font-mono">±{Math.round(best.coords.accuracy)} m</span></b>
                    <span class="font-mono text-sm">
                        {formatLat(best.coords.latitude, 4)}, {formatLng(best.coords.longitude, 4)}
                    </span>
                    <span class="text-sm leading-[1.45]">
                        Move away from roofs and walls, then re-capture. Aim for ±{LIMITS.goodGpsAccuracyM} m or better.
                    </span>
                </div>
            </div>
            <div class="flex gap-2">
                <Button variant="ghost" size="md" class="h-12 flex-1 bg-sf text-[15px]" onclick={() => best && accept(best)}>
                    Use anyway
                </Button>
                <Button size="md" class="h-12 flex-1 text-[15px]" onclick={capture}>Re-capture</Button>
            </div>
        </div>
    {:else if lat !== null && lng !== null}
        <!-- CF-02 captured -->
        <div class="overflow-hidden rounded-2xl border-[1.5px] border-line bg-sf">
            <div
                class="relative flex h-[110px] items-center justify-center bg-[repeating-linear-gradient(135deg,var(--sf2)_0_10px,var(--bg)_10px_20px)]"
                aria-hidden="true"
            >
                <span class="absolute top-1/2 left-1/2 -mt-[9px] -ml-[9px] size-[18px] rounded-full bg-mid shadow-[0_0_0_4px_var(--sf),0_0_0_22px_rgba(24,136,21,.18)]"></span>
            </div>
            <div class="flex flex-col gap-2.5 p-3.5">
                <div class={['flex items-center gap-2 text-[15px] font-bold', accuracyGood ? 'text-ok' : 'text-imp']}>
                    <CheckCircle class="size-5" />
                    Location captured{capturedAt ? ` · ${clock(capturedAt)}` : ''}
                    {#if accuracy !== null}
                        <span class={['ml-auto rounded-md px-2 py-1 font-mono text-[13px] font-semibold', accuracyGood ? 'bg-ok-bg' : 'bg-imp-bg']}>
                            ±{Math.round(accuracy)} m
                        </span>
                    {/if}
                </div>
                <dl class="grid grid-cols-2 gap-2 font-mono">
                    <div class="flex flex-col gap-0.5">
                        <dt class="text-xs text-mut">LAT</dt>
                        <dd class="text-[17px] font-medium">{formatLat(lat)}</dd>
                    </div>
                    <div class="flex flex-col gap-0.5">
                        <dt class="text-xs text-mut">LONG</dt>
                        <dd class="text-[17px] font-medium">{formatLng(lng)}</dd>
                    </div>
                </dl>
                <Button variant="soft" size="md" class="h-12 text-[15px]" onclick={capture}>
                    <Refresh />Re-capture
                </Button>
            </div>
        </div>
    {/if}

    {#if error && phase === 'idle'}
        <span id="gps-error" class="flex items-center gap-1 text-sm font-semibold text-bad">
            <ErrorIcon class="size-[18px]" />{error}
        </span>
    {/if}
</div>
