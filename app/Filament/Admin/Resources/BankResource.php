<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\BankResource\Pages;
use App\Filament\Admin\Resources\BankResource\RelationManagers;
use App\Models\Bank;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class BankResource extends Resource
{
    protected static ?string $model = Bank::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-library';

    protected static ?int $navigationSort = 8;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Transaksi');
    }

    public static function getModelLabel(): string
    {
        return __('Daftar Bank & E-Wallet');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Daftar Bank & E-Wallet');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Data Bank/E-Wallet'))
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->label(__('Nama'))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->label(__('Kode'))
                            ->unique(ignoreRecord: true)
                            ->required()
                            ->maxLength(255),
                        Forms\Components\Select::make('type')
                            ->label(__('Tipe'))
                            ->searchable()
                            ->options([
                                'bank' => 'Bank',
                                'ewallet' => 'E-Wallet',
                            ])
                            ->required(),
                        Forms\Components\Toggle::make('is_active')
                            ->label(__('Aktif'))
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Nama'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('code')
                    ->label(__('Kode'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('type')
                    ->label(__('Tipe'))
                    ->badge()
                    ->color(fn ($state) => $state === 'bank' ? 'info' : 'warning'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Aktif'))
                    ->boolean(),
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
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->button()
                    ->color('warning')
                    ->size('lg')
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title(__('Bank diperbarui'))
                            ->body(__('Data bank telah berhasil diperbarui.'))
                    ),
                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->color('danger')
                    ->size('lg')
                    ->successNotification(
                        \Filament\Notifications\Notification::make()
                            ->success()
                            ->title(__('Bank dihapus'))
                            ->body(__('Data bank telah berhasil dihapus.'))
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
            'index' => Pages\ManageBanks::route('/'),
        ];
    }
}
