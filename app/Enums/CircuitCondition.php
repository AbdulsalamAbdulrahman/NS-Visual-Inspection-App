<?php

declare(strict_types=1);

namespace App\Enums;

enum CircuitCondition: string
{
    case Satisfactory = 'satisfactory';
    case ImprovementRequired = 'improvement_required';
    case UrgentAttentionRequired = 'urgent_attention_required';
    case NonCompliant = 'non_compliant';

    public function label(): string
    {
        return match ($this) {
            self::Satisfactory => 'Satisfactory',
            self::ImprovementRequired => 'Improvement required',
            self::UrgentAttentionRequired => 'Urgent attention required',
            self::NonCompliant => 'Does not comply with standard',
        };
    }

    /** Compact label for tables (CK-02, print). */
    public function shortLabel(): string
    {
        return match ($this) {
            self::Satisfactory => 'Satisfactory',
            self::ImprovementRequired => 'Improvement required',
            self::UrgentAttentionRequired => 'Urgent attention',
            self::NonCompliant => 'Does not comply',
        };
    }

    /** Count label on review cards ("2 Improvement", "1 Non-compliant"). */
    public function countLabel(): string
    {
        return match ($this) {
            self::Satisfactory => 'Satisfactory',
            self::ImprovementRequired => 'Improvement',
            self::UrgentAttentionRequired => 'Urgent',
            self::NonCompliant => 'Non-compliant',
        };
    }

    /**
     * @return list<array{value: string, label: string}>
     */
    public static function options(): array
    {
        return array_map(fn (self $case): array => ['value' => $case->value, 'label' => $case->label()], self::cases());
    }
}
