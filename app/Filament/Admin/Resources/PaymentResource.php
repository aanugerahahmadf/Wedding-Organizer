<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Exports\PaymentExporter;
use App\Filament\Admin\Resources\PaymentResource\Pages;
use App\Models\Payment;
use App\Models\PaymentMethod;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin \Eloquent
 * @property-read \App\Models\Payment $record
 */
class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

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
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make(__('Info Pesanan & Pembayaran'))
                            ->description(__('Hubungkan pembayaran ke pesanan dan atur detail pembayaran.'))
                            ->icon('heroicon-o-shopping-bag')
                            ->schema([
                                Forms\Components\Select::make('order_id')
                                    ->label(__('Pesanan'))
                                    ->relationship('order', 'order_number')
                                    ->searchable()
                                    ->preload()
                                    ->prefixIcon('heroicon-o-shopping-cart')
                                    ->required(),
                                Forms\Components\TextInput::make('payment_number')
                                    ->label(__('Nomor Pembayaran'))
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-hashtag'),
                                Forms\Components\Select::make('payment_method')
                                    ->label(__('Metode Pembayaran'))
                                    ->relationship('methodDetails', 'name', function (Builder $query): void {
                                        $query->where('is_active', true);
                                    })
                                    ->searchable()
                                    ->preload()
                                    ->prefixIcon('heroicon-o-credit-card')
                                    ->required()
                                    ->reactive()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state): void {
                                        if ($state && $get('amount')) {
                                            /** @var PaymentMethod|null $method */
                                            $method = PaymentMethod::where('code', $state)->first(['*']);

                                            $fee = floatval($method?->fee ?? 0);
                                            $set('admin_fee', $fee);
                                            $set('total_amount', floatval($get('amount') ?? 0) + $fee);
                                        }
                                    }),
                                Forms\Components\Select::make('status')
                                    ->label(__('Status Pembayaran'))
                                    ->options(\App\Enums\PaymentStatus::class)
                                    ->searchable()
                                    ->native(false)
                                    ->required(),
                            ])->columns(2),

                        Forms\Components\Section::make(__('Bukti & Keterangan'))
                            ->description(__('Catatan, struk, beserta hasil verifikasi otomatis AI.'))
                            ->icon('heroicon-o-document-check')
                            ->schema([
                                Forms\Components\FileUpload::make('payment_proof')
                                    ->label(__('Bukti Pembayaran (Struk)'))
                                    ->image()
                                    ->imageEditor()
                                    ->directory('payment-proofs')
                                    ->visibility('public')
                                    ->columnSpanFull(),
                                Forms\Components\Textarea::make('notes')
                                    ->label(__('Catatan Khusus'))
                                    ->rows(3)
                                    ->columnSpanFull(),
                                Forms\Components\Fieldset::make(__('Verifikasi AI / Otomatis'))
                                    ->visible(fn (?Payment $record) => isset($record?->metadata['ai_analysis']))
                                    ->schema([
                                        Forms\Components\Placeholder::make('ai_status')
                                            ->label(__('Status Tinjauan AI'))
                                            ->content(fn (?Payment $record) => $record?->metadata['ai_analysis']['is_verified_by_ai'] ?? false ? '✅ '.__('Valid (Sistem AI telah memverifikasi)') : '⏳ '.__('Manual / Gagal diverifikasi otomatis oleh AI')),
                                        Forms\Components\KeyValue::make('metadata.ai_analysis')
                                            ->label(__('Log / Nilai Ekstraksi AI'))
                                            ->keyLabel(__('Atribut Dokumen'))
                                            ->valueLabel(__('Hasil Analisis AI'))
                                            ->columnSpan('full'),
                                    ]),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make(__('Detail Keuangan'))
                            ->description(__('Rincian harga, fee admin, total nilai transfer.'))
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\TextInput::make('amount')
                                    ->label(__('Nominal Pesanan'))
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->reactive()
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state): void {
                                        $methodCode = $get('payment_method');
                                        if ($methodCode) {
                                            /** @var PaymentMethod|null $method */
                                            $method = PaymentMethod::where('code', $methodCode)->first(['*']);

                                            $fee = floatval($method?->fee ?? 0);
                                            $set('admin_fee', $fee);
                                            $set('total_amount', floatval($state ?? 0) + $fee);
                                        } else {
                                            $set('total_amount', floatval($state ?? 0) + floatval($get('admin_fee') ?? 0));
                                        }
                                    }),
                                Forms\Components\TextInput::make('admin_fee')
                                    ->label(__('Biaya Admin Tambahan'))
                                    ->required()
                                    ->numeric()
                                    ->default(0.00)
                                    ->prefix('Rp')
                                    ->readOnly(),
                                Forms\Components\TextInput::make('total_amount')
                                    ->label(__('Total Tagihan (Grand Total)'))
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->extraInputAttributes(['class' => 'font-bold text-xl text-primary-600'])
                                    ->readOnly(),
                            ]),

                        Forms\Components\Section::make(__('Log Waktu Kejadian'))
                            ->icon('heroicon-o-clock')
                            ->schema([
                                Forms\Components\DateTimePicker::make('paid_at')
                                    ->label(__('Tanggal Pembayaran Tiba'))
                                    ->native(false)
                                    ->prefixIcon('heroicon-s-check-circle'),
                                Forms\Components\DateTimePicker::make('expired_at')
                                    ->label(__('Jatuh Tempo (Kadaluarsa)'))
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-exclamation-circle'),
                                Forms\Components\DateTimePicker::make('cancelled_at')
                                    ->label(__('Dilakukan Pembatalan'))
                                    ->native(false)
                                    ->prefixIcon('heroicon-o-x-circle'),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.user.full_name')
                    ->label(__('Pelanggan'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_number')
                    ->label(__('Nomor Pembayaran'))
                    ->searchable()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('Jumlah'))
                    ->money('IDR')
                    ->sortable()
                    ->alignment('right'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('methodDetails.name')
                    ->label(__('Metode'))
                    ->searchable()
                    ->alignment('center'),
                Tables\Columns\IconColumn::make('metadata.ai_analysis.is_verified_by_ai')
                    ->label(__('AI'))
                    ->boolean()
                    ->trueIcon('heroicon-o-cpu-chip')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($state) => (bool) $state ? __('Diverifikasi oleh AI') : __('Verifikasi Manual/Belum Scan'))
                    ->alignment('center'),

                Tables\Columns\ImageColumn::make('payment_proof')
                    ->label(__('Bukti'))
                    ->width(80)
                    ->height(80)
                    ->square()
                    ->extraImgAttributes(['class' => 'rounded-lg shadow-sm border border-gray-200'])
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label(__('Dibayar Pada'))
                    ->dateTime()
                    ->sortable()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label(__('Kadaluarsa Pada'))
                    ->dateTime()
                    ->sortable()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('cancelled_at')
                    ->label(__('Dibatalkan Pada'))
                    ->dateTime()
                    ->sortable()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Waktu'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Diperbarui Pada'))
                    ->dateTime()
                    ->sortable()
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
                    ->label(__('Verifikasi'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->size('lg')
                    ->button()
                    ->requiresConfirmation()
                    ->visible(fn (Payment $record) => $record->status === \App\Enums\PaymentStatus::PROCESSING || $record->status === 'processing')
                    ->action(function (Payment $record): void {
                        $record->markAsSuccess();
                    })
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('Pembayaran diverifikasi'))
                            ->body(__('Pembayaran telah berhasil diverifikasi.'))
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
