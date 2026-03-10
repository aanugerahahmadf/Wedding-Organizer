<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ReviewResource\Pages;
use App\Models\Review;
use App\Models\User;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    protected static ?string $navigationLabel = 'Ulasan Pelanggan';

    protected static ?string $navigationGroup = 'Blog & Media';

    protected static ?int $navigationSort = 6;

    protected static ?string $recordTitleAttribute = 'comment';

    public static function getGloballySearchableAttributes(): array
    {
        return ['comment'];
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
        return 'Total Ulasan Pelanggan';
    }

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Review Context')
                    ->description('Participants and services involved in this review.')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Reviewer')
                            ->options(User::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('wedding_organizer_id')
                            ->label('Target Studio')
                            ->relationship('weddingOrganizer', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\Select::make('package_id')
                            ->label('Target Package')
                            ->relationship('package', 'name')
                            ->searchable()
                            ->preload(),
                    ])->columns(3),

                Forms\Components\Section::make('Rating & Feedback')
                    ->description('User evaluation and detailed comment.')
                    ->schema([
                        Forms\Components\Select::make('rating')
                            ->label('Rating Score')
                            ->searchable()
                            ->options([
                                1 => '1 Star',
                                2 => '2 Stars',
                                3 => '3 Stars',
                                4 => '4 Stars',
                                5 => '5 Stars',
                            ])
                            ->required(),
                        Forms\Components\Textarea::make('comment')
                            ->label('Reviewer Comment')
                            ->columnSpanFull(),
                    ])->columns(1),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Reviewer')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('weddingOrganizer.name')
                    ->label('Makeup Studio')
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('package.name')
                    ->label('Package')
                    ->sortable(),
                Tables\Columns\TextColumn::make('rating')
                    ->label('Rating')
                    ->numeric()
                    ->sortable()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('comment')
                    ->label('Review Comment')
                    ->searchable()
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Date')
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
                            ->title('Review updated')
                            ->body('The review has been updated successfully.')
                    ),
                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->color('danger')
                    ->size('lg')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Review deleted')
                            ->body('The review has been deleted successfully.')
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
            'index' => Pages\ManageReviews::route('/'),
        ];
    }
}
