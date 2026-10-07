<?php

declare(strict_types=1);

namespace App\Support;

use chillerlan\QRCode\Common\EccLevel;
use chillerlan\QRCode\Output\QRMarkupSVG;
use chillerlan\QRCode\QRCode;
use chillerlan\QRCode\QROptions;

/**
 * QR code pointing at the public verify page for a ticket (CT-01, print header).
 */
final class TicketQr
{
    public static function verifyUrl(string $ticketNo): string
    {
        return rtrim((string) config('kens.verify_base_url'), '/').'/verify/'.rawurlencode($ticketNo);
    }

    /** Inline SVG markup (dark modules only; place it on a white tile). */
    public static function svg(string $ticketNo): string
    {
        $options = new QROptions([
            'outputInterface' => QRMarkupSVG::class,
            'outputBase64' => false,
            'eccLevel' => EccLevel::M,
            'addQuietzone' => true,
            'quietzoneSize' => 2,
            'drawLightModules' => false,
            'connectPaths' => true,
            'svgAddXmlHeader' => false,
            'cssClass' => 'kens-qr',
        ]);

        return (new QRCode($options))->render(self::verifyUrl($ticketNo));
    }

    /** For an <img src>: no raw markup injected into pages. */
    public static function dataUri(string $ticketNo): string
    {
        return 'data:image/svg+xml;base64,'.base64_encode(self::svg($ticketNo));
    }
}
