<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enums\ReviewStatus;
use App\Models\Inspection;
use Illuminate\Http\Request;
use Illuminate\Http\Response as HttpResponse;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

/**
 * Public, rate-limited check behind the certificate QR code. Shows only the
 * ticket, its status and dates, the service area, the contractor and a masked
 * owner name. No other personal data.
 */
class VerifyController extends Controller
{
    public function __invoke(Request $request, string $ticket): Response
    {
        $ticket = strtoupper(trim($ticket));

        $inspection = preg_match('/^KE-NSD-\d{4}-\d{6}$/', $ticket)
            ? Inspection::query()->submitted()->where('ticket_no', $ticket)->with('serviceArea:id,name')->first()
            : null;

        $page = Inertia::render('Verify', [
            'ticket' => $ticket,
            'result' => $inspection ? [
                'status' => $inspection->review_status->value ?? ReviewStatus::Pending->value,
                'submittedAt' => $inspection->submitted_at?->format('d M Y'),
                'approvedAt' => $inspection->approved_at?->format('d M Y'),
                'area' => $inspection->serviceArea?->name,
                'contractor' => $inspection->inspector_name,
                'owner' => self::maskName($inspection->owner_name),
            ] : null,
        ])->toResponse($request);

        return $inspection ? $page : $page->setStatusCode(HttpResponse::HTTP_NOT_FOUND);
    }

    /** "Alhaji Musa Ibrahim" → "Alhaji M. I."; "Musa" → "M." */
    public static function maskName(?string $name): ?string
    {
        $words = preg_split('/\s+/u', trim((string) $name), -1, PREG_SPLIT_NO_EMPTY) ?: [];

        if ($words === []) {
            return null;
        }

        $initial = fn (string $word): string => mb_strtoupper(mb_substr($word, 0, 1)).'.';

        if (count($words) === 1) {
            return $initial($words[0]);
        }

        return $words[0].' '.implode(' ', array_map($initial, array_slice($words, 1)));
    }
}
