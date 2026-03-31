<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Exports\PaymentExporter;
use App\Filament\Admin\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\PaymentMethod;
use App\Models\Order;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\HtmlString;

/**
 * @mixin \Eloquent
 * @property-read \App\Models\Payment $record
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'payment_number';

    public static function getModelLabel(): string
    {
        return __('Pembayaran');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Pembayaran');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['payment_number'];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Transaksi');
    }

    public static function getNavigationLabel(): string
    {
        return __('Pembayaran');
    }

    public static function getNavigationBadge(): ?string
    {
        /** @var Builder $query */
        $query = static::$model::query();

        return (string) $query->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('Verifikasi Pembayaran Pending');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Wizard::make([
                    Forms\Components\Wizard\Step::make(__('Data Tagihan'))
                        ->description(__('Detail pesanan dan pilih metode pembayaran'))
                        ->icon('heroicon-o-shopping-bag')
                        ->schema([
                            Forms\Components\Select::make('order_id')
                                ->label(__('Pilih Pesanan'))
                                ->options(Order::whereIn('status', [\App\Enums\OrderStatus::PENDING])->pluck('order_number', 'id'))
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
                                                $method = PaymentMethod::where('code', $methodCode)->first();
                                                $fee = $method?->fee ?? 0;
                                                $set('admin_fee', $fee);
                                                $set('total_amount', floatval($order->total_price) + floatval($fee));
                                            } else {
                                                $set('total_amount', $order->total_price);
                                            }
                                        }
                                    }
                                })
                                ->columnSpan(3),
                            Forms\Components\Select::make('payment_method')
                                ->label(__('Metode Pembayaran'))
                                ->options(PaymentMethod::where('is_active', true)->pluck('name', 'code'))
                                ->required()
                                ->native(false)
                                ->searchable()
                                ->live()
                                ->afterStateUpdated(function ($state, Forms\Set $set, Forms\Get $get) {
                                    if ($state) {
                                        $method = PaymentMethod::where('code', $state)->first();
                                        $fee = ($method?->type === \App\Enums\PaymentMethodType::WALLET) ? 0 : ($method?->fee ?? 0);
                                        $set('admin_fee', $fee);
                                        $set('total_amount', floatval($get('amount') ?? 0) + floatval($fee));
                                    }
                                })
                                ->prefixIcon('heroicon-o-credit-card')
                                ->columnSpan(3),
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
                                ->columnSpan(2),

                            // Info saldo wallet
                            Forms\Components\Placeholder::make('wallet_info')
                                ->hiddenLabel()
                                ->visible(function (Forms\Get $get) {
                                    $method = PaymentMethod::where('code', $get('payment_method'))->first();
                                    return $method && $method->type === \App\Enums\PaymentMethodType::WALLET;
                                })
                                ->content(function (Forms\Get $get, ?Payment $record) {
                                    $user = ($orderId = $get('order_id')) ? Order::find($orderId)?->user : ($record ? $record->order?->user : auth()->user());
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
                                    return new HtmlString(
                                        '<div class="flex flex-col gap-2 p-4 rounded-xl border-2 border-' . $color . '-200 dark:border-' . $color . '-800 bg-' . $color . '-50 dark:bg-' . $color . '-950">'
                                        . '<div class="flex items-center justify-between">'
                                        . '<span class="text-sm font-semibold text-gray-600 dark:text-gray-300">' . __('Saldo Pelanggan') . '</span>'
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
                                        $method = PaymentMethod::where('code', $get('payment_method'))->first();
                                        return $method && $method->type === \App\Enums\PaymentMethodType::QRIS;
                                    })
                                    ->content(function (Forms\Get $get) {
                                        $method = PaymentMethod::where('code', $get('payment_method'))->first();
                                        $url = $method?->qris_image_url;
                                        if (!$url) return new HtmlString('<span class="text-red-500">' . __('QRIS tidak tersedia.') . '</span>');
                                        return new HtmlString(
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
                                            $method = PaymentMethod::where('code', $get('payment_method'))->first();
                                            return $method?->qris_image_url;
                                        }, true)
                                        ->extraAttributes(['download' => 'qris.png']),
                                ])
                                    ->alignCenter()
                                    ->visible(function(Forms\Get $get) {
                                        $method = PaymentMethod::where('code', $get('payment_method'))->first();
                                        return $method && $method->type === \App\Enums\PaymentMethodType::QRIS && $method->qris_image_url;
                                    }),
                                Forms\Components\Placeholder::make('qris_hint')
                                    ->hiddenLabel()
                                    ->visible(function(Forms\Get $get) {
                                        $method = PaymentMethod::where('code', $get('payment_method'))->first();
                                        return $method && $method->type === \App\Enums\PaymentMethodType::QRIS;
                                    })
                                    ->content(__('Simpan QRIS ini atau scan langsung dari ponsel Anda.'))
                                    ->extraAttributes(['class' => 'text-[10px] text-center font-bold opacity-60 text-gray-500 dark:text-gray-400']),
                                Forms\Components\Placeholder::make('bank_transfer_info')
                                    ->hiddenLabel()
                                    ->visible(function(Forms\Get $get) {
                                        $method = PaymentMethod::where('code', $get('payment_method'))->first();
                                        return $method && $method->type === \App\Enums\PaymentMethodType::BANK_TRANSFER;
                                    })
                                    ->content(function (Forms\Get $get) {
                                        $method = PaymentMethod::where('code', $get('payment_method'))->first();
                                        return new HtmlString(
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
                        ->description(__('Unggah bukti dan selesaikan pembayaran'))
                        ->icon('heroicon-o-check-circle')
                        ->schema([
                            Forms\Components\Placeholder::make('wallet_confirm_info')
                                ->hiddenLabel()
                                ->visible(function (Forms\Get $get) {
                                    $method = PaymentMethod::where('code', $get('payment_method'))->first();
                                    return $method && $method->type === \App\Enums\PaymentMethodType::WALLET;
                                })
                                ->content(function (Forms\Get $get, ?Payment $record) {
                                    $user = ($orderId = $get('order_id')) ? Order::find($orderId)?->user : ($record ? $record->order?->user : auth()->user());
                                    $balance = $user->balance ?? 0;
                                    $total = floatval($get('total_amount') ?? 0);
                                    $balanceFmt = 'Rp ' . number_format($balance, 2, ',', '.');
                                    $totalFmt = 'Rp ' . number_format($total, 2, ',', '.');
                                    $remaining = $balance - $total;
                                    $remainingFmt = 'Rp ' . number_format(max(0, $remaining), 2, ',', '.');
                                    if ($balance < $total) {
                                        return new HtmlString(
                                            '<div class="p-4 rounded-xl bg-red-50 dark:bg-red-950 border-2 border-red-200 dark:border-red-800 text-center">'
                                            . '<p class="font-bold text-red-600 dark:text-red-400 text-lg flex items-center justify-center gap-2"><svg xmlns="http://www.w3.org/2000/svg" class="w-6 h-6" fill="none" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="m9.75 9.75 4.5 4.5m0-4.5-4.5 4.5M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg> ' . __('Saldo Tidak Mencukupi') . '</p>'
                                            . '<p class="text-sm text-red-500 mt-1">' . __('Saldo Pelanggan') . ': ' . $balanceFmt . ' &bull; ' . __('Dibutuhkan') . ': ' . $totalFmt . '</p>'
                                            . '<p class="text-xs mt-2 text-gray-500">' . __('Silakan top up saldo pelanggan terlebih dahulu atau gunakan metode lain.') . '</p>'
                                            . '</div>'
                                        );
                                    }
                                    return new HtmlString(
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
                            Forms\Components\FileUpload::make('payment_proof')
                                ->label(__('Unggah Foto Bukti Transfer'))
                                ->helperText(__('Pastikan nominal dan rekening tujuan terlihat jelas.'))
                                ->image()
                                ->imageEditor()
                                ->required(function (Forms\Get $get) {
                                    $method = PaymentMethod::where('code', $get('payment_method'))->first();
                                    return !($method && $method->type === \App\Enums\PaymentMethodType::WALLET);
                                })
                                ->visible(function (Forms\Get $get) {
                                    $method = PaymentMethod::where('code', $get('payment_method'))->first();
                                    return !($method && $method->type === \App\Enums\PaymentMethodType::WALLET);
                                })
                                ->directory('payment-proofs')
                                ->panelAspectRatio('2:1')
                                ->panelLayout('integrated')
                                ->columnSpanFull(),
                            Forms\Components\Select::make('status')
                                ->label(__('Status Pembayaran'))
                                ->options(\App\Enums\PaymentStatus::class)
                                ->native(false)
                                ->required()
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('notes')
                                ->label(__('Catatan Khusus'))
                                ->rows(3)
                                ->columnSpanFull(),
                        ]),
                ])->columnSpanFull()
            ]);
    }


    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.user.full_name')
                    ->label(__('Pelanggan'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_number')
                    ->searchable()
                    ->label(__('No. Pembayaran')),
                Tables\Columns\TextColumn::make('order.order_number')
                    ->searchable()
                    ->label(__('No. Pesanan'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('Jumlah'))
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 2, ',', '.'))
                    ->searchable()
                    ->alignment('right'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->searchable()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('methodDetails.name')
                    ->searchable()
                    ->label(__('Metode'))
                    ->badge()
                    ->searchable()
                    ->color('info'),
                Tables\Columns\IconColumn::make('metadata.ai_analysis.is_verified_by_ai')
                    ->label(__('Verifikasi AI'))
                    ->boolean()
                    ->searchable()
                    ->trueIcon('heroicon-o-cpu-chip')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($state) => (bool) $state ? __('Diverifikasi oleh AI') : __('Verifikasi Manual/Belum Scan'))
                    ->alignment('center'),
                Tables\Columns\ImageColumn::make('payment_proof')
                    ->label(__('Bukti'))
                    ->searchable()
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label(__('Dibayar Pada'))
                    ->dateTime()
                    ->searchable()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label(__('Kadaluarsa Pada'))
                    ->dateTime()
                    ->searchable()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('cancelled_at')
                    ->label(__('Dibatalkan Pada'))
                    ->dateTime()
                    ->searchable()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Waktu'))
                    ->dateTime('d M Y H:i')
                    ->searchable()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Diperbarui Pada'))
                    ->dateTime()
                    ->searchable()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->slideOver()
                    ->button()
                    ->color('info')
                    ->size('lg'),
                Tables\Actions\Action::make('verify')
                    ->label(fn (Payment $record) => ($record->metadata['ai_analysis']['is_verified_by_ai'] ?? false) ? __('Setujui (Rekomendasi AI)') : __('Setujui'))
                    ->icon(fn (Payment $record) => ($record->metadata['ai_analysis']['is_verified_by_ai'] ?? false) ? 'heroicon-o-cpu-chip' : 'heroicon-o-check-circle')
                    ->color('success')
                    ->size('lg')
                    ->button()
                    ->requiresConfirmation()
                    ->visible(fn (Payment $record) => in_array($record->status, [\App\Enums\PaymentStatus::PENDING, \App\Enums\PaymentStatus::PROCESSING, 'pending', 'processing'], true))
                    ->action(function (Payment $record): void {
                        $record->markAsSuccess();
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('Pembayaran disetujui'))
                            ->body(__('Pembayaran telah berhasil disetujui.'))
                    ),
                Tables\Actions\Action::make('reject')
                    ->slideOver()
                    ->label(__('Tolak'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->size('lg')
                    ->button()
                    ->requiresConfirmation()
                    ->visible(fn (Payment $record) => in_array($record->status, [\App\Enums\PaymentStatus::PENDING, \App\Enums\PaymentStatus::PROCESSING, 'pending', 'processing'], true))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->required()
                            ->label(__('Alasan Penolakan')),
                    ])
                    ->action(function (Payment $record, array $data): void {
                        $record->markAsFailed($data['reason']);
                    })
                    ->successNotification(
                        Notification::make()
                            ->danger()
                            ->title(__('Pembayaran ditolak'))
                            ->body(__('Pembayaran telah ditolak.'))
                    ),
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->button()
                    ->color('warning')
                    ->size('lg')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('Pembayaran diperbarui'))
                            ->body(__('Pembayaran telah berhasil diperbarui.'))
                    ),
                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->color('danger')
                    ->size('lg')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('Pembayaran dihapus'))
                            ->body(__('Pembayaran telah berhasil dihapus.'))
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    ExportBulkAction::make()
                        ->exporter(PaymentExporter::class),
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManagePayments::route('/'),
        ];
    }
}
