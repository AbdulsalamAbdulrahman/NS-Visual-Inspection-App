import imageCompression from 'browser-image-compression';
import exifr from 'exifr';
// The compression worker loads this script; bundle it instead of the default CDN copy so it works offline.
import compressionWorkerUrl from 'browser-image-compression/dist/browser-image-compression.js?url';

export type PhotoMeta = {
    exif_lat: number | null;
    exif_lng: number | null;
    taken_at: string | null;
};

/**
 * Read GPS and capture time from the original photo. This must happen before
 * compression, which strips EXIF.
 */
export async function readPhotoMeta(file: File): Promise<PhotoMeta> {
    try {
        const data = await exifr.parse(file, {
            gps: true,
            pick: ['DateTimeOriginal', 'CreateDate', 'latitude', 'longitude'],
        });
        const taken: Date | undefined =
            data?.DateTimeOriginal ?? data?.CreateDate;

        return {
            exif_lat:
                typeof data?.latitude === 'number'
                    ? Number(data.latitude.toFixed(7))
                    : null,
            exif_lng:
                typeof data?.longitude === 'number'
                    ? Number(data.longitude.toFixed(7))
                    : null,
            taken_at:
                taken instanceof Date && !Number.isNaN(taken.getTime())
                    ? taken.toISOString()
                    : null,
        };
    } catch {
        return { exif_lat: null, exif_lng: null, taken_at: null };
    }
}

/** ~1600 px on the long edge, ~300–500 KB JPEG. PDFs and small files pass through. */
export async function compressImage(file: File): Promise<File> {
    if (!file.type.startsWith('image/') || file.size < 350 * 1024) {
        return file;
    }

    const compressed = await imageCompression(file, {
        maxWidthOrHeight: 1600,
        maxSizeMB: 0.5,
        initialQuality: 0.82,
        fileType: 'image/jpeg',
        useWebWorker: true,
        libURL: new URL(compressionWorkerUrl, window.location.origin).href,
    });

    const name =
        file.name.replace(/\.(heic|heif|png|webp|jpe?g)$/i, '') + '.jpg';

    return new File([compressed], name, {
        type: 'image/jpeg',
        lastModified: file.lastModified,
    });
}
