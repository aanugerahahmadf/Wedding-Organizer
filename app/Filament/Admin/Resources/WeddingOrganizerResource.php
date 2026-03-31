<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Resources\WeddingOrganizerResource\Pages;
use App\Models\WeddingOrganizer;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

/**
 * @mixin \Eloquent
 * @property-read \App\Models\WeddingOrganizer $record
 */
class WeddingOrganizerResource extends Resource
{
    protected static ?string $model = WeddingOrganizer::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?int $navigationSort = 7;

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    protected static ?string $recordTitleAttribute = 'name';

    public static function getModelLabel(): string
    {
        return __('Profil Studio');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Profil Studio');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name'];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Data Master');
    }

    public static function getNavigationLabel(): string
    {
        return __('Profil Devi Make Up');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make(__('Profil & Identitas Studio'))
                            ->description(__('Nama, deskripsi, dan identitas visual utama.'))
                            ->icon('govicon-building')
                            ->schema([
                                 Forms\Components\TextInput::make('name')
                                    ->label(__('Nama Studio'))
                                    ->required()
                                    ->maxLength(255)
                                    ->default('Devi Make Up & Wedding')
                                    ->dehydrated()
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(fn (Forms\Set $set, ?string $state) => $set('slug', str($state)->slug()))
                                    ->prefixIcon('heroicon-o-sparkles'),
                                Forms\Components\TextInput::make('slug')
                                    ->label(__('Slug'))
                                    ->required()
                                    ->unique(ignorable: fn (?WeddingOrganizer $record) => $record)
                                    ->maxLength(255)
                                    ->dehydrated()
                                    ->prefixIcon('heroicon-o-link'),
                                Forms\Components\RichEditor::make('description')
                                    ->label(__('Deskripsi Lengkap Studio'))
                                    ->default('Artis rias pengantin profesional yang mengkhususkan diri dalam gaya pernikahan tradisional dan modern.')
                                    ->columnSpanFull()
                                    ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList']),
                            ])->columns(2),

                        Forms\Components\Section::make(__('Visual & Galeri'))
                            ->description(__('Koleksi foto karya dan presentasi video studio.'))
                            ->icon('heroicon-o-photo')
                            ->schema([
                                Forms\Components\SpatieMediaLibraryFileUpload::make('gallery')
                                    ->label(__('Galeri Foto Portfolio'))
                                    ->collection('gallery')
                                    ->multiple()
                                    ->reorderable()
                                    ->image()
                                    ->imageEditor()
                                    ->columnSpanFull(),
                                Forms\Components\SpatieMediaLibraryFileUpload::make('videos')
                                    ->label(__('Video Profil Studio'))
                                    ->collection('videos')
                                    ->multiple()
                                    ->acceptedFileTypes(['video/mp4', 'video/webm', 'video/ogg', 'video/quicktime'])
                                    ->maxSize(102400000) // 100GB
                                    ->maxFiles(3)
                                    ->helperText(__('Upload video profil/showreel studio. Format: MP4, WebM, MOV. Maks 100MB per file.'))
                                    ->columnSpanFull(),
                            ]),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make(__('Reputasi & Kontak'))
                            ->icon('heroicon-o-star')
                            ->schema([
                                Forms\Components\TextInput::make('rating')
                                    ->label(__('Rating Studio'))
                                    ->required()
                                    ->default(0.00)
                                    ->minValue(0)
                                    ->maxValue(5)
                                    ->step(0.1)
                                    ->prefixIcon('heroicon-s-star'),
                                Forms\Components\Toggle::make('is_verified')
                                    ->label(__('Akun Terverifikasi'))
                                    ->required()
                                    ->default(true)
                                    ->onColor('success')
                                    ->onIcon('heroicon-s-check-badge')
                                    ->offIcon('heroicon-o-x-mark'),
                            ]),

                        Forms\Components\Section::make(__('Lokasi Geografis'))
                            ->icon('heroicon-o-map-pin')
                            ->description(__('Ketik alamat lalu klik di luar kotak untuk menyinkronkan titik peta secara otomatis.'))
                            ->schema([
                                Forms\Components\Textarea::make('address')
                                    ->label(__('Alamat Lengkap'))
                                    ->default('Jakarta Selatan, DKI Jakarta')
                                    ->placeholder(__('Contoh: Jakarta Selatan, DKI Jakarta'))
                                    ->helperText(__('Setelah mengisi alamat, titik peta akan otomatis berpindah ke lokasi tersebut.'))
                                    ->maxLength(255)
                                    ->rows(3)
                                    ->live(onBlur: true)
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?string $state) {
                                        if (!$state) return;

                                        try {
                                            $response = \Illuminate\Support\Facades\Http::withHeaders([
                                                'User-Agent' => 'WeddingOrganizerApp/1.0',
                                            ])->get('https://nominatim.openstreetmap.org/search', [
                                                'q'       => $state,
                                                'format'  => 'json',
                                                'limit'   => 1,
                                            ]);

                                            if ($response->successful() && $json = $response->json()) {
                                                if (isset($json[0])) {
                                                    $data = $json[0];
                                                    $lat  = (float) $data['lat'];
                                                    $lng  = (float) $data['lon'];

                                                    $set('location',  ['lat' => $lat, 'lng' => $lng]);
                                                    $set('latitude',  $lat);
                                                    $set('longitude', $lng);
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            // Fail silently
                                        }
                                    }),

                                \Dotswan\MapPicker\Fields\Map::make('location')
                                    ->label(__('Titik Koordinat Peta'))
                                    ->helperText(__('Tips: Anda bisa geser titik biru ini untuk mengisi Alamat Lengkap secara otomatis.'))
                                    ->columnSpanFull()
                                    ->extraStyles([
                                        'min-height: 450px',
                                        'z-index: 1',
                                    ])
                                    ->showMyLocationButton(false) // Hilangkan target lokasi saya
                                    ->live()
                                    ->afterStateHydrated(function (Forms\Set $set, $record) {
                                        if ($record) {
                                            $set('location', [
                                                'lat' => (float) ($record->latitude),
                                                'lng' => (float) ($record->longitude),
                                            ]);
                                        }
                                    })
                                    ->afterStateUpdated(function (Forms\Get $get, Forms\Set $set, ?array $state) {
                                        if (!$state) return;
                                        
                                        $set('latitude',  $state['lat']);
                                        $set('longitude', $state['lng']);

                                        // REVERSE GEOCODING: Geser titik -> Alamat terisi otomatis
                                        try {
                                            $response = \Illuminate\Support\Facades\Http::withHeaders([
                                                'User-Agent' => 'WeddingOrganizerApp/1.0',
                                            ])->get('https://nominatim.openstreetmap.org/reverse', [
                                                'lat'    => $state['lat'],
                                                'lon'    => $state['lng'],
                                                'format' => 'json',
                                            ]);

                                            if ($response->successful()) {
                                                $address = $response->json()['display_name'] ?? null;
                                                if ($address) {
                                                    $set('address', $address);
                                                }
                                            }
                                        } catch (\Exception $e) {
                                            // Fail silently
                                        }
                                    }),

                                Forms\Components\Hidden::make('latitude'),
                                Forms\Components\Hidden::make('longitude'),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->searchable()
                    ->label(__('Nama Studio')),
                Tables\Columns\TextColumn::make('slug')
                    ->label(__('Slug'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('address')
                    ->searchable()
                    ->label(__('Alamat'))
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('latitude')
                    ->label(__('Latitude'))

                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('longitude')
                    ->label(__('Longitude'))

                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('rating')
                    ->label(__('Rating'))
                    ->numeric()
                    ->alignment('center'),
                Tables\Columns\IconColumn::make('is_verified')
                    ->label(__('Terverifikasi'))
                    ->boolean()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Terdaftar Pada'))
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
                    ->modalWidth('5xl')
                    ->button()
                    ->color('info')
                    ->size('lg'),
                Tables\Actions\EditAction::make()
                    ->label(__('Atur Profil'))
                    ->slideOver()
                    ->modalWidth('5xl')
                    ->button()
                    ->color('warning')
                    ->size('lg')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('Studio Rias Pengantin diperbarui'))
                            ->body(__('Studio rias pengantin telah berhasil diperbarui.'))
                    ),
                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->color('danger')
                    ->size('lg')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('Studio Rias Pengantin dihapus'))
                            ->body(__('Studio rias pengantin telah berhasil dihapus.'))
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canDelete(\Illuminate\Database\Eloquent\Model $record): bool
    {
        return false;
    }

    public static function canDeleteAny(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageWeddingOrganizers::route('/'),
        ];
    }
}
