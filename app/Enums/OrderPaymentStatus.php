<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum OrderPaymentStatus: string implements HasLabel, HasColor, HasIcon
{
    case PENDING = 'pending';
    case PAID = 'paid';
    case FAILED = 'failed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => __('Tertunda'),
            self::PAID => __('Lunas'),
            self::FAILED => __('Gagal'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::PAID => 'success',
            self::FAILED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-m-clock',
            self::PAID => 'heroicon-m-check-circle',
            self::FAILED => 'heroicon-m-x-circle',
        };
    }
}
