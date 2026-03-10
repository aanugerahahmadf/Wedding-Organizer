<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TopupResource\Pages;
use App\Models\Topup;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;
use Illuminate\Support\Facades\DB;

class TopupResource extends Resource
{
    protected static ?string $model = Topup::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Transactions';

    protected static ?string $navigationLabel = 'Topup Saldo';

    protected static ?int $navigationSort = 6;

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
        return 'Total Topup Saldo';
    }

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('user_id')
                    ->relationship('user', 'full_name')
                    ->required(),
                Forms\Components\TextInput::make('reference_number')
                    ->required()
                    ->disabled()
                    ->maxLength(255),
                Forms\Components\TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                Forms\Components\TextInput::make('admin_fee')
                    ->required()
                    ->numeric()
                    ->default(0.00)
                    ->prefix('Rp'),
                Forms\Components\TextInput::make('total_amount')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                Forms\Components\TextInput::make('payment_method')
                    ->maxLength(255),
                Forms\Components\Select::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                        'cancelled' => 'Cancelled',
                    ])
                    ->required(),
                Forms\Components\FileUpload::make('payment_proof')
                    ->image()
                    ->directory('payment-proofs'),
                Forms\Components\DateTimePicker::make('paid_at'),
                Forms\Components\Textarea::make('notes')
                    ->columnSpanFull(),
            ]);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('reference_number')
                    ->searchable(),
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Nama User')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->money('IDR')
                    ->label('Total Bayar'),
                Tables\Columns\TextColumn::make('payment_method')
                    ->badge(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'success' => 'success',
                        'failed' => 'danger',
                        'cancelled' => 'gray',
                    }),
                Tables\Columns\ImageColumn::make('payment_proof')
                    ->label('Bukti'),
                Tables\Columns\TextColumn::make('paid_at')
                    ->dateTime()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'success' => 'Success',
                        'failed' => 'Failed',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->visible(fn (Topup $record) => $record->status === 'pending')
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
                    ->visible(fn (Topup $record) => $record->status === 'pending')
                    ->color('danger')
                    ->icon('heroicon-o-x-mark')
                    ->button()
                    ->size('lg')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('notes')->label('Alasan Penolakan')->required(),
                    ])
                    ->action(function (Topup $record, array $data): void {
                        $record->update([
                            'status' => 'failed',
                            'notes' => $data['notes'],
                        ]);
                    }),
                Tables\Actions\ViewAction::make()
                    ->button()
                    ->color('info')
                    ->size('lg'),
                Tables\Actions\EditAction::make()
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
