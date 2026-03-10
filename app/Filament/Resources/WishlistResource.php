<?php

namespace App\Filament\Resources;

use App\Filament\Resources\WishlistResource\Pages;
use App\Models\Wishlist;
use Filament\Forms;
use Filament\Resources\Resource;
use Filament\Tables;

class WishlistResource extends Resource
{
    protected static ?string $model = Wishlist::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $navigationLabel = 'Keinginan Pelanggan';

    protected static ?string $navigationGroup = 'Transactions';

    protected static ?int $navigationSort = 7;

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
        return 'Total Keinginan Pelanggan';
    }

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Wishlist Detail')
                    ->description('Informasi pelanggan dan paket rias yang diinginkan.')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Pelanggan')
                            ->relationship('user', 'full_name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('package_id')
                            ->label('Paket Rias')
                            ->relationship('package', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(\Filament\Tables\Table $table): \Filament\Tables\Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label('Pelanggan')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('package.name')
                    ->label('Paket Rias')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Ditambahkan Pada')
                    ->dateTime()
                    ->sortable()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Terakhir Diubah')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignment('center'),
            ])
            ->filters([
                //
            ])
            ->actions([
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
                            ->title('Wishlist diperbarui')
                            ->body('Data keinginan pelanggan berhasil diubah.')
                    ),
                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->color('danger')
                    ->size('lg')
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title('Wishlist dihapus')
                            ->body('Data keinginan pelanggan berhasil dihapus.')
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
            'index' => Pages\ManageWishlists::route('/'),
        ];
    }
}
