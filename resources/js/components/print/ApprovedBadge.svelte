<script lang="ts">
    /** Gold shield with the four review steps (our own artwork). */
    type Props = { size?: number };

    let { size = 96 }: Props = $props();

    const uid = $props.id();
    const steps = ['INSPECTED', 'REPORTED', 'REVIEWED', 'APPROVED'];
</script>

<svg viewBox="0 0 100 112" width={size} height={size * 1.12} role="img" aria-label="Inspected, reported, reviewed, approved">
    <defs>
        <linearGradient id="{uid}-gold" x1="0" y1="0" x2="1" y2="1">
            <stop offset="0" stop-color="#F3D774" />
            <stop offset="0.55" stop-color="#D4A73C" />
            <stop offset="1" stop-color="#A9761E" />
        </linearGradient>
    </defs>
    <!-- Laurel -->
    {#each [-1, 1] as side (side)}
        {#each [0, 1, 2, 3, 4] as i (i)}
            <ellipse
                cx={50 + side * (38 - i * 1.5)}
                cy={96 - i * 13}
                rx="3.2"
                ry="7"
                fill="url(#{uid}-gold)"
                transform="rotate({side * (35 - i * 9)} {50 + side * (38 - i * 1.5)} {96 - i * 13})"
            />
        {/each}
    {/each}
    <path d="M50 4 L84 14 V52 C84 76 68 92 50 100 C32 92 16 76 16 52 V14 Z" fill="url(#{uid}-gold)" />
    <path d="M50 10 L78 18.5 V52 C78 72 65 86 50 93 C35 86 22 72 22 52 V18.5 Z" fill="#FFF8E1" stroke="#C99A33" stroke-width="1" />
    {#each steps as step, i (step)}
        <g transform="translate(30 {30 + i * 13})">
            <rect width="8" height="8" rx="1.5" fill="#09502E" />
            <path d="M1.8 4.2 L3.4 5.8 L6.4 2.4" fill="none" stroke="#FFFFFF" stroke-width="1.3" stroke-linecap="round" stroke-linejoin="round" />
            <text x="11" y="6.7" font-family="Public Sans, sans-serif" font-size="6.3" font-weight="800" fill="#4A3606">{step}</text>
        </g>
    {/each}
</svg>
