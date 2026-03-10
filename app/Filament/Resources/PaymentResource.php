<?php

namespace App\Filament\Resources;

use App\Filament\Exports\PaymentExporter;
use App\Filament\Resources\PaymentResource\Pages;
use App\Models\Payment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ExportAction;
use Filament\Tables\Actions\ExportBulkAction;
use Filament\Tables\Table;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationLabel = 'Pembayaran';

    protected static ?string $navigationGroup = 'Transactions';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'payment_number';

    public static function getGloballySearchableAttributes(): array
    {
        return ['payment_number'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Verifikasi Pembayaran Pending';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Info Pesanan & Pembayaran')
                    ->description('Hubungkan pembayaran ke pesanan dan atur detail pembayaran.')
                    ->schema([
                        Forms\Components\Select::make('order_id')
                            ->label('Pesanan')
                            ->relationship('order', 'order_number')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('payment_number')
                            ->label('Nomor Pembayaran')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('payment_method')
                            ->label('Metode Pembayaran')
                            ->relationship('methodDetails', 'name', fn ($query) => $query->where('is_active', true))
                            ->searchable()
                            ->preload()
                            ->required()
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state): void {
                                if ($state && $get('amount')) {
                                    $method = \App\Models\PaymentMethod::where('code', $state)->first();
                                    $fee = $method ? floatval($method->fee) : 0;
                                    $set('admin_fee', $fee);
                                    $set('total_amount', floatval($get('amount')) + $fee);
                                }
                            }),
                        Forms\Components\Select::make('status')
                            ->label('Status Pembayaran')
                            ->options(Payment::statusLabels())
                            ->searchable()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Detail Keuangan')
                    ->description('Harga dan pelacakan pembayaran untuk transaksi ini.')
                    ->schema([
                        Forms\Components\TextInput::make('amount')
                            ->label('Jumlah')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->reactive()
                            ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, $state): void {
                                $methodCode = $get('payment_method');
                                if ($methodCode) {
                                    $method = \App\Models\PaymentMethod::where('code', $methodCode)->first();
                                    $fee = $method ? floatval($method->fee) : 0;
                                    $set('admin_fee', $fee);
                                    $set('total_amount', floatval($state) + $fee);
                                } else {
                                    $set('total_amount', floatval($state) + floatval($get('admin_fee') ?? 0));
                                }
                            }),
                        Forms\Components\TextInput::make('admin_fee')
                            ->label('Biaya Admin')
                            ->required()
                            ->numeric()
                            ->default(0.00)
                            ->prefix('Rp')
                            ->readOnly(),
                        Forms\Components\TextInput::make('total_amount')
                            ->label('Total Jumlah')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly(),
                    ])->columns(3),

                Forms\Components\Section::make('Bukti Pembayaran')
                    ->description('Foto struk atau bukti transfer dari user.')
                    ->schema([
                        Forms\Components\FileUpload::make('payment_proof')
                            ->label('Upload Bukti')
                            ->image()
                            ->directory('payment-proofs')
                            ->visibility('public')
                            ->columnSpan('full'),
                    ]),

                Forms\Components\Section::make('Kecerdasan Buatan (AI Scan)')
                    ->description('Hasil analisa otomatis terhadap bukti pembayaran.')
                    ->schema([
                        Forms\Components\Placeholder::make('ai_status')
                            ->label('Status Scan AI')
                            ->content(fn ($record) => $record?->metadata['ai_analysis']['is_verified_by_ai'] ?? false ? '✅ Valid (Terverifikasi Otomatis)' : '⏳ Belum di-scan/Manual'),
                        Forms\Components\KeyValue::make('metadata.ai_analysis')
                            ->label('Detail Analisa AI')
                            ->keyLabel('Atribut')
                            ->valueLabel('Hasil')
                            ->columnSpan('full'),
                    ])
                    ->collapsed()
                    ->visible(fn ($record) => isset($record->metadata['ai_analysis'])),

                Forms\Components\Section::make('Waktu')
                    ->description('Tanggal penting untuk pembayaran ini.')
                    ->schema([
                        Forms\Components\DateTimePicker::make('paid_at')
                            ->label('Dibayar Pada'),
                        Forms\Components\DateTimePicker::make('expired_at')
                            ->label('Kadaluarsa Pada'),
                        Forms\Components\DateTimePicker::make('cancelled_at')
                            ->label('Dibatalkan Pada'),
                    ])->columns(3),

                Forms\Components\Section::make('Catatan Tambahan')
                    ->description('Informasi atau keterangan tambahan.')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan')
                            ->columnSpan('full'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('order.user.name')
                    ->label('Pelanggan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('Payment Number')
                    ->searchable()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable()
                    ->alignment('right'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
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
                    ->label('Metode')
                    ->searchable()
                    ->alignment('center'),
                Tables\Columns\IconColumn::make('metadata.ai_analysis.is_verified_by_ai')
                    ->label('AI')
                    ->boolean()
                    ->trueIcon('heroicon-o-cpu-chip')
                    ->falseIcon('heroicon-o-user')
                    ->trueColor('success')
                    ->falseColor('gray')
                    ->tooltip(fn ($state) => $state ? 'Diverifikasi oleh AI' : 'Verifikasi Manual/Belum Scan')
                    ->alignment('center'),

                Tables\Columns\ImageColumn::make('payment_proof')
                    ->label('Bukti')
                    ->width(80)
                    ->height(80)
                    ->square()
                    ->extraImgAttributes(['class' => 'rounded-lg shadow-sm border border-gray-200'])
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Dibayar Pada')
                    ->dateTime()
                    ->sortable()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('expired_at')
                    ->label('Kadaluarsa Pada')
                    ->dateTime()
                    ->sortable()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('cancelled_at')
                    ->label('Dibatalkan Pada')
                    ->dateTime()
                    ->sortable()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Waktu')
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Diperbarui Pada')
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
                    ->label('Verifikasi')
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
                            ->title('Pembayaran diverifikasi')
                            ->body('Pembayaran telah berhasil diverifikasi.')
                    ),
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->size('lg')
                    ->button()
                    ->requiresConfirmation()
                    ->visible(fn (Payment $record) => in_array($record->status, ['pending', 'processing']))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->required()
                            ->label('Alasan Penolakan'),
                    ])
                    ->action(function (Payment $record, array $data): void {
                        $record->markAsFailed($data['reason']);
                    })
                    ->successNotification(
                        Notification::make()
                            ->danger()
                            ->title('Pembayaran ditolak')
                            ->body('Pembayaran telah ditolak.')
                    ),
                Tables\Actions\EditAction::make()
                    ->button()
                    ->color('warning')
                    ->size('lg')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Pembayaran diperbarui')
                            ->body('Pembayaran telah berhasil diperbarui.')
                    ),
                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->color('danger')
                    ->size('lg')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Pembayaran dihapus')
                            ->body('Pembayaran telah berhasil dihapus.')
                    ),
            ])
            // ->headerActions([
            //     ExportAction::make()
            //         ->exporter(PaymentExporter::class)
            //         ->label('Export Payments'),
            // ])
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
