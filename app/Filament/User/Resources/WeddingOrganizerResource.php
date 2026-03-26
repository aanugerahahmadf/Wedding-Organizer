<?php

namespace App\Filament\User\Resources;

use App\Filament\User\Resources\WeddingOrganizerResource\Pages;
use App\Filament\User\Resources\WeddingOrganizerResource\RelationManagers;
use App\Models\WeddingOrganizer;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Table;
use Filament\Forms;
use Filament\Tables;
use Filament\Support\Enums\FontWeight;

class WeddingOrganizerResource extends Resource
{
    protected static ?string $model = WeddingOrganizer::class;

    protected static ?string $navigationIcon = 'heroicon-o-sparkles';

    protected static ?string $slug = 'about-studio';

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return static::getNavigationLabel();
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Belanja & Jelajahi');
    }

    public static function getNavigationLabel(): string
    {
        return __('About the Studio');
    }

    protected static ?int $navigationSort = 4;


    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->contentGrid([
                'md' => 1,
                'lg' => 2,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\SpatieMediaLibraryImageColumn::make('media')
                            ->collection('gallery')
                            ->label('')
                            ->square()
                            ->height('10rem')
                            ->grow(false)
                            ->extraImgAttributes(['class' => 'object-cover rounded-xl shadow-md']),
                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('name')
                                ->label(__('Nama Studio'))
                                ->weight(FontWeight::Black)
                                ->size('lg')
                                ->icon('heroicon-s-sparkles')
                                ->color('primary')
                                ->alignment('center')
                                ->extraAttributes(['class' => 'whitespace-nowrap truncate justify-center']),
                            Tables\Columns\TextColumn::make('address')
                                ->label(__('Alamat'))
                                ->icon('heroicon-m-map-pin')
                                ->color('gray')
                                ->size('xs')
                                ->alignment('center')
                                ->extraAttributes(['class' => 'whitespace-nowrap truncate justify-center']),
                        ])->space(1),
                        Tables\Columns\TextColumn::make('rating')
                            ->badge()
                            ->color('warning')
                            ->icon('heroicon-s-star'),
                    ])->extraAttributes(['class' => 'items-center gap-6']),
                ])->space(3)->extraAttributes(['class' => 'p-6 bg-white dark:bg-gray-950 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('Lihat Profil WO'))
                    ->button()
                    ->color('gray')
                    ->outlined()
                    ->size('lg')
                    ->icon('heroicon-m-eye')
                    ->slideOver()
                    ->modalWidth('2xl')
                    ->modalHeading(__('Detail Wedding Organizer')),
            ]);
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make()
                    ->schema([
                        \Filament\Infolists\Components\SpatieMediaLibraryImageEntry::make('gallery')
                            ->collection('gallery')
                            ->hiddenLabel()
                            ->height('16rem')
                            ->width('100%')
                            ->extraImgAttributes(['class' => 'object-cover rounded-2xl']),
                        \Filament\Infolists\Components\Grid::make(1)->schema([
                            \Filament\Infolists\Components\TextEntry::make('name')
                                ->hiddenLabel()
                                ->weight(\Filament\Support\Enums\FontWeight::Black)
                                ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large)
                                ->extraAttributes(['class' => 'mt-4']),
                            \Filament\Infolists\Components\TextEntry::make('rating')
                                ->hiddenLabel()
                                ->badge()
                                ->color('warning')
                                ->icon('heroicon-s-star')
                                ->inlineLabel(),
                            \Filament\Infolists\Components\TextEntry::make('address')
                                ->hiddenLabel()
                                ->icon('heroicon-m-map-pin')
                                ->color('gray'),
                        ]),
                    ])->extraAttributes(['class' => 'bg-gray-50 dark:bg-white/5 border-0 shadow-none rounded-3xl mb-4']),

                \Filament\Infolists\Components\Section::make(__('Tentang Kami'))
                    ->icon('heroicon-o-information-circle')
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('description')
                            ->hiddenLabel()
                            ->prose(),
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageWeddingOrganizers::route('/'),
        ];
    }
}
