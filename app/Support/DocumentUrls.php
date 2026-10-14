<?php

declare(strict_types=1);

namespace App\Support;

use App\Models\Inspection;
use App\Models\InspectionAttachment;
use Illuminate\Support\Facades\URL;

/**
 * Links to an inspection's documents and the files on them. Signed-in users get
 * the authorised routes; the building owner (from an email, no account) gets
 * signed links. The owner's document pages mark the request with
 * CUSTOMER_VIEW so images on those pages are signed too.
 */
class DocumentUrls
{
    public const string CUSTOMER_VIEW = 'kens.customer_view';

    /** How long image links on an owner's page stay valid (the page itself doesn't expire). */
    private const int FILE_LINK_HOURS = 24;

    /**
     * Permanent signed link for the owner to open a document from an email.
     *
     * @param  'receipt'|'certificate'|'report'  $document
     */
    public static function forOwner(string $document, Inspection $inspection): string
    {
        return URL::signedRoute("customer.{$document}", $inspection);
    }

    public static function signature(Inspection $inspection): string
    {
        return self::customerView()
            ? self::signedFile('customer.files.signature', ['inspection' => $inspection])
            : route('inspections.signature', $inspection);
    }

    public static function signatory(Inspection $inspection): string
    {
        return self::customerView()
            ? self::signedFile('customer.files.signatory', ['inspection' => $inspection])
            : route('inspections.certificate.signature', $inspection);
    }

    public static function attachment(InspectionAttachment $attachment): string
    {
        return self::customerView()
            ? self::signedFile('customer.files.attachment', ['attachment' => $attachment])
            : route('attachments.show', $attachment);
    }

    private static function customerView(): bool
    {
        return (bool) request()->attributes->get(self::CUSTOMER_VIEW, false);
    }

    /**
     * @param  array<string, mixed>  $parameters
     */
    private static function signedFile(string $route, array $parameters): string
    {
        return URL::temporarySignedRoute($route, now()->addHours(self::FILE_LINK_HOURS), $parameters);
    }
}
