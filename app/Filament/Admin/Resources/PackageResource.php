<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\PackageResource\Pages;
use App\Models\Package;
use App\Models\WeddingOrganizer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin \Eloquent
 * @property-read \App\Models\Package $record
 */
class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    protected static ?int $navigationSort = 3;

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('Paket');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Paket');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'theme', 'color'];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Data Master');
    }

    public static function getNavigationLabel(): string
    {
        return __('Paket Rias');
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
        return __('Total Paket Rias');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make(__('Informasi Utama'))
                            ->description(__('Penamaan dan deskripsi paket rias.'))
                            ->icon('heroicon-o-information-circle')
                            ->schema([
                                Forms\Components\TextInput::make('name')
                                    ->label(__('Nama Paket'))
                                    ->required()
                                    ->maxLength(255)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', str($state)->slug()))
                                    ->prefixIcon('heroicon-o-gift'),
                                Forms\Components\TextInput::make('slug')
                                    ->label(__('Slug'))
                                    ->required()
                                    ->unique(ignorable: fn (?Package $record) => $record)
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-link'),
                                Forms\Components\Hidden::make('wedding_organizer_id')
                                    ->default(1)
                                    ->required(),
                                Forms\Components\Select::make('category_id')
                                    ->searchable()
                                    ->label(__('Kategori Rias'))
                                    ->relationship('category', 'name')
                                    ->preload()
                                    ->prefixIcon('heroicon-o-tag')
                                    ->columnSpanFull()
                                    ->required(),
                                Forms\Components\RichEditor::make('description')
                                    ->label(__('Deskripsi Lengkap'))
                                    ->columnSpanFull()
                                    ->toolbarButtons([
                                        'bold', 'italic', 'underline', 'strike', 'link', 'h2', 'h3', 'bulletList', 'orderedList', 'redo', 'undo',
                                    ]),
                                Forms\Components\Select::make('article_id')
                                    ->searchable()
                                    ->label(__('Artikel Terkait'))
                                    ->relationship('article', 'title')
                                    ->preload()
                                    ->placeholder(__('Pilih artikel untuk menjelaskan paket ini...'))
                                    ->prefixIcon('heroicon-o-document-text')
                                    ->columnSpanFull()
                                    ->helperText(__('Pilih artikel panduan atau tips yang relevan dengan paket ini.')),
                            ])->columns(2),

                        Forms\Components\Section::make(__('Harga & Fitur'))
                            ->description(__('Informasi finansial dan fasilitas yang didapatkan.'))
                            ->icon('heroicon-o-currency-dollar')
                            ->schema([
                                Forms\Components\TextInput::make('price')
                                    ->label(__('Harga Dasar'))
                                    ->required()
                                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, ',', '.') : null)
                                    ->dehydrateStateUsing(fn ($state) => $state ? (float) str_replace(',', '.', str_replace(['Rp', '.', ' '], '', $state)) : null)
                                    ->prefix('Rp')
                                    ->extraInputAttributes(['class' => 'font-bold text-lg text-primary-600']),
                                Forms\Components\TextInput::make('discount_price')
                                    ->label(__('Harga Diskon'))
                                    ->formatStateUsing(fn ($state) => $state ? number_format((float) $state, 2, ',', '.') : null)
                                    ->dehydrateStateUsing(fn ($state) => $state ? (float) str_replace(',', '.', str_replace(['Rp', '.', ' '], '', $state)) : null)
                                    ->prefix('Rp')
                                    ->validationAttribute('price')
                                    ->rules(['nullable']),
                                Forms\Components\TagsInput::make('features')
                                    ->label(__('Fitur Paket'))
                                    ->placeholder(__('Ketik fitur lalu tekan Enter'))
                                    ->color('primary')
                                    ->columnSpanFull(),
                            ])->columns(2),

                        Forms\Components\Section::make(__('Media Portfolio'))
                            ->description(__('Upload foto utama dan video presentasi dari paket ini.'))
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Forms\Components\SpatieMediaLibraryFileUpload::make('package_image')
                                    ->label(__('Foto Utama Paket'))
                                    ->collection('package')
                                    ->image()
                                    ->imageEditor()
                                    ->formatStateUsing(fn (mixed $state): mixed => static::sanitizeSpatieUploadState($state))
                                    ->afterStateHydrated(
                                        fn (Forms\Components\SpatieMediaLibraryFileUpload $component, mixed $state): mixed => $component->state(static::sanitizeSpatieUploadState($state))
                                    )
                                    ->getUploadedFileUsing(
                                        fn (Forms\Components\SpatieMediaLibraryFileUpload $component, mixed $file): ?array => static::safeUploadedMediaFileData($component, $file)
                                    )
                                    ->maxSize(102400000) // 100GB
                                    ->columnSpanFull(),
                                Forms\Components\SpatieMediaLibraryFileUpload::make('videos')
                                    ->label(__('Video Portfolio'))
                                    ->collection('videos')
                                    ->multiple()
                                    ->formatStateUsing(fn (mixed $state): mixed => static::sanitizeSpatieUploadState($state))
                                    ->afterStateHydrated(
                                        fn (Forms\Components\SpatieMediaLibraryFileUpload $component, mixed $state): mixed => $component->state(static::sanitizeSpatieUploadState($state))
                                    )
                                    ->getUploadedFileUsing(
                                        fn (Forms\Components\SpatieMediaLibraryFileUpload $component, mixed $file): ?array => static::safeUploadedMediaFileData($component, $file)
                                    )
                                    ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'])
                                    ->maxSize(102400000) // 100GB
                                    ->maxFiles(5)
                                    ->helperText(__('Upload video portfolio paket. Format: MP4, WebM, MOV. Maks 100GB per file.'))
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make(__('Status & Klasifikasi'))
                            ->icon('heroicon-o-sparkles')
                            ->schema([
                                Forms\Components\Hidden::make('wedding_organizer_id')
                                    ->default(function () {
                                        return WeddingOrganizer::getBrand()?->id ?? WeddingOrganizer::query()->value('id');
                                    })
                                    ->dehydrateStateUsing(function () {
                                        return WeddingOrganizer::getBrand()?->id ?? WeddingOrganizer::query()->value('id');
                                    })
                                    ->required()
                                    ->dehydrated(),
                                Forms\Components\Toggle::make('is_featured')
                                    ->label(__('Paket Unggulan'))
                                    ->helperText(__('Tampilkan paket ini di halaman rekomendasi.'))
                                    ->onIcon('heroicon-s-star')
                                    ->offIcon('heroicon-o-star')
                                    ->onColor('warning'),
                            ]),

                        Forms\Components\Section::make(__('Tema & Kapasitas'))
                            ->icon('heroicon-o-users')
                            ->schema([
                                Forms\Components\TextInput::make('theme')
                                    ->label(__('Tema Visual'))
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-swatch'),
                                Forms\Components\ColorPicker::make('color')
                                    ->label(__('Warna Aksen')),
                                Forms\Components\Fieldset::make(__('Target Tamu'))
                                    ->schema([
                                        Forms\Components\TextInput::make('min_capacity')
                                            ->label(__('Minimum'))
                                            ->numeric()
                                            ->suffix('Pax'),
                                        Forms\Components\TextInput::make('max_capacity')
                                            ->label(__('Maksimum'))
                                            ->numeric()
                                            ->suffix('Pax'),
                                    ])->columns(2),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query): void {
                $brandId = WeddingOrganizer::getBrand()?->id;
                if ($brandId) {
                    $query->where('wedding_organizer_id', $brandId);
                }
            })
            ->columns([
                Tables\Columns\TextColumn::make('category.name')
                    ->searchable()
                    ->label(__('Kategori')),
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label(__('Nama Paket')),
                Tables\Columns\TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('description')
                    ->label(__('Deskripsi'))
                    ->limit(50)
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('price')
                    ->label(__('Harga Dasar'))
                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 2, ',', '.'))
                    ->alignment('right'),
                Tables\Columns\TextColumn::make('theme')
                    ->label(__('Tema'))
                    ->searchable()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('color')
                    ->label(__('Warna'))
                    ->searchable()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('min_capacity')
                    ->label(__('Min Pax'))
                    ->numeric()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('max_capacity')
                    ->label(__('Max Pax'))
                    ->numeric()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Dibuat Pada'))
                    ->dateTime()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Diperbarui Pada'))
                    ->dateTime()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
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
                        Notification::make()
                            ->success()
                            ->title(__('Paket diperbarui'))
                            ->body(__('Paket telah berhasil diperbarui.'))
                    ),
                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->color('danger')
                    ->size('lg')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('Paket dihapus'))
                            ->body(__('Paket telah berhasil dihapus.'))
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
            'index' => Pages\ManagePackages::route('/'),
        ];
    }

    private static function sanitizeSpatieUploadState(mixed $state): array
    {
        return collect(is_array($state) ? $state : [$state])
            ->filter(fn (mixed $item): bool => is_string($item) && $item !== '')
            ->mapWithKeys(fn (string $item): array => [$item => $item])
            ->all();
    }

    private static function safeUploadedMediaFileData(Forms\Components\SpatieMediaLibraryFileUpload $component, mixed $file): ?array
    {
        if (! is_string($file) || $file === '' || ! $component->getRecord()) {
            return null;
        }

        /** @var Media|null $media */
        $media = $component->getRecord()->getRelationValue('media')?->firstWhere('uuid', $file);

        if (! $media) {
            return null;
        }

        $url = $component->getConversion() && $media->hasGeneratedConversion($component->getConversion())
            ? $media->getUrl($component->getConversion())
            : $media->getUrl();

        return [
            'name' => $media->getAttributeValue('name') ?? $media->getAttributeValue('file_name'),
            'size' => $media->getAttributeValue('size'),
            'type' => $media->getAttributeValue('mime_type'),
            'url' => $url,
        ];
    }
}
