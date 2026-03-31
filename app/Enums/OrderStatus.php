<?php

namespace App\Enums;

use Filament\Support\Contracts\HasColor;
use Filament\Support\Contracts\HasIcon;
use Filament\Support\Contracts\HasLabel;

enum OrderStatus: string implements HasLabel, HasColor, HasIcon
{
    case PENDING = 'pending';
    case CONFIRMED = 'confirmed';
    case PREPARING = 'preparing';
    case EVENT_DAY = 'event_day';
    case COMPLETED = 'completed';
    case CANCELLED = 'cancelled';

    public function getLabel(): ?string
    {
        return match ($this) {
            self::PENDING   => __('Menunggu'),
            self::CONFIRMED => __('Dikonfirmasi'),
            self::PREPARING => __('Dipersiapkan'),
            self::EVENT_DAY => __('Hari H / Acara'),
            self::COMPLETED => __('Selesai'),
            self::CANCELLED => __('Dibatalkan'),
        };
    }

    public function getColor(): string|array|null
    {
        return match ($this) {
            self::PENDING   => 'warning',
            self::CONFIRMED => 'primary',
            self::PREPARING => 'info',
            self::EVENT_DAY => 'success',
            self::COMPLETED => 'success',
            self::CANCELLED => 'danger',
        };
    }

    public function getIcon(): ?string
    {
        return match ($this) {
            self::PENDING   => 'heroicon-m-clock',
            self::CONFIRMED => 'heroicon-m-check-circle',
            self::PREPARING => 'heroicon-m-cog-6-tooth',
            self::EVENT_DAY => 'heroicon-m-sparkles',
            self::COMPLETED => 'heroicon-m-check-badge',
            self::CANCELLED => 'heroicon-m-x-circle',
        };
    }
}
