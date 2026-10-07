/** 10.522171 → "10.52217° N" */
export function formatLat(lat: number, digits = 5): string {
    return `${Math.abs(lat).toFixed(digits)}° ${lat >= 0 ? 'N' : 'S'}`;
}

/** 7.438312 → "7.43831° E" */
export function formatLng(lng: number, digits = 5): string {
    return `${Math.abs(lng).toFixed(digits)}° ${lng >= 0 ? 'E' : 'W'}`;
}

export function formatCoords(lat: number, lng: number, digits = 5): string {
    return `${formatLat(lat, digits)}, ${formatLng(lng, digits)}`;
}

export function googleMapsUrl(lat: number, lng: number): string {
    return `https://www.google.com/maps?q=${lat},${lng}`;
}

/** "10:42" in the device's local time. */
export function clock(date: Date | string): string {
    const d = typeof date === 'string' ? new Date(date) : date;

    return d.toLocaleTimeString('en-GB', {
        hour: '2-digit',
        minute: '2-digit',
    });
}

/** 1234567 → "1.2 MB" */
export function fileSize(bytes: number): string {
    if (bytes < 1024) {
        return `${bytes} B`;
    }

    if (bytes < 1024 * 1024) {
        return `${Math.round(bytes / 1024)} KB`;
    }

    return `${(bytes / 1024 / 1024).toFixed(1)} MB`;
}

/** 16 → "16", 2.5 → "2.5", null → "—" */
export function num(value: number | null | undefined): string {
    return value === null || value === undefined
        ? '—'
        : String(Number(value.toFixed(2)));
}
