<?php

namespace App\Filament\Resources;

use App\Filament\Exports\PaymentExporter;
use App\Filament\Resources\PaymentResource\Pages;
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

    protected static ?int $navigationSort = 3;

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
                Forms\Components\Section::make(__('Info Pesanan & Pembayaran'))
                    ->description(__('Hubungkan pembayaran ke pesanan dan atur detail pembayaran.'))
                    ->schema([
                        Forms\Components\Select::make('order_id')
                            ->label(__('Pesanan'))
                            ->relationship('order', 'order_number')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('payment_number')
                            ->label(__('Nomor Pembayaran'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('payment_method')
                            ->label(__('Metode Pembayaran'))
                            ->relationship('methodDetails', 'name', function (Builder $query): void {
                                $query->where('is_active', true);
                            })
                            ->searchable()
                            ->preload()
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
                            ->options(Payment::statusLabels())
                            ->searchable()
                            ->required(),
                    ])->columns(['sm' => 2]),

                Forms\Components\Section::make(__('Detail Keuangan'))
                    ->description(__('Harga dan pelacakan pembayaran untuk transaksi ini.'))
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label(__('Jumlah'))
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
                            ->label(__('Biaya Admin'))
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->prefix('Rp')
                            ->readOnly(),
                        Forms\Components\TextInput::make('total_amount')
                            ->label(__('Total Jumlah'))
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly(),
                    ])->columns(['sm' => 3]),

                Forms\Components\Section::make(__('Bukti Pembayaran'))
                    ->description(__('Foto struk atau bukti transfer dari user.'))
                    ->schema([
                        Forms\Components\FileUpload::make('payment_proof')
                            ->label(__('Upload Bukti'))
                            ->image()
                            ->directory('payment-proofs')
                            ->visibility('public')
                            ->columnSpanFull(),
                    ]),

                Forms\Components\Section::make(__('Kecerdasan Buatan (AI Scan)'))
                    ->description(__('Hasil analisa otomatis terhadap bukti pembayaran.'))
                    ->schema([
                        Forms\Components\Placeholder::make('ai_status')
                            ->label(__('Status Scan AI'))
                            ->content(fn (?Payment $record) => $record?->metadata['ai_analysis']['is_verified_by_ai'] ?? false ? '✅ '.__('Valid (Terverifikasi Otomatis)') : '⏳ '.__('Belum di-scan/Manual')),
                        Forms\Components\KeyValue::make('metadata.ai_analysis')
                            ->label(__('Detail Analisa AI'))
                            ->keyLabel(__('Atribut'))
                            ->valueLabel(__('Hasil'))
                            ->columnSpan('full'),
                    ])
                    ->collapsed()
                    ->visible(fn (?Payment $record) => isset($record?->metadata['ai_analysis'])),

                Forms\Components\Section::make(__('Waktu'))
                    ->description(__('Tanggal penting untuk pembayaran ini.'))
                    ->schema([
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label(__('Dibayar Pada')),
                        Forms\Components\DateTimePicker::make('expired_at')
                            ->label(__('Kadaluarsa Pada')),
                        Forms\Components\DateTimePicker::make('cancelled_at')
                            ->label(__('Dibatalkan Pada')),
                    ])->columns(['sm' => 3]),

                Forms\Components\Section::make(__('Catatan Tambahan'))
                    ->description(__('Informasi atau keterangan tambahan.'))
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label(__('Catatan'))
                            ->columnSpan('full'),
                    ]),
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
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'processing' => 'info',
                        'success' => 'success',
                        'failed' => 'danger',
                        'expired', 'cancelled' => 'gray',
                        default => 'gray',
                    })
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
                    ->visible(fn (Payment $record) => $record->status === 'processing')
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
                    ->label(__('Tolak'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->size('lg')
                    ->button()
                    ->requiresConfirmation()
                    ->visible(fn (Payment $record) => in_array($record->status, ['pending', 'processing']))
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
