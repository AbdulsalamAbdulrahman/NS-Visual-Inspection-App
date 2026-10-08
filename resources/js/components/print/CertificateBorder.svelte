<script lang="ts">
    /**
     * Engraved-style certificate border and a faint guilloche rosette behind
     * the text (our own artwork, drawn for a 794 × 1123 A4 sheet).
     */
    const uid = $props.id();

    const GREEN = '#0B5A33';
    const GOLD = '#C9A13B';

    // Rosette: 36 thin ellipses turned 5° apart.
    const petals = Array.from({ length: 36 }, (_, i) => i * 5);
    // Corner ornaments sit where the bands meet.
    const corners = [
        [33, 33],
        [761, 33],
        [33, 1090],
        [761, 1090],
    ];
</script>

<svg class="pointer-events-none absolute inset-0 size-full" viewBox="0 0 794 1123" aria-hidden="true">
    <defs>
        <!-- Two interlaced waves: a chain running along the band. -->
        <pattern id="{uid}-h" width="20" height="22" patternUnits="userSpaceOnUse" x="0" y="22">
            <path d="M0 11 Q5 3 10 11 T20 11" fill="none" stroke={GOLD} stroke-width="1" />
            <path d="M0 11 Q5 19 10 11 T20 11" fill="none" stroke={GREEN} stroke-width="0.8" />
            <path d="M0 6 Q5 1 10 6 T20 6 M0 16 Q5 21 10 16 T20 16" fill="none" stroke={GOLD} stroke-width="0.5" opacity="0.7" />
        </pattern>
        <pattern id="{uid}-v" width="22" height="20" patternUnits="userSpaceOnUse" x="22" y="0">
            <path d="M11 0 Q3 5 11 10 T11 20" fill="none" stroke={GOLD} stroke-width="1" />
            <path d="M11 0 Q19 5 11 10 T11 20" fill="none" stroke={GREEN} stroke-width="0.8" />
            <path d="M6 0 Q1 5 6 10 T6 20 M16 0 Q21 5 16 10 T16 20" fill="none" stroke={GOLD} stroke-width="0.5" opacity="0.7" />
        </pattern>
    </defs>

    <!-- Outer rule, patterned band, inner rules -->
    <rect x="14" y="14" width="766" height="1095" fill="none" stroke={GREEN} stroke-width="2.5" />
    <rect x="22" y="22" width="750" height="22" fill="url(#{uid}-h)" />
    <rect x="22" y="1079" width="750" height="22" fill="url(#{uid}-h)" />
    <rect x="22" y="22" width="22" height="1079" fill="url(#{uid}-v)" />
    <rect x="750" y="22" width="22" height="1079" fill="url(#{uid}-v)" />
    <rect x="22" y="22" width="750" height="1079" fill="none" stroke={GREEN} stroke-width="0.8" />
    <rect x="44" y="44" width="706" height="1035" fill="none" stroke={GREEN} stroke-width="0.8" />
    <rect x="52" y="52" width="690" height="1019" fill="none" stroke={GOLD} stroke-width="0.8" />

    {#each corners as [cx, cy] (`${cx}-${cy}`)}
        <g transform="translate({cx} {cy})">
            <rect x="-13" y="-13" width="26" height="26" fill="#FFFDF6" stroke={GREEN} stroke-width="1" />
            <circle r="8" fill="none" stroke={GOLD} stroke-width="1.2" />
            <circle r="3.2" fill={GREEN} />
        </g>
    {/each}

    <!-- Rosette watermark behind the statement -->
    <g transform="translate(397 560)" opacity="0.09">
        {#each petals as angle (angle)}
            <ellipse rx="190" ry="62" fill="none" stroke={GOLD} stroke-width="0.9" transform="rotate({angle})" />
        {/each}
        <circle r="64" fill="none" stroke={GREEN} stroke-width="1.2" />
        <circle r="58" fill="none" stroke={GOLD} stroke-width="0.8" />
    </g>
</svg>
