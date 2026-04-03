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

    public static function getGloballySearchableAttributes(): array
    {
        return ['order_number', 'package.name', 'package.weddingOrganizer.name'];
    }



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
            ->emptyStateDescription(__('Wujudkan acara impianmu dengan paket terbaik dari kami. Mulai pesan sekarang!'))
            ->emptyStateIcon('heroicon-o-shopping-bag')
            ->emptyStateActions([
                Tables\Actions\Action::make('start_shopping')
                    ->label(__('Pesan Sekarang'))
                    ->url(PackageResource::getUrl())
                    ->button()
                    ->color('primary')
                    ->size('lg')
                    ->icon('heroicon-m-sparkles'),
            ])
            ->contentGrid([
                'sm' => 2,
                'md' => 3,
                'lg' => 5,
                'xl' => 6,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    // Image Section (Top Center in White Box)
                    Tables\Columns\ImageColumn::make('package.image_url')
                        ->label('')
                        ->height('14rem')
                        ->width('100%')
                        ->extraAttributes(['class' => 'w-full flex justify-center items-center bg-white p-2 rounded-t-xl overflow-hidden aspect-square'])
                        ->extraImgAttributes([
                            'class' => 'object-contain transition-all duration-500 opacity-90 group-hover:opacity-100 !mx-auto',
                            'style' => 'max-height: 100%; width: auto; object-fit: contain;'
                        ]),

                    Tables\Columns\Layout\Stack::make([
                        // Store Info (Single Appearance)
                        Tables\Columns\TextColumn::make('package.weddingOrganizer.name')
                            ->size('xs')
                            ->weight(FontWeight::Bold)
                            ->color('gray')
                            ->icon('heroicon-s-building-storefront')
,
                        Tables\Columns\TextColumn::make('package.name')
                            ->weight(FontWeight::Bold)
                            ->size('sm')
                            ->lineClamp(1)
                            ->color('info')

                            ->extraAttributes(['class' => 'mt-1']),
                        Tables\Columns\TextColumn::make('order_number')
                            ->prefix('#')
                            ->size('xs')
                            ->color('gray')
,
                        // Metadata & Status Footer
                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\TextColumn::make('booking_date')
                                ->date('d/m/y')
                                ->size('xs')
                                ->color('gray'),
                            Tables\Columns\TextColumn::make('total_price')
                                ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 2, ',', '.'))
                                ->weight(FontWeight::Bold)
                                ->size('xs')
                                ->alignEnd(),
                        ])->extraAttributes(['class' => 'mt-2 pt-2 border-t border-gray-100/10']),
                        // Status Badge at the very bottom
                        Tables\Columns\TextColumn::make('status')
                            ->badge()
                            ->color(fn ($state) => $state->getColor())
                            ->extraAttributes(['class' => 'mt-1 self-end']),
                    ])->space(1)->extraAttributes(['class' => 'p-2']),
                ])->extraAttributes([
                    'class' => 'bg-white dark:bg-gray-950 rounded-xl shadow-sm hover:shadow-lg transition-all border border-gray-100 dark:border-gray-800 group overflow-hidden'
                ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->searchable()
                    ->options(\App\Enums\OrderStatus::class)
                    ->label(__('Status Pesanan')),
                Tables\Filters\Filter::make('id')
                    ->form([
                        Forms\Components\TextInput::make('value')
                            ->label(__('ID')),
                    ])
                    ->query(fn (Builder $query, array $data) => $query->when($data['value'], fn ($q, $id) => $q->where('id', $id)))
                    ->hidden(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->hiddenLabel()
                    ->iconButton()
                    ->color('gray')
                    ->size('lg')
                    ->icon('heroicon-m-eye')
                    ->extraAttributes(['class' => 'flex-1 justify-center !rounded-lg border border-gray-200 dark:border-gray-800'])
                    ->slideOver()
                    ->modalWidth('2xl')
                    ->modalHeading(__('Detail Pesanan'))
                    ->modalDescription(fn($record) => $record->order_number),
                Tables\Actions\Action::make('pay')
                    ->label(__('Bayar'))
                    ->button()
                    ->color('warning')
                    ->size('lg')
                    ->icon('heroicon-m-credit-card')
                    ->extraAttributes(['class' => 'flex-1 justify-center !rounded-lg'])
                    ->visible(fn ($record) => $record?->status === \App\Enums\OrderStatus::PENDING)
                    ->slideOver()
                    ->modalWidth('3xl')
                    ->modalHeading(__('Konfirmasi Pembayaran'))
                    ->steps(fn ($record) => \App\Filament\User\Resources\PaymentResource::getWizardSteps())
                    ->fillForm(fn ($record) => [
                        'order_id'    => $record->id,
                        'amount'      => $record->total_price,
                        'total_amount'=> $record->total_price,
                        'admin_fee'   => 0,
                    ])
                    ->action(function ($record, array $data) {
                        $method = \App\Models\PaymentMethod::where('code', $data['payment_method'])->first();
                        $user = auth()->user();
                        $totalAmount = floatval($data['total_amount'] ?? $data['amount'] ?? 0);

                        // Wallet payment
                        if ($method && $method->type === \App\Enums\PaymentMethodType::WALLET) {
                            if ($user->balance < $totalAmount) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title(__('Saldo Tidak Mencukupi'))
                                    ->body(__('Saldo Anda tidak cukup untuk menyelesaikan pembayaran ini.'))
                                    ->send();
                                return;
                            }
                            $user->decrement('balance', $totalAmount);
                            $status    = \App\Enums\PaymentStatus::SUCCESS;
                            $paidAt    = now();
                            $orderStatus = \App\Enums\OrderStatus::CONFIRMED;
                            $orderPay    = \App\Enums\OrderPaymentStatus::PAID;
                        } else {
                            $status    = \App\Enums\PaymentStatus::PENDING;
                            $paidAt    = null;
                            $orderStatus = \App\Enums\OrderStatus::PENDING;
                            $orderPay    = \App\Enums\OrderPaymentStatus::UNPAID;
                        }

                        // Buat payment record
                        $payment = \App\Models\Payment::create([
                            'order_id'       => $record->id,
                            'user_id'        => $user->id,
                            'payment_method' => $data['payment_method'],
                            'payment_number' => 'PAY-' . strtoupper(str()->random(8)),
                            'amount'         => $data['amount'],
                            'admin_fee'      => $data['admin_fee'] ?? 0,
                            'total_amount'   => $totalAmount,
                            'status'         => $status,
                            'paid_at'        => $paidAt,
                            'evidence_url'   => $data['evidence_url'] ?? null,
                        ]);

                        // Update order
                        $record->update([
                            'status'         => $orderStatus,
                            'payment_status' => $orderPay,
                        ]);

                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title($method && $method->type === \App\Enums\PaymentMethodType::WALLET
                                ? __('Pembayaran Berhasil! Pesanan dikonfirmasi otomatis.')
                                : __('Bukti pembayaran berhasil dikirim.'))
                            ->send();
                    }),
            ])
            ->actionsAlignment('center');
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
                                    ->icon('govicon-building')
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
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 2, ',', '.'))
                            ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                            ->weight(FontWeight::Bold)
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
