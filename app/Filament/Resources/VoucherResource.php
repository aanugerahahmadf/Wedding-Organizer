<?php

namespace App\Filament\Resources;

use App\Filament\Resources\VoucherResource\Pages;
use App\Models\Voucher;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    protected static ?string $navigationLabel = 'Voucher Promo';

    protected static ?string $navigationGroup = 'Transactions';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'code';

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
        return 'Total Voucher Promo';
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'description'];
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Voucher Details')
                    ->description('General information about the voucher.')
                    ->schema([
                        Forms\Components\TextInput::make('code')
                            ->label('Voucher Code')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(255),
                        Forms\Components\TextInput::make('description')
                            ->label('Description')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Discount Configuration')
                    ->description('Settings for the discount value.')
                    ->schema([
                        Forms\Components\TextInput::make('discount_amount')
                            ->label('Discount Amount')
                            ->required()
                            ->numeric()
                            ->prefix('Rp'),
                        Forms\Components\ToggleButtons::make('discount_type')
                            ->label('Discount Type')
                            ->options([
                                'fixed' => 'Fixed Amount (Rp)',
                                'percentage' => 'Percentage (%)',
                            ])
                            ->icons([
                                'fixed' => 'heroicon-m-currency-dollar',
                                'percentage' => 'heroicon-m-percent-badge',
                            ])
                            ->colors([
                                'fixed' => 'info',
                                'percentage' => 'success',
                            ])
                            ->default('fixed')
                            ->required()
                            ->inline()
                            ->reactive(),
                        Forms\Components\TextInput::make('min_purchase')
                            ->label('Minimum Purchase')
                            ->numeric()
                            ->prefix('Rp'),
                    ])->columns(3),

                Forms\Components\Section::make('Availability Settings')
                    ->description('Manage when the voucher is valid and its status.')
                    ->schema([
                        Forms\Components\DateTimePicker::make('expires_at')
                            ->label('Expiration Date'),
                        Forms\Components\Toggle::make('is_active')
                            ->label('Is Active')
                            ->default(true)
                            ->required(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('code')
                    ->label('Code')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('discount_amount')
                    ->label('Discount')
                    ->money('IDR')
                    ->sortable()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('discount_type')
                    ->label('Type')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'fixed' => 'info',
                        'percentage' => 'success',
                    })
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('expires_at')
                    ->label('Expires At')
                    ->dateTime()
                    ->sortable()
                    ->alignment('center'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->boolean()
                    ->searchable()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('Active Status'),
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
                        Notification::make()
                            ->success()
                            ->title('Voucher updated')
                            ->body('The voucher has been updated successfully.')
                    ),
                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->color('danger')
                    ->size('lg')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Voucher deleted')
                            ->body('The voucher has been deleted successfully.')
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
            'index' => Pages\ManageVouchers::route('/'),
        ];
    }
}
