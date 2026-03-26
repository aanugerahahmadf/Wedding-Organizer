<?php

namespace App\Filament\User\Resources;

use App\Models\Order;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';

    protected static ?string $slug = 'my-orders';

    public static function getNavigationGroup(): ?string
    {
        return __('Transaksi & Aktivitas');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return static::getNavigationLabel();
    }

    public static function getNavigationLabel(): string
    {
        return __('Pesanan Saya');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('Belum ada pesanan'))
            ->emptyStateDescription(__('Mulai pesan paket pernikahan impianmu hari ini!'))
            ->emptyStateIcon('heroicon-o-shopping-bag')
            ->contentGrid([
                'md' => 1,
                'lg' => 2,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    // Header (Store & Status)
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('package.weddingOrganizer.name')
                            ->weight(FontWeight::Bold)
                            ->searchable()
                            ->grow(false)
                            ->icon('heroicon-s-building-storefront')
                            ->color('gray'),
                        Tables\Columns\TextColumn::make('status')
                            ->badge()
                            ->alignEnd(),
                    ])->extraAttributes(['class' => 'mb-2 border-b border-gray-100 dark:border-gray-800 pb-2']),
                    
                    // Product Details Box
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\ImageColumn::make('package.image_url')
                            ->height('5rem')
                            ->width('5rem')
                            ->grow(false)
                            ->extraImgAttributes(['class' => 'rounded-xl object-cover']),
                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('package.name')
                                ->weight(FontWeight::Bold)
                                ->size('lg'),
                            Tables\Columns\TextColumn::make('order_number')
                                ->formatStateUsing(fn($state) => 'No: ' . $state)
                                ->size('sm')
                                ->color('gray')
                                ->searchable(),
                            Tables\Columns\TextColumn::make('booking_date')
                                ->formatStateUsing(fn($state) => 'Acara: ' . \Carbon\Carbon::parse($state)->translatedFormat('d F Y'))
                                ->size('xs')
                                ->color('gray'),
                        ])->space(1),
                    ])->extraAttributes(['class' => 'bg-gray-50 dark:bg-gray-900 rounded-xl p-3']),
                    
                    // Total Footer
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('total_text')
                            ->state(fn() => 'Total Pesanan:')
                            ->size('sm')
                            ->color('gray')
                            ->alignEnd()
                            ->extraAttributes(['class' => 'mt-1']),
                        Tables\Columns\TextColumn::make('total_price')
                            ->money('idr')
                            ->weight(FontWeight::Black)
                            ->color('primary')
                            ->grow(false)
                            ->size('lg'),
                    ])->extraAttributes(['class' => 'mt-2 pt-2']),
                ])->space(3)->extraAttributes(['class' => 'p-4 bg-white dark:bg-gray-950 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800']),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(\App\Enums\OrderStatus::class)
                    ->label(__('Status Pesanan'))
                    ->searchable(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('Lihat Detail'))
                    ->button()
                    ->color('gray')
                    ->outlined()
                    ->size('lg')
                    ->icon('heroicon-m-eye')
                    ->slideOver()
                    ->modalWidth('2xl')
                    ->modalHeading(__('Detail Pesanan'))
                    ->modalDescription(fn($record) => $record->order_number),
                Tables\Actions\Action::make('pay')
                    ->label(__('Lanjut Bayar'))
                    ->button()
                    ->color('primary')
                    ->size('lg')
                    ->icon('heroicon-m-credit-card')
                    ->visible(fn ($record) => $record?->status === \App\Enums\OrderStatus::PENDING)
                    ->url(fn ($record) => $record ? "/user/payments/create?order_id={$record->id}" : '#'),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                // Alert/Status Box
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('status')
                                ->label(__('Status Pesanan'))
                                ->badge()
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                            Infolists\Components\TextEntry::make('payment_status')
                                ->label(__('Status Pembayaran'))
                                ->badge()
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                            Infolists\Components\TextEntry::make('order_number')
                                ->label(__('No. Pesanan'))
                                ->weight(FontWeight::Bold)
                                ->copyable(),
                        ]),
                    ])
                    ->extraAttributes(['class' => 'bg-gray-50 dark:bg-white/5 border-0 shadow-none rounded-2xl mb-4']),

                // Ordered Product
                Infolists\Components\Section::make(__('Paket Dipesan'))
                    ->icon('heroicon-o-shopping-bag')
                    ->iconColor('primary')
                    ->compact()
                    ->schema([
                        Infolists\Components\Grid::make()->schema([
                            Infolists\Components\ImageEntry::make('package.image_url')
                                ->hiddenLabel()
                                ->height('6rem')
                                ->width('6rem')
                                ->extraImgAttributes(['class' => 'rounded-xl object-cover shadow-sm'])
                                ->grow(false),
                            Infolists\Components\Group::make([
                                Infolists\Components\TextEntry::make('package.name')
                                    ->hiddenLabel()
                                    ->weight(FontWeight::Bold)
                                    ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                                Infolists\Components\TextEntry::make('package.weddingOrganizer.name')
                                    ->hiddenLabel()
                                    ->icon('heroicon-s-building-storefront')
                                    ->color('gray'),
                                Infolists\Components\TextEntry::make('booking_date')
                                    ->label(__('Untuk Tanggal Acara:'))
                                    ->inlineLabel()
                                    ->date('d F Y')
                                    ->weight(FontWeight::Bold)
                                    ->color('primary'),
                            ])->columnSpan(2),
                        ])->columns(3),
                    ]),

                // Pricing
                Infolists\Components\Section::make(__('Rincian Harga'))
                    ->icon('heroicon-o-currency-dollar')
                    ->iconColor('success')
                    ->compact()
                    ->schema([
                        Infolists\Components\TextEntry::make('total_price')
                            ->label(__('Total Pembayaran'))
                            ->money('idr')
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight(FontWeight::Black)
                            ->color('primary')
                            ->inlineLabel(),
                    ]),

                // Notes
                Infolists\Components\Section::make(__('Catatan Pemesan'))
                    ->icon('heroicon-o-document-text')
                    ->iconColor('gray')
                    ->compact()
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->hiddenLabel()
                            ->placeholder(__('Tidak ada catatan tambahan.'))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\User\Resources\OrderResource\Pages\ManageOrders::route('/'),
        ];
    }
}
