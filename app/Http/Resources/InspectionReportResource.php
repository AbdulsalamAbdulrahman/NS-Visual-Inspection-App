<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Enums\CircuitCondition;
use App\Enums\WiringMethod;
use App\Models\Inspection;
use App\Models\InspectionCircuit;
use App\Models\InspectionReview;
use App\Support\InspectionChecklist;
use App\Support\Money;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * The full read-only report (CD-01, AD-03, AM-04, SR-M3, print). Values are
 * display-ready; payment details are included for admins only.
 *
 * @mixin Inspection
 */
class InspectionReportResource extends JsonResource
{
    /** Printed reports leave payment details off for every role (spec). */
    private bool $forPrint = false;

    public function forPrint(): static
    {
        $this->forPrint = true;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $warnings = [
            'electrode' => $this->earth_electrode_ft !== null && $this->earth_electrode_ft < InspectionChecklist::MIN_ELECTRODE_FT,
            'conductor' => $this->earth_conductor_mm2 !== null && $this->earth_conductor_mm2 < InspectionChecklist::MIN_EARTH_CONDUCTOR_MM2,
            'resistance' => $this->earth_resistance_ohm !== null && $this->earth_resistance_ohm > InspectionChecklist::MAX_EARTH_RESISTANCE_OHM,
        ];

        $mains = $this->mainsRows();
        $circuits = $this->circuits->map(fn (InspectionCircuit $c): array => [
            'n' => $c->label(),
            'description' => $c->descriptionLabel(),
            'rating' => $c->rating_a,
            'conductor' => $c->conductor_mm2,
            'condition' => $c->condition?->value,
            'observation' => $c->observation,
        ])->values();

        $notStandard = collect([$this->cb_standard, $this->fuse_standard])->filter(fn ($v) => $v === false)->count()
            + collect($mains)->where('tone', 'bad')->count();
        $badCircuits = $this->circuits->filter(fn (InspectionCircuit $c) => $c->condition !== null && $c->condition !== CircuitCondition::Satisfactory)->count();

        return [
            'uuid' => $this->uuid,
            'ticketNo' => $this->ticket_no,
            'status' => $this->status->value,
            'submittedAt' => $this->submitted_at?->format('d M Y'),
            'submittedAtLong' => $this->submitted_at?->format('d M Y, H:i'),

            'a' => [
                'form74No' => $this->form74_no,
                'ownerName' => $this->owner_name,
                'address' => $this->property_address,
                'area' => $this->serviceArea?->name,
                'purpose' => $this->purpose?->label(),
                'connection' => $this->connection_type?->label(),
                'voltage' => $this->voltage_level?->label(),
                'inspectionDate' => $this->inspection_date?->format('d M Y'),
                'contractor' => $this->inspector_name ?? $this->contractor->name,
                'gps' => $this->gps_lat !== null && $this->gps_lng !== null ? [
                    'lat' => $this->gps_lat,
                    'lng' => $this->gps_lng,
                    'accuracy' => $this->gps_accuracy_m,
                    'capturedAt' => $this->gps_captured_at?->format('H:i'),
                    'mapsUrl' => "https://www.google.com/maps?q={$this->gps_lat},{$this->gps_lng}",
                ] : null,
            ],

            'b1' => [
                'electrodeFt' => $this->earth_electrode_ft,
                'conductorMm2' => $this->earth_conductor_mm2,
                'resistanceOhm' => $this->earth_resistance_ohm,
                'pit' => $this->earth_pit,
                'warnings' => $warnings,
            ],

            'b2' => [
                'cbRatedA' => $this->cb_rated_a,
                'cbStandard' => $this->cb_standard,
                'fuseRatedA' => $this->fuse_rated_a,
                'fuseStandard' => $this->fuse_standard,
                'poles' => $this->poles,
            ],

            'b3' => $mains,

            'b4' => $circuits,

            'c' => [
                'earthingSystem' => $this->earthing_system_type?->label(),
                'dbCount' => $this->db_count,
                'subCircuitCount' => $this->sub_circuit_count,
                'mainCableMm2' => $this->main_cable_mm2,
                'conductorType' => $this->conductor_type?->label(),
                'wiringMethod' => $this->wiring_method === WiringMethod::Other
                    ? ($this->wiring_method_other ?: 'Other')
                    : $this->wiring_method?->label(),
                'cableInsulation' => $this->cable_insulation,
            ],

            'd' => [
                'name' => $this->inspector_name ?? $this->contractor->name,
                'category' => ($this->inspector_nemsa_category ?? $this->contractor->contractorProfile?->nemsa_category)?->label(),
                'regNo' => $this->inspector_nemsa_reg_no ?? $this->contractor->contractorProfile?->nemsa_reg_no,
                'corenNo' => $this->inspector_coren_no ?? $this->contractor->contractorProfile?->coren_no,
                'firmName' => $this->inspector_firm_name ?? $this->contractor->contractorProfile?->firm_name,
                'declaredAt' => $this->declaration_accepted_at?->format('d M Y'),
                'signatureUrl' => $this->signature_path ? route('inspections.signature', $this->resource) : null,
            ],

            'attachments' => AttachmentResource::collection($this->attachments),

            'summary' => [
                'warnings' => count(array_filter($warnings)),
                'issues' => $notStandard + $badCircuits,
            ],

            'review' => [
                'status' => $this->review_status?->value,
                'label' => $this->review_status?->label(),
                'note' => $this->review_note,
                'reviewedAt' => $this->reviewed_at?->format('d M Y, H:i'),
                'approvedAt' => $this->approved_at?->format('d M Y'),
                'history' => $this->when(
                    (bool) $request->user()?->isAdmin() && $this->relationLoaded('reviews'),
                    fn () => $this->reviews->map(fn (InspectionReview $r): array => [
                        'id' => $r->id,
                        'action' => $r->action->value,
                        'label' => $r->action->label(),
                        'note' => $r->note,
                        'by' => $r->user?->name,
                        'at' => $r->created_at->format('d M Y, H:i'),
                    ])->values(),
                ),
            ],

            'payment' => $this->when(! $this->forPrint && (bool) $request->user()?->isAdmin(), function (): ?array {
                $payment = $this->paidPayment;

                return $payment ? [
                    'reference' => $payment->transaction_reference ?? $payment->payment_reference,
                    'ourReference' => $payment->payment_reference,
                    'amount' => Money::format($payment->amount_paid_kobo ?? $payment->amount_kobo),
                    'channel' => $payment->channelLabel(),
                    'paidAt' => $payment->paid_at?->format('d M Y H:i:s'),
                ] : null;
            }),
        ];
    }

    /**
     * B3 rows as shown in AD-03: label, standard reference, value, tone.
     *
     * @return list<array{label: string, ref: string|null, value: string, tone: string}>
     */
    private function mainsRows(): array
    {
        $std = fn (?bool $v): array => match ($v) {
            true => ['Standard', 'ok'],
            false => ['Not standard', 'bad'],
            null => ['—', 'muted'],
        };

        $seen = function (?bool $seen, ?bool $standard): array {
            if ($seen === null) {
                return ['—', 'muted'];
            }

            if ($seen === false) {
                return ['Not seen', 'muted'];
            }

            return match ($standard) {
                true => ['Seen · Standard', 'ok'],
                false => ['Seen · Not standard', 'bad'],
                null => ['Seen', 'muted'],
            };
        };

        $secondary = collect([
            $this->secondary_rated_a !== null ? rtrim(rtrim(number_format($this->secondary_rated_a, 1), '0'), '.').' A' : null,
            $this->secondary_type?->label(),
            match ($this->secondary_standard) {
                true => 'Std',
                false => 'Not std',
                null => null,
            },
        ])->filter()->join(' · ');

        $rows = [
            ['Distribution board', null, ...$seen($this->db_seen, $this->db_standard)],
            ['Change-over switch', null, ...$seen($this->changeover_seen, $this->changeover_standard)],
            ['Main switch', 'BS', ...$std($this->main_switch_standard)],
            ['Circuit breaker type', 'BS EN 60898-1 · Type B', ...$std($this->cb_type_standard)],
            ['Socket outlets', 'BS 1363-2', ...$std($this->socket_outlets_standard)],
            ['Secondary protection', null, $secondary !== '' ? $secondary : '—', $this->secondary_standard === false ? 'bad' : 'muted'],
        ];

        return array_map(fn (array $r): array => ['label' => $r[0], 'ref' => $r[1], 'value' => $r[2], 'tone' => $r[3]], $rows);
    }
}
