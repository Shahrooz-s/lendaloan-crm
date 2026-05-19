<?php

namespace Modules\Invoice\Enums;

use Modules\Core\Support\InteractsWithEnums;

enum InvoiceStatusEnum: string {
    use InteractsWithEnums;
    case UNPAID = 'unpaid';
    case PARTIALLY_PAID = 'partially-paid';
    case PAID = 'paid';

    public function label(): string
    {
        return __('invoice::fields.statuses.'.$this->value);
    }

    public function badgeVariant(): string
    {
        return self::badgeVariants()[$this->name];
    }

    /**
     * Get the available badge variants.
     */
    public static function badgeVariants(): array
    {
        return [
            InvoiceStatusEnum::UNPAID->name => 'danger',
            InvoiceStatusEnum::PARTIALLY_PAID->name => 'warning',
            InvoiceStatusEnum::PAID->name => 'success',
        ];
    }

    public static function toArray(): array
    {
        return array_map(fn($e) => [ 'label' => $e->value, 'value' => $e->value], self::cases());
    }
}
