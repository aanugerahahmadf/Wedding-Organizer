<?php

namespace App\Filament\User\Resources;

use App\Models\Voucher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $slug = 'vouchers';

    public static function getNavigationGroup(): ?string
    {
        return __('Belanja & Jelajahi');
    }

    public static function getNavigationLabel(): string
    {
        return __('Deals & Coupons');
    }

    protected static ?int $navigationSort = 3;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return static::getNavigationLabel();
    }


    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('Belum ada kupon'))
            ->contentGrid([
                'md' => 1,
                'lg' => 2,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    // Ticket Content
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('discount_value')
                                ->formatStateUsing(fn ($state, $record) => $record?->discount_type === 'percent' ? 'Diskon ' . $state . '%' : 'Cashback Rp ' . number_format($state, 0, ',', '.'))
                                ->weight(FontWeight::Black)
                                ->size('xl')
                                ->color('primary'),
                            Tables\Columns\TextColumn::make('name')
                                ->weight(FontWeight::Bold)
                                ->size('sm')
                                ->color('gray'),
                        ])->space(1),
                        Tables\Columns\TextColumn::make('code')
                            ->weight(FontWeight::Black)
                            ->size('sm')
                            ->fontFamily('mono')
                            ->color('primary')
                            ->alignEnd()
                            ->grow(false)
                            ->extraAttributes(['class' => 'px-3 py-1 bg-primary-50 dark:bg-primary-950 rounded-lg border-2 border-dashed border-primary-500']),
                    ])->extraAttributes(['class' => 'mb-2 border-b border-gray-100 dark:border-gray-800 pb-2']),
                    
                    // Ticket Footer
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('valid_until')
                            ->formatStateUsing(fn($state) => 'Berlaku s/d: ' . \Carbon\Carbon::parse($state)->translatedFormat('d F Y'))
                            ->size('xs')
                            ->color('gray')
                            ->icon('heroicon-o-clock'),
                    ])->extraAttributes(['class' => 'mt-2']),
                ])->space(3)->extraAttributes(['class' => 'p-4 bg-white dark:bg-gray-950 rounded-2xl shadow-sm border-y border-r border-l-[6px] border-gray-100 dark:border-gray-800 border-l-primary-500 transition-all hover:border-l-primary-600']),
            ])
            ->actions([
                Tables\Actions\Action::make('claim')
                    ->label(__('Gunakan'))
                    ->button()
                    ->color('success')
                    ->size('lg')
                    ->icon('heroicon-m-check-badge')
                    ->action(fn() => Notification::make()->title(__('Kupon Disalin'))->success()->send()),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\User\Resources\VoucherResource\Pages\ManageVouchers::route('/'),
        ];
    }
}
