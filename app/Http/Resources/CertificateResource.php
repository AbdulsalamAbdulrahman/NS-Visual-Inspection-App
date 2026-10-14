<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\NigerianState;
use App\Models\Inspection;
use App\Support\DocumentUrls;
use App\Support\Form74Range;
use App\Support\TicketQr;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Everything printed on the one-page certificate. Only built for approved
 * inspections; the signatory comes from the snapshot taken at approval.
 *
 * @mixin Inspection
 */
class CertificateResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $ticket = (string) $this->ticket_no;
        $profile = $this->contractor->contractorProfile;
        $category = $this->inspector_nemsa_category ?? $profile?->nemsa_category;

        return [
            'uuid' => $this->uuid,
            'certificateNo' => $ticket,
            'form74No' => Form74Range::label($this->form74_no, $this->form74_last_no),
            'description' => collect([
                $this->building_type?->label() ?? $this->purpose?->label(),
                $this->structure_count > 1 ? "{$this->structure_count} structures" : null,
                $this->connection_type?->label(),
                $this->voltage_level?->label(),
                $this->sub_circuit_count ? "{$this->sub_circuit_count} sub-circuits" : null,
            ])->filter()->join(' · '),
            'ownerName' => $this->owner_name,
            'address' => $this->property_address ? (string) str($this->property_address)->squish() : null,
            'area' => $this->serviceArea?->name,
            'chapter' => ($this->serviceArea->state ?? NigerianState::Kaduna)->label(),
            'inspectionDate' => $this->inspection_date?->format('jS F, Y'),
            'submittedAt' => $this->submitted_at?->format('jS F, Y'),
            'approvedAt' => $this->approved_at?->format('jS F, Y'),
            'approvedAtShort' => $this->approved_at?->format('d M Y'),
            'contractor' => [
                'name' => $this->inspector_name ?? $this->contractor->name,
                'firm' => $this->inspector_firm_name ?? $profile?->firm_name,
                'category' => $category?->label(),
                'regNo' => $this->inspector_nemsa_reg_no ?? $profile?->nemsa_reg_no,
                'corenNo' => $this->inspector_coren_no ?? $profile?->coren_no,
                'signatureUrl' => $this->signature_path ? DocumentUrls::signature($this->resource) : null,
            ],
            // First-level approval by the LECAN state chapter (name only; no signature image yet).
            'lecan' => $this->lecan_approver_name ? [
                'name' => $this->lecan_approver_name,
                'approvedAt' => $this->lecan_approved_at?->format('d M Y'),
            ] : null,
            'signatory' => [
                'name' => $this->signatory_name,
                'title' => $this->signatory_title,
                'signatureUrl' => $this->signatory_signature_path ? DocumentUrls::signatory($this->resource) : null,
            ],
            'verifyUrl' => TicketQr::verifyUrl($ticket),
            'verifyLabel' => preg_replace('#^https?://#', '', TicketQr::verifyUrl($ticket)),
            'qr' => TicketQr::dataUri($ticket),
        ];
    }
}
