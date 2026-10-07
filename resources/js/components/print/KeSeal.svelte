<script lang="ts">
    /** Round NSD seal for the certificate (our own artwork; no third-party marks). */
    type Props = { size?: number };

    let { size = 110 }: Props = $props();

    const uid = $props.id();

    // Scalloped edge: a 48-point star between radii 49 and 46.
    const scallop = Array.from({ length: 96 }, (_, i) => {
        const r = i % 2 === 0 ? 49 : 46;
        const a = (i / 96) * Math.PI * 2;
        return `${(50 + r * Math.cos(a)).toFixed(2)},${(50 + r * Math.sin(a)).toFixed(2)}`;
    }).join(' ');
</script>

<svg viewBox="0 0 100 100" width={size} height={size} role="img" aria-label="Kaduna Electric New Service Department seal">
    <defs>
        <path id="{uid}-ring" d="M50,50 m-33,0 a33,33 0 1,1 66,0 a33,33 0 1,1 -66,0" />
    </defs>
    <polygon points={scallop} fill="#7BB43B" />
    <circle cx="50" cy="50" r="44.5" fill="#09502E" />
    <circle cx="50" cy="50" r="41" fill="none" stroke="#E7F2DC" stroke-width="0.8" />
    <circle cx="50" cy="50" r="25" fill="#FBFCF8" />
    <circle cx="50" cy="50" r="25" fill="none" stroke="#7BB43B" stroke-width="1.2" />
    <text font-family="Public Sans, sans-serif" font-size="6.6" font-weight="800" letter-spacing="1.05" fill="#FFFFFF">
        <textPath href="#{uid}-ring" startOffset="0">KADUNA ELECTRIC • NEW SERVICE DEPARTMENT •</textPath>
    </text>
    <image href="/images/ke-logo.png" x="34" y="30" width="32" height="26" preserveAspectRatio="xMidYMid meet" />
    <text x="50" y="65" text-anchor="middle" font-family="IBM Plex Mono, monospace" font-size="6.5" font-weight="600" letter-spacing="0.6" fill="#09502E">NSD</text>
    <text x="50" y="71.5" text-anchor="middle" font-size="5" fill="#7BB43B">★ ★ ★</text>
</svg>
