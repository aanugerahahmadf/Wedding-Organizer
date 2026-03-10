<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WithdrawalResource\Pages;
use App\Models\Withdrawal;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Str; // Added for Str::random

class WithdrawalResource extends Resource
{
    protected static ?string $model = Withdrawal::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationLabel = 'Tarik Saldo';

    protected static ?string $navigationGroup = 'Transactions';

    protected static ?int $navigationSort = 4;

    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'danger';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Total Withdrawal Requests';
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informasi Penarikan')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->relationship('user', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('reference_number')
                            ->default('WD-'.strtoupper(Str::random(10)))
                            ->required()
                            ->readOnly(),
                        Forms\Components\TextInput::make('amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp')
                            ->readOnly(fn ($record) => $record !== null),
                        Forms\Components\Select::make('status')
                            ->options([
                                'pending' => 'Pending',
                                'approved' => 'Approved',
                                'rejected' => 'Rejected',
                                'completed' => 'Completed',
                            ])
                            ->required()
                            ->default('pending'),
                    ])->columns(2),

                Forms\Components\Section::make('Tujuan Transfer')
                    ->schema([
                        Forms\Components\TextInput::make('bank_name')
                            ->required(),
                        Forms\Components\TextInput::make('account_number')
                            ->required(),
                        Forms\Components\TextInput::make('account_holder')
                            ->required(),
                    ])->columns(3),

                Forms\Components\Section::make('Catatan')
                    ->schema([
                        Forms\Components\Textarea::make('notes')
                            ->label('Catatan User')
                            ->readOnly(),
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Catatan Admin'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Pelanggan')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('reference_number')
                    ->label('Ref')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Jumlah')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('bank_name')
                    ->label('Bank')
                    ->searchable(),
                Tables\Columns\TextColumn::make('account_number')
                    ->label('Rekening'),
                Tables\Columns\TextColumn::make('account_holder')
                    ->label('Pemilik Rekening')
                    ->searchable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'approved' => 'info',
                        'completed' => 'success',
                        'rejected' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Tgl Pengajuan')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'approved' => 'Approved',
                        'completed' => 'Completed',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('approve')
                    ->label('Setujui')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Withdrawal $record) => $record->status === 'pending')
                    ->action(function (Withdrawal $record): void {
                        $record->update(['status' => 'approved']);
                    }),
                Tables\Actions\Action::make('complete')
                    ->label('Selesai')
                    ->icon('heroicon-o-check-badge')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn (Withdrawal $record) => $record->status === 'approved')
                    ->action(function (Withdrawal $record): void {
                        $record->update(['status' => 'completed']);
                    }),
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Withdrawal $record) => $record->status === 'pending')
                    ->form([
                        Forms\Components\Textarea::make('admin_notes')
                            ->label('Alasan Penolakan')
                            ->required(),
                    ])
                    ->action(function (Withdrawal $record, array $data): void {
                        // Return balance to user
                        $record->user->increment('balance', $record->amount);
                        $record->update([
                            'status' => 'rejected',
                            'admin_notes' => $data['admin_notes'],
                        ]);
                    }),
                Tables\Actions\ViewAction::make()
                    ->button()
                    ->color('info')
                    ->size('lg'),
                Tables\Actions\EditAction::make()
                    ->button()
                    ->color('warning')
                    ->size('lg')
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Withdrawal updated')
                            ->body('The withdrawal has been updated successfully.')
                    ),
                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->color('danger')
                    ->size('lg')
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Withdrawal deleted')
                            ->body('The withdrawal has been deleted successfully.')
                    ),
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
            'index' => Pages\ManageWithdrawals::route('/'),
        ];
    }
}
