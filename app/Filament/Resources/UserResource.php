<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Support\Facades\Hash;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'User';

    protected static ?int $navigationSort = 1;

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
        return 'Total Pengguna Terdaftar';
    }

        public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('User Details')
                    ->schema([
                        Forms\Components\FileUpload::make('avatar_url')
                            ->label('Avatar Image')
                            ->image()
                            ->avatar(),
                        Forms\Components\TextInput::make('full_name')
                            ->label('Full Name')
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label('Email Address')
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label('Email Verified At'),

                        Forms\Components\TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('first_name')
                            ->label('First Name')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('last_name')
                            ->label('Last Name')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('username')
                            ->label('Username')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label('Phone Number')
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('address')
                            ->label('Address')
                            ->columnSpanFull(),

                        Forms\Components\Select::make('roles')
                            ->label('Roles')
                            ->relationship('roles', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => str($record->name)->headline())
                            ->multiple()
                            ->preload()
                            ->searchable(),

                        Forms\Components\Toggle::make('active_status')
                            ->label('Active Status')
                            ->required()
                            ->default(true)
                            ->disabled(fn ($record) => $record && $record->hasRole('super_admin'))
                            ->helperText('Super admins cannot be deactivated for security reasons.'),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->mobileCards()
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label('Avatar')
                    ->circular()
                    ->alignment('center'),

                Tables\Columns\TextColumn::make('full_name')
                    ->label('Full Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('first_name')
                    ->label('First Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_name')
                    ->label('Last Name')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('username')
                    ->label('Username')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label('Email')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label('Verified At')
                    ->dateTime()
                    ->sortable()
                    ->alignment('center'),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label('Roles')
                    ->badge()
                    ->searchable()
                    ->alignment('center')
                    ->formatStateUsing(fn ($state): string => str($state)->headline()),

                Tables\Columns\ToggleColumn::make('active_status')
                    ->label('Active Status')
                    ->searchable()
                    ->disabled(fn ($record) => $record->hasRole('super_admin'))
                    ->alignment('center')
                    ->afterStateUpdated(function ($record, $state): void {
                        if (! $state) {
                            $record->tokens()->delete();
                        }
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label('Created At')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignment('center'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
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
                        Notification::make()
                            ->success()
                            ->title('User updated')
                            ->body('The user has been updated successfully.')
                    ),
                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->color('danger')
                    ->size('lg')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('User deleted')
                            ->body('The user has been deleted successfully.')
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
            'index' => Pages\ManageUsers::route('/'),
        ];
    }
}
