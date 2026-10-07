<script lang="ts">
    import SignaturePad from 'signature_pad';
    import type { Attachment } from 'svelte/attachments';
    import ErrorIcon from '~icons/ms/error';
    import InkEraser from '~icons/ms/ink-eraser';

    type Props = {
        /** URL of the saved signature (null when none). */
        savedUrl: string | null;
        /** New PNG data URL, or null to clear. */
        onchange: (dataUrl: string | null) => void;
        error?: string | null;
    };

    let { savedUrl, onchange, error = null }: Props = $props();

    let pad: SignaturePad | null = null;
    let drawing = $state(false);
    let hasInk = $state(false);

    // Show the saved image until the user clears it to sign again.
    const showSaved = $derived(savedUrl !== null && !drawing);

    // Mount signature_pad on the canvas and keep it sized to the box (hi-DPI aware).
    const signaturePad: Attachment<HTMLCanvasElement> = (canvas) => {
        pad = new SignaturePad(canvas, {
            // Always dark ink on white "paper", so it prints and shows in dark mode.
            penColor: '#0D1B13',
            backgroundColor: 'rgba(0,0,0,0)',
            minWidth: 1,
            maxWidth: 2.6,
        });

        const resize = (): void => {
            const ratio = Math.max(window.devicePixelRatio || 1, 1);
            const data = pad?.toData() ?? [];
            canvas.width = canvas.offsetWidth * ratio;
            canvas.height = canvas.offsetHeight * ratio;
            canvas.getContext('2d')?.scale(ratio, ratio);
            pad?.clear();
            pad?.fromData(data);
        };

        resize();
        const observer = new ResizeObserver(resize);
        observer.observe(canvas);

        pad.addEventListener('beginStroke', () => (drawing = true));
        pad.addEventListener('endStroke', () => {
            hasInk = !(pad?.isEmpty() ?? true);
            onchange(hasInk ? (pad?.toDataURL('image/png') ?? null) : null);
        });

        return () => {
            observer.disconnect();
            pad?.off();
            pad = null;
        };
    };

    function clear(): void {
        pad?.clear();
        hasInk = false;
        drawing = true;
        onchange(null);
    }
</script>

<div class="flex flex-col gap-1.5">
    <div class="flex items-center justify-between">
        <span id="signature-label" class="text-[15px] font-semibold">Signature</span>
        <button
            type="button"
            class="flex min-h-11 items-center gap-1 px-1 text-sm font-bold text-brand disabled:opacity-40"
            disabled={!showSaved && !hasInk}
            onclick={clear}
        >
            <InkEraser class="size-[18px]" />Clear
        </button>
    </div>

    <div
        class={[
            'relative h-[140px] overflow-hidden rounded-[14px] border-[1.5px] border-dashed bg-white',
            error ? 'border-2 border-solid border-bad' : 'border-line',
        ]}
    >
        {#if showSaved}
            <img src={savedUrl} alt="Your saved signature" class="absolute inset-0 size-full object-contain p-2" />
        {:else}
            <canvas
                {@attach signaturePad}
                class="absolute inset-0 size-full touch-none"
                aria-labelledby="signature-label"
                aria-describedby="signature-hint"
            ></canvas>
        {/if}
        <span class="pointer-events-none absolute inset-x-5 bottom-[26px] h-px bg-[#D3DBD1]" aria-hidden="true"></span>
        <span id="signature-hint" class="pointer-events-none absolute bottom-2 left-5 text-xs text-[#4A5A50]">
            {showSaved ? 'Signed · tap Clear to sign again' : 'Sign with your finger'}
        </span>
    </div>

    {#if error}
        <span class="flex items-center gap-1 text-sm font-semibold text-bad"><ErrorIcon class="size-[18px]" />{error}</span>
    {/if}
</div>
