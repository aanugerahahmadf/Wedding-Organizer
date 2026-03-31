<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\TopupResource\Pages;
use App\Models\Topup;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * @mixin \Eloquent
 * @property-read \App\Models\Topup $record
 */
class TopupResource extends Resource
{
    protected static ?string $model = Topup::class;

    protected static ?string $navigationIcon = 'heroicon-o-wallet';

    protected static ?int $navigationSort = 3;

    public static function getModelLabel(): string
    {
        return __('Topup');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Topup');
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Transaksi');
    }

    public static function getNavigationLabel(): string
    {
        return __('Topup Saldo');
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
        return __('Total Topup Saldo');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->searchable()
                    ->label(__('Nama Pengguna'))
                    ->relationship('user', 'full_name')
                    ->preload()
                    ->required(),
                Forms\Components\TextInput::make('reference_number')
                    ->label(__('Nomor Referensi'))
                    ->required()
                    ->disabled()
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->label(__('Jumlah'))
                    ->required()
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, ',', '.') : null)
                    ->dehydrateStateUsing(fn ($state) => $state ? (float) str_replace(',', '.', str_replace(['Rp', '.', ' '], '', $state)) : null)
                    ->prefix('Rp'),
                Forms\Components\TextInput::make('admin_fee')
                    ->label(__('Biaya Admin'))
                    ->required()
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, ',', '.') : '0,00')
                    ->dehydrateStateUsing(fn ($state) => $state ? (float) str_replace(',', '.', str_replace(['Rp', '.', ' '], '', $state)) : 0)
                    ->default(0.00)
                    ->prefix('Rp'),
                Forms\Components\TextInput::make('total_amount')
                    ->label(__('Total Jumlah'))
                    ->required()
                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, ',', '.') : null)
                    ->dehydrateStateUsing(fn ($state) => $state ? (float) str_replace(',', '.', str_replace(['Rp', '.', ' '], '', $state)) : null)
                    ->prefix('Rp'),
                Forms\Components\TextInput::make('payment_method')
                    ->label(__('Metode Pembayaran'))
                    ->maxLength(255),
                Forms\Components\Select::make('status')
                    ->searchable()
                    ->label(__('Status'))
                    ->options(\App\Enums\TopupStatus::class)
                    ->required(),
                Forms\Components\FileUpload::make('payment_proof')
                    ->label(__('Bukti Pembayaran'))
                    ->image()
                    ->directory('payment-proofs'),
                Forms\Components\DateTimePicker::make('paid_at')
                    ->label(__('Dibayar Pada')),
                Forms\Components\Textarea::make('notes')
                    ->label(__('Catatan'))
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->searchable()
                    ->label(__('Nomor Referensi')),
                Tables\Columns\TextColumn::make('user.full_name')
                    ->searchable()
                    ->label(__('Nama Pengguna')),
                Tables\Columns\TextColumn::make('amount')
                    ->label(__('Jumlah'))
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 2, ',', '.')),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('Total Bayar'))
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 2, ',', '.')),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('Metode Pembayaran'))
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge(),
                Tables\Columns\ImageColumn::make('payment_proof')
                    ->label(__('Bukti')),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label(__('Dibayar Pada'))
                    ->dateTime(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Dibuat Pada'))
                    ->dateTime()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->searchable()
                    ->label(__('Status'))
                    ->options([
                        'pending' => __('Tertunda'),
                        'success' => __('Berhasil'),
                        'failed' => __('Gagal'),
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label(__('Setujui'))
                    ->visible(fn (Topup $record) => $record->status === \App\Enums\TopupStatus::PENDING)
                    ->color('success')
                    ->icon('heroicon-o-check')
                    ->button()
                    ->size('lg')
                    ->requiresConfirmation()
                    ->action(function (Topup $record): void {
                        DB::transaction(function () use ($record): void {
                            $user = $record->user;
                            $user->increment('balance', $record->amount);
                            $record->update([
                                'status' => 'success',
                                'paid_at' => now(),
                            ]);
                        });
                    }),
                Tables\Actions\Action::make('reject')
                    ->label(__('Tolak'))
                    ->visible(fn (Topup $record) => $record->status === \App\Enums\TopupStatus::PENDING)
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->button()
                    ->size('lg')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('notes')->label(__('Alasan Penolakan'))->required(),
                    ])
                    ->action(function (Topup $record, array $data): void {
                        $record->update([
                            'status' => 'failed',
                            'notes' => $data['notes'],
                        ]);
                    }),
                Tables\Actions\ViewAction::make()
                    ->slideOver()
                    ->button()
                    ->color('info')
                    ->size('lg'),
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->button()
                    ->color('warning')
                    ->size('lg'),
                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->color('danger')
                    ->size('lg'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageTopups::route('/'),
        ];
    }
}
