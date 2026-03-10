<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BannerResource\Pages;
use App\Models\Banner;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class BannerResource extends Resource
{
    protected static ?string $model = Banner::class;

    protected static ?string $navigationIcon = 'heroicon-o-photo';

    protected static ?string $navigationLabel = 'Banner Promo';

    protected static ?string $navigationGroup = 'Blog & Media';

    protected static ?int $navigationSort = 2;

    protected static ?string $recordTitleAttribute = 'title';

    public static function getGloballySearchableAttributes(): array
    {
        return ['title'];
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
        return 'Total Banner Promo';
    }

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Banner Content')
                    ->description('Details of the promotion or highlight banner.')
                    ->schema([
                        Forms\Components\TextInput::make('title')
                            ->placeholder('Enter banner title')
                            ->maxLength(255),
                        Forms\Components\FileUpload::make('image_url')
                            ->label('Banner Image')
                            ->image()
                            ->directory('banners')
                            ->required(),
                        Forms\Components\TextInput::make('link_url')
                            ->label('Redirect URL')
                            ->placeholder('https://example.com')
                            ->maxLength(255),
                    ])->columns(2),

                Forms\Components\Section::make('Configuration')
                    ->description('Banner visibility and display order settings.')
                    ->schema([
                        Forms\Components\Toggle::make('is_active')
                            ->label('Active Status')
                            ->required(),
                        Forms\Components\TextInput::make('sort_order')
                            ->label('Display Priority')
                            ->required()
                            ->numeric()
                            ->default(0),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->mobileCards()
            ->columns([
                Tables\Columns\TextColumn::make('title')
                    ->label('Banner Title')
                    ->searchable()
                    ->sortable()
                    ->alignment('center'),
                Tables\Columns\ImageColumn::make('image_url')
                    ->label('Image Preview')
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('link_url')
                    ->label('Redirect Link')
                    ->searchable()
                    ->alignment('center'),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('Status')
                    ->alignment('center')
                    ->boolean(),
                Tables\Columns\TextColumn::make('sort_order')
                    ->label('Priority')
                    ->numeric()
                    ->sortable()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
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
                Tables\Actions\EditAction::make()
                    ->button()
                    ->color('warning')
                    ->size('lg')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Banner updated')
                            ->body('The banner has been updated successfully.')
                    ),
                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->color('danger')
                    ->size('lg')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Banner deleted')
                            ->body('The banner has been deleted successfully.')
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
            'index' => Pages\ManageBanners::route('/'),
        ];
    }
}
