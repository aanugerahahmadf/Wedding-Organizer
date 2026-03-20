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
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Hash;

/**
 * @mixin \Eloquent
 * @property-read \App\Models\User $record
 */
class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?int $navigationSort = 1;

    public static function getNavigationGroup(): ?string
    {
        return __('Pengguna');
    }

    public static function getNavigationLabel(): string
    {
        return __('Pengguna');
    }

    public static function getModelLabel(): string
    {
        return __('Pengguna');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Pengguna');
    }

    public static function getNavigationBadge(): ?string
    {
        /** @var Builder $query */
        $query = static::$model::query();

        return (string) $query->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('Total Pengguna Terdaftar');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Detail Pengguna'))
                    ->schema([
                        Forms\Components\FileUpload::make('avatar_url')
                            ->label(__('Avatar'))
                            ->image()
                            ->avatar(),
                        Forms\Components\TextInput::make('full_name')
                            ->label(__('Nama Lengkap'))
                            ->required()
                            ->maxLength(255),

                        Forms\Components\TextInput::make('email')
                            ->label(__('Alamat Email'))
                            ->email()
                            ->required()
                            ->maxLength(255),

                        Forms\Components\DateTimePicker::make('email_verified_at')
                            ->label(__('Email Diverifikasi Pada')),

                        Forms\Components\TextInput::make('password')
                            ->label(__('Kata Sandi'))
                            ->password()
                            ->dehydrateStateUsing(fn ($state) => Hash::make($state))
                            ->dehydrated(fn ($state) => filled($state))
                            ->required(fn (string $context): bool => $context === 'create')
                            ->maxLength(255),

                        Forms\Components\TextInput::make('first_name')
                            ->label(__('Nama Depan'))
                            ->maxLength(255),

                        Forms\Components\TextInput::make('last_name')
                            ->label(__('Nama Belakang'))
                            ->maxLength(255),

                        Forms\Components\TextInput::make('username')
                            ->label(__('Username'))
                            ->maxLength(255),

                        Forms\Components\TextInput::make('phone')
                            ->label(__('Nomor Telepon'))
                            ->tel()
                            ->maxLength(255),

                        Forms\Components\Textarea::make('address')
                            ->label(__('Alamat'))
                            ->columnSpanFull(),

                        Forms\Components\Select::make('roles')
                            ->label(__('Peran'))
                            ->relationship('roles', 'name')
                            ->getOptionLabelFromRecordUsing(fn ($record) => str($record->name)->headline())
                            ->multiple()
                            ->preload()
                            ->searchable(),

                        Forms\Components\Toggle::make('active_status')
                            ->label(__('Status Aktif'))
                            ->required()
                            ->default(true)
                            ->disabled(fn (?User $record) => $record?->hasRole('super_admin') ?? false)
                            ->helperText(__('Super admin tidak dapat dinonaktifkan demi alasan keamanan.')),
                    ])->columns(['sm' => 2]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->mobileCards()
            ->columns([
                Tables\Columns\ImageColumn::make('avatar_url')
                    ->label(__('Avatar'))
                    ->circular()
                    ->alignment('center'),

                Tables\Columns\TextColumn::make('full_name')
                    ->label(__('Nama Lengkap'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('first_name')
                    ->label(__('Nama Depan'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('last_name')
                    ->label(__('Nama Belakang'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('username')
                    ->label(__('Username'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email')
                    ->label(__('Email'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('email_verified_at')
                    ->label(__('Diverifikasi Pada'))
                    ->dateTime()
                    ->sortable()
                    ->alignment('center'),

                Tables\Columns\TextColumn::make('roles.name')
                    ->label(__('Peran'))
                    ->badge()
                    ->searchable()
                    ->alignment('center')
                    ->formatStateUsing(fn ($state): string => __((string) str($state)->headline())),

                Tables\Columns\ToggleColumn::make('active_status')
                    ->label(__('Status Aktif'))
                    ->disabled(fn (?User $record) => $record?->hasRole('super_admin') ?? false)
                    ->alignment('center')
                    ->afterStateUpdated(function ($record, $state): void {
                        if (! $state) {
                            $record->tokens()->delete();
                        }
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Terdaftar Pada'))
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true)
                    ->alignment('center'),

                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Terakhir Diperbarui'))
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
                            ->title(__('Pengguna diperbarui'))
                            ->body(__('Pengguna telah berhasil diperbarui.'))
                    ),
                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->color('danger')
                    ->size('lg')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('Pengguna dihapus'))
                            ->body(__('Pengguna telah berhasil dihapus.'))
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
