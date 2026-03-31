<?php

namespace App\Filament\Admin\Resources\OrderResource\RelationManagers;

use App\Models\Payment;
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
                    ->label(__('Nomor Pembayaran'))
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('total_amount')
                    ->label(__('Total Jumlah'))
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                Forms\Components\Select::make('status')->searchable()
                    ->label(__('Status'))
                    ->options(Payment::statusLabels())
                    ->required(),
                Forms\Components\Select::make('payment_method')->searchable()
                    ->label(__('Metode Pembayaran'))
                    ->options(Payment::paymentMethodLabels())
                    ->required(),
                Forms\Components\FileUpload::make('payment_proof')
                    ->label(__('Bukti Pembayaran'))
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
                Tables\Columns\TextColumn::make('payment_number')->searchable()
                    ->label(__('Nomor Pembayaran')),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label(__('Total'))
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.')),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge(),
                Tables\Columns\TextColumn::make('payment_method')
                    ->label(__('Metode')),
                Tables\Columns\ImageColumn::make('payment_proof')
                    ->label(__('Bukti'))
                    ->openUrlInNewTab(),
                Tables\Columns\TextColumn::make('paid_at')
                    ->label(__('Dibayar Pada'))
                    ->dateTime(),
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
                    ->label(__('Verifikasi'))
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => $record->status === 'processing')
                    ->action(fn ($record) => $record->markAsSuccess()),
                Tables\Actions\Action::make('reject')
                    ->label(__('Tolak'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn ($record) => in_array($record->status, ['pending', 'processing']))
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->required()
                            ->label(__('Alasan Penolakan')),
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
