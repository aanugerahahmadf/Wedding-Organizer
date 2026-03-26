<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasLabel, HasColor, HasIcon
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case CANCELLED = 'cancelled';
    case COMPLETED = 'completed';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING => __('Tertunda'),
            self::CONFIRMED => __('Dikonfirmasi'),
            self::CANCELLED => __('Dibatalkan'),
            self::COMPLETED => __('Selesai'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING => 'warning',
            self::CONFIRMED => 'info',
            self::CANCELLED => 'danger',
            self::COMPLETED => 'success',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING => 'heroicon-m-clock',
            self::CONFIRMED => 'heroicon-m-check-circle',
            self::CANCELLED => 'heroicon-m-x-circle',
            self::COMPLETED => 'heroicon-m-check-badge',
        };
    }
}
