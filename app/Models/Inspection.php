<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ConductorType;
use App\Enums\ConnectionType;
use App\Enums\EarthingSystemType;
use App\Enums\InspectionStatus;
use App\Enums\NemsaCategory;
use App\Enums\PaymentStatus;
use App\Enums\PropertyPurpose;
use App\Enums\ProtectionType;
use App\Enums\VoltageLevel;
use App\Enums\WiringMethod;
use Carbon\CarbonImmutable;
use Database\Factories\InspectionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * A Visual Site Inspection Report. Editable by its contractor while a draft;
 * locked once Monnify confirms payment and it becomes submitted.
 *
 * @property int $id
 * @property string $uuid
 * @property int $contractor_id
 * @property int|null $service_area_id
 * @property InspectionStatus $status
 * @property int $current_step
 * @property string|null $ticket_no
 * @property string|null $form74_no
 * @property string|null $owner_name
 * @property string|null $property_address
 * @property PropertyPurpose|null $purpose
 * @property ConnectionType|null $connection_type
 * @property VoltageLevel|null $voltage_level
 * @property float|null $gps_lat
 * @property float|null $gps_lng
 * @property float|null $gps_accuracy_m
 * @property CarbonImmutable|null $gps_captured_at
 * @property CarbonImmutable|null $inspection_date
 * @property float|null $earth_electrode_ft
 * @property float|null $earth_conductor_mm2
 * @property float|null $earth_resistance_ohm
 * @property bool|null $earth_pit
 * @property float|null $cb_rated_a
 * @property bool|null $cb_standard
 * @property float|null $fuse_rated_a
 * @property bool|null $fuse_standard
 * @property int|null $poles
 * @property bool|null $db_seen
 * @property bool|null $db_standard
 * @property bool|null $changeover_seen
 * @property bool|null $changeover_standard
 * @property bool|null $main_switch_standard
 * @property bool|null $cb_type_standard
 * @property float|null $secondary_rated_a
 * @property ProtectionType|null $secondary_type
 * @property bool|null $secondary_standard
 * @property bool|null $socket_outlets_standard
 * @property EarthingSystemType|null $earthing_system_type
 * @property int|null $db_count
 * @property int|null $sub_circuit_count
 * @property float|null $main_cable_mm2
 * @property ConductorType|null $conductor_type
 * @property WiringMethod|null $wiring_method
 * @property string|null $wiring_method_other
 * @property string|null $cable_insulation
 * @property CarbonImmutable|null $declaration_accepted_at
 * @property string|null $signature_path
 * @property string|null $inspector_name
 * @property NemsaCategory|null $inspector_nemsa_category
 * @property string|null $inspector_nemsa_reg_no
 * @property string|null $inspector_coren_no
 * @property string|null $inspector_firm_name
 * @property CarbonImmutable|null $submitted_at
 * @property CarbonImmutable|null $created_at
 * @property CarbonImmutable|null $updated_at
 * @property-read User $contractor
 * @property-read ServiceArea|null $serviceArea
 * @property-read Collection<int, InspectionCircuit> $circuits
 * @property-read Collection<int, InspectionAttachment> $attachments
 * @property-read Payment|null $paidPayment
 */
class Inspection extends Model
{
    /** @use HasFactory<InspectionFactory> */
    use HasFactory, HasUuids;

    /** Steps 1–8 are form sections; 9 is Review. */
    public const STEPS = 9;

    /** Fields a contractor may change on a draft (SaveDraft). */
    public const DRAFT_FIELDS = [
        'current_step',
        'form74_no', 'owner_name', 'property_address', 'service_area_id', 'purpose', 'connection_type',
        'voltage_level', 'gps_lat', 'gps_lng', 'gps_accuracy_m', 'gps_captured_at',
        'earth_electrode_ft', 'earth_conductor_mm2', 'earth_resistance_ohm', 'earth_pit',
        'cb_rated_a', 'cb_standard', 'fuse_rated_a', 'fuse_standard', 'poles',
        'db_seen', 'db_standard', 'changeover_seen', 'changeover_standard', 'main_switch_standard',
        'cb_type_standard', 'secondary_rated_a', 'secondary_type', 'secondary_standard', 'socket_outlets_standard',
        'earthing_system_type', 'db_count', 'sub_circuit_count', 'main_cable_mm2', 'conductor_type',
        'wiring_method', 'wiring_method_other', 'cable_insulation',
        'declaration_accepted_at',
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        ...self::DRAFT_FIELDS,
        'contractor_id',
        'status',
        'inspection_date',
        'signature_path',
    ];

    /**
     * @var array<string, string>
     */
    protected $casts = [
        'status' => InspectionStatus::class,
        'current_step' => 'integer',
        'purpose' => PropertyPurpose::class,
        'connection_type' => ConnectionType::class,
        'voltage_level' => VoltageLevel::class,
        'gps_lat' => 'float',
        'gps_lng' => 'float',
        'gps_accuracy_m' => 'float',
        'gps_captured_at' => 'datetime',
        'inspection_date' => 'date',
        'earth_electrode_ft' => 'float',
        'earth_conductor_mm2' => 'float',
        'earth_resistance_ohm' => 'float',
        'earth_pit' => 'boolean',
        'cb_rated_a' => 'float',
        'cb_standard' => 'boolean',
        'fuse_rated_a' => 'float',
        'fuse_standard' => 'boolean',
        'poles' => 'integer',
        'db_seen' => 'boolean',
        'db_standard' => 'boolean',
        'changeover_seen' => 'boolean',
        'changeover_standard' => 'boolean',
        'main_switch_standard' => 'boolean',
        'cb_type_standard' => 'boolean',
        'secondary_rated_a' => 'float',
        'secondary_type' => ProtectionType::class,
        'secondary_standard' => 'boolean',
        'socket_outlets_standard' => 'boolean',
        'earthing_system_type' => EarthingSystemType::class,
        'db_count' => 'integer',
        'sub_circuit_count' => 'integer',
        'main_cable_mm2' => 'float',
        'conductor_type' => ConductorType::class,
        'wiring_method' => WiringMethod::class,
        'declaration_accepted_at' => 'datetime',
        'inspector_nemsa_category' => NemsaCategory::class,
        'submitted_at' => 'datetime',
    ];

    /**
     * @return list<string>
     */
    public function uniqueIds(): array
    {
        return ['uuid'];
    }

    public function getRouteKeyName(): string
    {
        return 'uuid';
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function contractor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'contractor_id')->withTrashed();
    }

    /**
     * @return BelongsTo<ServiceArea, $this>
     */
    public function serviceArea(): BelongsTo
    {
        return $this->belongsTo(ServiceArea::class);
    }

    /**
     * @return HasMany<InspectionCircuit, $this>
     */
    public function circuits(): HasMany
    {
        return $this->hasMany(InspectionCircuit::class)->orderBy('position');
    }

    /**
     * @return HasMany<InspectionAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(InspectionAttachment::class)->orderBy('id');
    }

    /**
     * @return HasMany<Payment, $this>
     */
    public function payments(): HasMany
    {
        return $this->hasMany(Payment::class)->latest('id');
    }

    /**
     * The payment that submitted this inspection.
     *
     * @return HasOne<Payment, $this>
     */
    public function paidPayment(): HasOne
    {
        return $this->hasOne(Payment::class)->where('status', PaymentStatus::Paid)->oldest('paid_at');
    }

    public function isDraft(): bool
    {
        return $this->status === InspectionStatus::Draft;
    }

    public function isSubmitted(): bool
    {
        return $this->status === InspectionStatus::Submitted;
    }

    /** Folder on the private disk for this inspection's files. */
    public function storageDirectory(): string
    {
        return "inspections/{$this->uuid}";
    }

    /**
     * @param  Builder<Inspection>  $query
     */
    public function scopeDrafts(Builder $query): void
    {
        $query->where('status', InspectionStatus::Draft);
    }

    /**
     * @param  Builder<Inspection>  $query
     */
    public function scopeSubmitted(Builder $query): void
    {
        $query->where('status', InspectionStatus::Submitted);
    }

    /**
     * Inspections the user may see: admins all, contractors their own, reps
     * submitted ones in their assigned areas. Mirrors InspectionPolicy::view.
     *
     * @param  Builder<Inspection>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        match (true) {
            $user->isAdmin() => null,
            $user->isContractor() => $query->where('contractor_id', $user->id),
            $user->isRep() => $query
                ->where('status', InspectionStatus::Submitted)
                ->whereIn('service_area_id', $user->serviceAreas()->select('service_areas.id')),
            default => $query->whereRaw('1 = 0'),
        };
    }

    /**
     * @param  Builder<Inspection>  $query
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $term = trim($term);

        if ($term === '') {
            return;
        }

        $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $term).'%';

        $query->where(fn (Builder $q) => $q
            ->where('ticket_no', 'like', $like)
            ->orWhere('owner_name', 'like', $like)
            ->orWhere('form74_no', 'like', $like));
    }
}
