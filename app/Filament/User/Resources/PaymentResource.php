<?php

namespace App\Filament\User\Resources;

use App\Filament\User\Resources\PaymentResource\Pages;
use App\Filament\User\Resources\PaymentResource\RelationManagers;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use App\Models\Order;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function getGloballySearchableAttributes(): array
    {
        return ['payment_number', 'order.order_number', 'payment_method'];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Transaksi & Aktivitas');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::whereHas('order', fn ($q) => $q->where('user_id', auth()->id()))->count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return static::getNavigationLabel();
    }

    public static function getNavigationLabel(): string
    {
        return __('Konfirmasi Bayar');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->whereHas('order', fn ($q) => $q->where('user_id', auth()->id()));
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Wizard::make(static::getWizardSteps())
                ->columnSpanFull(),
        ]);
    }

    public static function getWizardSteps(): array
    {
        return [
            Forms\Components\Wizard\Step::make(__('Data Tagihan'))
                ->schema([
                    Forms\Components\Select::make('order_id')->searchable()
                        ->label(__('Pilih Pesanan Anda'))
                        ->options(Order::where('user_id', auth()->id())->whereIn('status', [\App\Enums\OrderStatus::PENDING])->pluck('order_number', 'id'))
                        ->required()
                        ->searchable()
                        ->prefixIcon('heroicon-o-shopping-bag')
                        ->default(fn() => request()->query('order_id'))
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            if ($state) {
                                $order = Order::find($state);
                                if ($order) {
                                    $set('amount', $order->total_price);
                                    $methodCode = $get('payment_method');
                                    if ($methodCode) {
                                        $method = \App\Models\PaymentMethod::where('code', $methodCode)->first();
                                        $fee = $method?->fee ?? 0;
                                        $set('admin_fee', $fee);
                                        $set('total_amount', floatval($order->total_price) + floatval($fee));
                                    } else {
                                        $set('total_amount', $order->total_price);
                                    }
                                }
                            }
                        })
                        ->columnSpan(2),
                    Forms\Components\TextInput::make('amount')
                        ->label(__('Nominal Pesanan'))
                        ->required()
                        ->readOnly()
                        ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, ',', '.') : null)
                        ->extraInputAttributes(['class' => 'font-bold opacity-70'])
                        ->prefix('Rp')
                        ->prefixIcon('heroicon-o-banknotes')
                        ->default(function() {
                            $orderId = request()->query('order_id');
                            return $orderId ? Order::find($orderId)?->total_price : null;
                        })
                        ->dehydrateStateUsing(fn ($state) => $state ? (float) str_replace(',', '.', str_replace(['Rp', '.', ' '], '', $state)) : 0)
                        ->columnSpan(2),
                    Forms\Components\TextInput::make('admin_fee')
                        ->label(__('Biaya Layanan'))
                        ->required()
                        ->readOnly()
                        ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, ',', '.') : '0,00')
                        ->default(0)
                        ->prefix('Rp')
                        ->prefixIcon('heroicon-o-ticket')
                        ->dehydrateStateUsing(fn ($state) => $state ? (float) str_replace(',', '.', str_replace(['Rp', '.', ' '], '', $state)) : 0)
                        ->columnSpan(2),
                    Forms\Components\TextInput::make('total_amount')
                        ->label(__('Total Yang Harus Dibayar'))
                        ->required()
                        ->readOnly()
                        ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, ',', '.') : null)
                        ->extraInputAttributes(['class' => 'text-2xl font-bold text-primary-600 dark:text-primary-400'])
                        ->prefix('Rp')
                        ->prefixIcon('heroicon-o-calculator')
                        ->default(function() {
                            $orderId = request()->query('order_id');
                            return $orderId ? Order::find($orderId)?->total_price : null;
                        })
                        ->columnSpan(3),
                    Forms\Components\Select::make('payment_method')->searchable()
                        ->label(__('Metode Pembayaran'))
                        ->options(\App\Models\PaymentMethod::where('is_active', true)->pluck('name', 'code'))
                        ->required()
                        ->native(false)
                        ->searchable()
                        ->live()
                        ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                            if ($state) {
                                $method = \App\Models\PaymentMethod::where('code', $state)->first();
                                $fee = ($method?->type === \App\Enums\PaymentMethodType::WALLET) ? 0 : ($method?->fee ?? 0);
                                $set('admin_fee', $fee);
                                $set('total_amount', floatval($get('amount') ?? 0) + floatval($fee));
                            }
                        })
                        ->prefixIcon('heroicon-o-credit-card')
                        ->columnSpan(3),
                    // Info saldo wallet
                    Forms\Components\Placeholder::make('wallet_info')
                        ->hiddenLabel()
                        ->visible(function (Forms\Get $get) {
                            $method = \App\Models\PaymentMethod::where('code', $get('payment_method'))->first();
                            return $method && $method->type === \App\Enums\PaymentMethodType::WALLET;
                        })
                        ->content(function (Forms\Get $get) {
                            $user = auth()->user();
                            $balance = $user->balance ?? 0;
                            $total = floatval($get('total_amount') ?? 0);
                            $sufficient = $balance >= $total;
                            $balanceFmt = 'Rp ' . number_format($balance, 2, ',', '.');
                            $color = $sufficient ? 'emerald' : 'red';
                            $icon = $sufficient 
                                ? '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>' 
                                : '<svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4 inline-block mr-1" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>';
                            $msg = $sufficient
                                ? __('Saldo mencukupi. Pembayaran akan dikonfirmasi otomatis.')
                                : __('Saldo tidak mencukupi. Silakan top up terlebih dahulu.');
                            return new \Illuminate\Support\HtmlString(
                                '<div class="flex flex-col gap-2 p-4 rounded-xl border-2 border-' . $color . '-200 dark:border-' . $color . '-800 bg-' . $color . '-50 dark:bg-' . $color . '-950">'
                                . '<div class="flex items-center justify-between">'
                                . '<span class="text-sm font-semibold text-gray-600 dark:text-gray-300">' . __('Saldo Anda') . '</span>'
                                . '<span class="text-xl font-bold text-' . $color . '-600 dark:text-' . $color . '-400">' . $balanceFmt . '</span>'
                                . '</div>'
                                . '<p class="text-xs text-gray-500 dark:text-gray-400">' . $icon . ' ' . $msg . '</p>'
                                . '</div>'
                            );
                        })
                        ->columnSpanFull(),
                    // Info QRIS / Transfer Bank
                    Forms\Components\Group::make([
                        Forms\Components\Placeholder::make('qris_image')
                            ->hiddenLabel()
                            ->visible(function(Forms\Get $get) {
                                $method = \App\Models\PaymentMethod::where('code', $get('payment_method'))->first();
                                return $method && $method->type === \App\Enums\PaymentMethodType::QRIS;
                            })
                            ->content(function (Forms\Get $get) {
                                $method = \App\Models\PaymentMethod::where('code', $get('payment_method'))->first();
                                $url = $method?->qris_image_url;
                                if (!$url) return new \Illuminate\Support\HtmlString('<span class="text-red-500">' . __('QRIS tidak tersedia.') . '</span>');
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="flex flex-col items-center py-3">'
                                    . '<div class="p-3 bg-white dark:bg-gray-950 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800 inline-block">'
                                    . '<img src="' . $url . '" class="w-64 h-64 object-contain" alt="QRIS" />'
                                    . '</div>'
                                    . '</div>'
                                );
                            }),
                        Forms\Components\Actions::make([
                            Forms\Components\Actions\Action::make('download_qris')
                                ->label(__('Unduh Gambar QRIS'))
                                ->icon('heroicon-m-arrow-down-tray')
                                ->color('primary')
                                ->size('lg')
                                ->button()
                                ->url(function(Forms\Get $get) {
                                    $method = \App\Models\PaymentMethod::where('code', $get('payment_method'))->first();
                                    return $method?->qris_image_url;
                                }, true)
                                ->extraAttributes(['download' => 'qris.png']),
                        ])
                            ->alignCenter()
                            ->visible(function(Forms\Get $get) {
                                $method = \App\Models\PaymentMethod::where('code', $get('payment_method'))->first();
                                return $method && $method->type === \App\Enums\PaymentMethodType::QRIS && $method->qris_image_url;
                            }),
                        Forms\Components\Placeholder::make('qris_hint')
                            ->hiddenLabel()
                            ->visible(function(Forms\Get $get) {
                                $method = \App\Models\PaymentMethod::where('code', $get('payment_method'))->first();
                                return $method && $method->type === \App\Enums\PaymentMethodType::QRIS;
                            })
                            ->content(__('Simpan QRIS ini atau scan langsung dari ponsel Anda.'))
                            ->extraAttributes(['class' => 'text-[10px] text-center font-bold opacity-60 text-gray-500 dark:text-gray-400']),
                        Forms\Components\Placeholder::make('bank_transfer_info')
                            ->hiddenLabel()
                            ->visible(function(Forms\Get $get) {
                                $method = \App\Models\PaymentMethod::where('code', $get('payment_method'))->first();
                                return $method && $method->type === \App\Enums\PaymentMethodType::BANK_TRANSFER;
                            })
                            ->content(function (Forms\Get $get) {
                                $method = \App\Models\PaymentMethod::where('code', $get('payment_method'))->first();
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="flex flex-col items-center gap-2 bg-primary-50 dark:bg-primary-950 p-4 rounded-xl border-2 border-primary-200 dark:border-primary-800">'
                                    . '<p class="text-center text-gray-950 dark:text-white">' . __('Tujuan Transfer:') . '<br>'
                                    . '<span class="text-2xl font-bold tracking-widest text-primary-600 dark:text-primary-400 select-all">' . ($method->account_number ?? '-') . '</span><br>'
                                    . '<span class="text-sm font-semibold opacity-80 mt-1 text-gray-600 dark:text-gray-400">' . ($method->account_holder ?? '-') . '</span></p>'
                                    . '</div>'
                                );
                            }),
                    ])
                        ->columnSpanFull()
                        ->visible(fn(Forms\Get $get) => (bool) $get('payment_method')),
                ])->columns(6),
            Forms\Components\Wizard\Step::make(__('Konfirmasi Bayar'))
                ->schema([
                    Forms\Components\Placeholder::make('wallet_confirm_info')
                        ->hiddenLabel()
                        ->visible(function (Forms\Get $get) {
                            $method = \App\Models\PaymentMethod::where('code', $get('payment_method'))->first();
                            return $method && $method->type === \App\Enums\PaymentMethodType::WALLET;
                        })
                        ->content(function (Forms\Get $get) {
                            $user = auth()->user();
                            $balance = $user->balance ?? 0;
                            $total = floatval($get('total_amount') ?? 0);
                            $balanceFmt = 'Rp ' . number_format($balance, 2, ',', '.');
                            $totalFmt = 'Rp ' . number_format($total, 2, ',', '.');
                            $remaining = $balance - $total;
                            $remainingFmt = 'Rp ' . number_format(max(0, $remaining), 2, ',', '.');
                            if ($balance < $total) {
                                return new \Illuminate\Support\HtmlString(
                                    '<div class="p-4 rounded-xl bg-red-50 dark:bg-red-950 border-2 border-red-200 dark:border-red-800 text-center">'
                                    . '<p class="font-bold text-red-600 dark:text-red-400 text-lg flex items-center justify-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg> ' . __('Saldo Tidak Mencukupi') . '</p>'
                                    . '<p class="text-sm text-red-500 mt-1">' . __('Saldo Anda') . ': ' . $balanceFmt . ' &bull; ' . __('Dibutuhkan') . ': ' . $totalFmt . '</p>'
                                    . '<p class="text-xs mt-2 text-gray-500">' . __('Silakan top up saldo terlebih dahulu.') . '</p>'
                                    . '</div>'
                                );
                            }
                            return new \Illuminate\Support\HtmlString(
                                '<div class="p-4 rounded-xl bg-emerald-50 dark:bg-emerald-950 border-2 border-emerald-200 dark:border-emerald-800">'
                                . '<p class="font-bold text-emerald-600 dark:text-emerald-400 text-center text-lg mb-3 flex items-center justify-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg> ' . __('Konfirmasi Pembayaran Saldo') . '</p>'
                                . '<div class="flex justify-between text-sm mb-1"><span class="text-gray-500">' . __('Saldo Saat Ini') . '</span><span class=" font-bold">' . $balanceFmt . '</span></div>'
                                . '<div class="flex justify-between text-sm mb-1"><span class="text-gray-500">' . __('Total Dibayar') . '</span><span class=" font-bold text-red-500">- ' . $totalFmt . '</span></div>'
                                . '<div class="border-t border-emerald-200 dark:border-emerald-700 my-2"></div>'
                                . '<div class="flex justify-between text-sm"><span class="text-gray-500">' . __('Sisa Saldo') . '</span><span class=" font-bold text-emerald-600">' . $remainingFmt . '</span></div>'
                                . '<p class="text-xs text-center mt-3 text-gray-500">' . __('Klik Simpan untuk menyelesaikan pembayaran secara otomatis.') . '</p>'
                                . '</div>'
                            );
                        })
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('evidence_url')
                        ->label(__('Unggah Foto Bukti Transfer'))
                        ->helperText(__('Pastikan nominal dan rekening tujuan terlihat jelas.'))
                        ->image()
                        ->imageEditor()
                        ->required(function (Forms\Get $get) {
                            $method = \App\Models\PaymentMethod::where('code', $get('payment_method'))->first();
                            return !($method && $method->type === \App\Enums\PaymentMethodType::WALLET);
                        })
                        ->visible(function (Forms\Get $get) {
                            $method = \App\Models\PaymentMethod::where('code', $get('payment_method'))->first();
                            return !($method && $method->type === \App\Enums\PaymentMethodType::WALLET);
                        })
                        ->directory('payment-evidence')
                        ->panelAspectRatio('2:1')
                        ->panelLayout('integrated')
                        ->columnSpanFull(),
                ]),
        ];
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('Belum ada riwayat bayar'))
            ->emptyStateIcon('heroicon-o-wallet')
            ->contentGrid([
                'md' => 1,
                'lg' => 2,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    // Header
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('payment_number')
                            ->default(fn($record) => 'PAY-' . str_pad($record->id, 4, '0', STR_PAD_LEFT))
                            ->weight(FontWeight::Bold)
                            ->icon('heroicon-s-wallet')
                            ->color('gray')

                            ->grow(false),
                        Tables\Columns\TextColumn::make('status')
                            ->badge()
                            ->alignEnd(),
                    ])->extraAttributes(['class' => 'mb-2 border-b border-gray-100 dark:border-gray-800 pb-2']),

                    // Middle Box
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('order.order_number')
                                ->label(__('Pesanan'))
                                ->formatStateUsing(fn($state) => __('Transaksi:') . ' ' . $state)
                                ->weight(FontWeight::Bold)
                                ->searchable()
                                ->size('lg'),
                            Tables\Columns\TextColumn::make('payment_method')
                                ->badge()
                                ->color('info')
                                ->searchable()
                                ->size('xs'),
                                Tables\Columns\TextColumn::make('created_at')
                                ->formatStateUsing(fn($state) => __('Tanggal:') . ' ' . \Carbon\Carbon::parse($state)->translatedFormat('d F Y, H:i'))
                                ->size('xs')
                                ->color('gray'),
                        ])->space(1),
                    ])->extraAttributes(['class' => 'bg-gray-50 dark:bg-gray-900 rounded-xl p-3']),

                    // Footer
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('total_text')
                            ->state(fn() => __('Total Bayar') . ':')
                            ->size('sm')
                            ->color('gray')
                            ->alignEnd()
                            ->extraAttributes(['class' => 'mt-1']),
                        Tables\Columns\TextColumn::make('total_amount')
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 2, ',', '.'))
                            ->weight(FontWeight::Bold)
                            ->color('success')
                            ->grow(false)
                            ->size('lg'),
                    ])->extraAttributes(['class' => 'mt-2 pt-2']),

                ])->space(3)->extraAttributes(['class' => 'p-4 bg-white dark:bg-gray-950 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800']),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('order_id')->searchable()
                    ->label(__('Pesanan'))
                    ->relationship('order', 'order_number')

                    ->native(false)
                    ->preload(),
                Tables\Filters\SelectFilter::make('status')->searchable()
                    ->options(\App\Enums\PaymentStatus::class)
                    ->label(__('Status Bayar'))

                    ->native(false),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('Rincian'))
                    ->button()
                    ->color('gray')
                    ->outlined()
                    ->size('lg')
                    ->icon('heroicon-m-eye')
                    ->slideOver()
                    ->modalWidth('xl')
                    ->modalHeading(__('Detail Pembayaran')),
            ])
            ->actionsAlignment('center')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('Konfirmasi Bayar'))
                    ->button()
                    ->size('lg')
                    ->color('primary')
                    ->icon('heroicon-m-plus-circle')
                    ->slideOver()
                    ->modalWidth('3xl')
                    ->modalHeading(__('Konfirmasi Pembayaran'))
                    ->steps(static::getWizardSteps())
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['payment_number'] = 'PAY-' . strtoupper(str()->random(8));

                        $method = \App\Models\PaymentMethod::where('code', $data['payment_method'])->first();
                        if ($method && $method->type === \App\Enums\PaymentMethodType::WALLET) {
                            $user = auth()->user();
                            $totalAmount = floatval($data['total_amount'] ?? $data['amount'] ?? 0);

                            if ($user->balance < $totalAmount) {
                                \Filament\Notifications\Notification::make()
                                    ->danger()
                                    ->title(__('Saldo Tidak Mencukupi'))
                                    ->body(__('Saldo Anda tidak cukup untuk menyelesaikan pembayaran ini.'))
                                    ->send();

                                throw new \Filament\Exceptions\Halt();
                            }

                            $user->decrement('balance', $totalAmount);
                            $data['status'] = \App\Enums\PaymentStatus::SUCCESS;
                            $data['paid_at'] = now();
                        }

                        return $data;
                    })
                    ->after(function ($record) {
                        $method = \App\Models\PaymentMethod::where('code', $record->payment_method)->first();

                        if ($method && $method->type === \App\Enums\PaymentMethodType::WALLET) {
                            $record->order?->update([
                                'status' => \App\Enums\OrderStatus::CONFIRMED,
                                'payment_status' => \App\Enums\OrderPaymentStatus::PAID,
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->success()
                                ->title(__('Pembayaran Berhasil!'))
                                ->body(__('Saldo Anda telah dipotong dan pesanan dikonfirmasi otomatis.'))
                                ->send();
                        }
                    }),
            ]);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make()
                    ->schema([
                        \Filament\Infolists\Components\Grid::make(3)->schema([
                            \Filament\Infolists\Components\TextEntry::make('payment_number')
                                ->label(__('ID Pembayaran'))
                                ->default(fn($record) => 'PAY-' . str_pad($record->id, 4, '0', STR_PAD_LEFT))
                                ->weight(FontWeight::Bold)
                                ->copyable(),
                            \Filament\Infolists\Components\TextEntry::make('status')
                                ->label(__('Status'))
                                ->badge()
                                ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large),
                            \Filament\Infolists\Components\TextEntry::make('payment_method')
                                ->label(__('Metode'))
                                ->badge()
                                ->color('info'),
                        ]),
                    ])
                    ->extraAttributes(['class' => 'bg-gray-50 dark:bg-white/5 border-0 shadow-none rounded-2xl mb-4']),

                \Filament\Infolists\Components\Section::make(__('Data Transaksi'))
                    ->icon('heroicon-o-document-text')
                    ->compact()
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('order.order_number')
                            ->label(__('Nomor Pesanan'))
                            ->weight(FontWeight::Bold)
                            ->color('primary'),
                        \Filament\Infolists\Components\TextEntry::make('amount')
                            ->label(__('Nominal Pesanan'))
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 2, ',', '.'))
                            ->color('gray'),
                        \Filament\Infolists\Components\TextEntry::make('admin_fee')
                            ->label(__('Biaya Layanan'))
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 2, ',', '.'))
                            ->color('gray'),
                        \Filament\Infolists\Components\TextEntry::make('total_amount')
                            ->label(__('Total Dibayar'))
                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 2, ',', '.'))
                            ->weight(FontWeight::Bold)
                            ->color('success')
                            ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large),
                    ])->columns(2),

                \Filament\Infolists\Components\Section::make(__('Bukti Transfer'))
                    ->icon('heroicon-o-photo')
                    ->compact()
                    ->schema([
                        \Filament\Infolists\Components\ImageEntry::make('evidence_url')
                            ->hiddenLabel()
                            ->height('20rem')
                            ->width('100%')
                            ->extraImgAttributes(['class' => 'object-contain rounded-xl'])
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayments::route('/'),
        ];
    }
}
