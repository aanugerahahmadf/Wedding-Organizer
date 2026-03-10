<?php

namespace App\Filament\Resources\OrderResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentsRelationManager extends RelationManager
{
    protected static string $relationship = 'payments';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('payment_number')
                    ->label('Nomor Pembayaran')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('total_amount')
                    ->label('Total Jumlah')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                Forms\Components\Select::make('status')
                    ->label('Status')
                    ->options(\App\Models\Payment::statusLabels())
                    ->required(),
                Forms\Components\Select::make('payment_method')
                    ->label('Metode Pembayaran')
                    ->options(\App\Models\Payment::paymentMethodLabels())
                    ->required(),
                Forms\Components\FileUpload::make('payment_proof')
                    ->label('Bukti Pembayaran')
                    ->image()
                    ->directory('payment-proofs')
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('payment_number')
            ->columns([
                Tables\Columns\TextColumn::make('payment_number')
                    ->label('Nomor Pembayaran')
                    ->searchable(),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label('Metode'),
                Tables\Columns\ImageColumn::make('payment_proof')
                    ->label('Bukti')
                    ->width(50)
                    ->height(50)
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label('Dibayar Pada')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Tables\Actions\CreateAction::make(),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\Action::make('verify')
                    ->label('Verifikasi')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'processing')
                    ->action(fn ($record) => $record->markAsSuccess()),
                Tables\Actions\Action::make('reject')
                    ->label('Tolak')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'processing']))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->required()
                            ->label('Alasan Penolakan'),
                    ])
                    ->action(fn ($record, array $data) => $record->markAsFailed($data['reason'])),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
