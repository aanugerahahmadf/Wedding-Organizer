<?php

namespace App\Filament\User\Resources;

use App\Filament\User\Resources\PackageResource;
use App\Filament\User\Resources\ArticleResource\Pages;
use App\Filament\User\Resources\ArticleResource\RelationManagers;
use App\Models\Article;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ArticleResource extends Resource
{
    protected static ?string $navigationIcon = 'heroicon-o-document-text';



    public static function getNavigationGroup(): ?string
    {
        return __('Belanja & Jelajahi');
    }

    public static function getNavigationLabel(): string
    {
        return __('Tips & Inspiration');
    }


    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return static::getNavigationLabel();
    }


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('title')
                    ->label(__('Judul'))
                    ->readonly(),
                Forms\Components\RichEditor::make('content')
                    ->label(__('Konten'))
                    ->disabled()
                    ->columnSpanFull(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('Belum ada artikel'))
            ->contentGrid([
                'sm' => 2,
                'md' => 3,
                'lg' => 4,
                'xl' => 6,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    // Cover Image (Shopee product-style)
                    Tables\Columns\ImageColumn::make('image_url')
                        ->label('')
                        ->height('10rem')
                        ->width('100%')
                        ->extraAttributes(['class' => 'w-full overflow-hidden rounded-t-xl'])
                        ->extraImgAttributes([
                            'class' => 'w-full h-full object-cover transition-transform duration-500 group-hover:scale-105',
                            'style' => 'width: 100%; height: 100%; object-fit: cover;'
                        ]),

                    // Text content block
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('title')
                            ->searchable()
                            ->weight(FontWeight::Bold)
                            ->size('sm')
                            ->lineClamp(2),
                        Tables\Columns\TextColumn::make('created_at')
                            ->date('d M Y')
                            ->size('xs')
                            ->color('gray')
                            ->icon('heroicon-o-clock'),
                    ])->space(1)->extraAttributes(['class' => 'p-2']),
                ])->extraAttributes([
                    'class' => 'bg-white dark:bg-gray-900 rounded-xl shadow-sm hover:shadow-xl border border-gray-100 dark:border-gray-800 transition-all duration-300 group overflow-hidden cursor-pointer'
                ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('Baca Artikel'))
                    ->button()
                    ->color('warning')
                    ->size('lg')
                    ->icon('heroicon-m-book-open')
                    ->extraAttributes(['class' => 'w-full justify-center !rounded-lg'])
                    ->slideOver()
                    ->modalWidth('2xl')
                    ->modalHeading(__('Membaca Artikel')),
            ])
            ->actionsAlignment('center');
    }

    public static function infolist(\Filament\Infolists\Infolist $infolist): \Filament\Infolists\Infolist
    {
        return $infolist
            ->schema([
                \Filament\Infolists\Components\Section::make()
                    ->schema([
                        \Filament\Infolists\Components\ImageEntry::make('image_url')
                            ->hiddenLabel()
                            ->height('16rem')
                            ->width('100%')
                            ->extraImgAttributes(['class' => 'object-cover rounded-2xl mb-4']),
                        \Filament\Infolists\Components\TextEntry::make('title')
                            ->hiddenLabel()
                            ->weight(\Filament\Support\Enums\FontWeight::Bold)
                            ->size(\Filament\Infolists\Components\TextEntry\TextEntrySize::Large),
                        \Filament\Infolists\Components\TextEntry::make('created_at')
                            ->hiddenLabel()
                            ->date('d F Y')
                            ->icon('heroicon-o-clock')
                            ->color('gray'),
                    ])
                    ->extraAttributes(['class' => 'bg-gray-50 dark:bg-white/5 border-0 shadow-none rounded-3xl mb-4']),
                \Filament\Infolists\Components\Section::make()
                    ->schema([
                        \Filament\Infolists\Components\TextEntry::make('content')
                            ->hiddenLabel()
                            ->html()
                            ->prose()
                            ->columnSpanFull(),
                    ])
                    ->extraAttributes(['class' => 'border-0 shadow-none']),
                
                \Filament\Infolists\Components\Section::make(__('Paket Layanan Terkait'))
                    ->icon('heroicon-o-shopping-bag')
                    ->visible(fn ($record) => $record->packages()->exists())
                    ->schema([
                        \Filament\Infolists\Components\RepeatableEntry::make('packages')
                            ->hiddenLabel()
                            ->schema([
                                \Filament\Infolists\Components\TextEntry::make('name')
                                    ->weight(FontWeight::Bold)
                                    ->color('primary')
                                    ->url(fn ($record) => $record ? PackageResource::getUrl('index') . '?tableFilters[id][value]=' . $record->id : null),
                                \Filament\Infolists\Components\TextEntry::make('final_price')
                                    ->label(__('Mulai dari'))
                                    ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 2, ',', '.'))
                                    ->color('gray'),
                            ])
                            ->grid(2)
                            ->columnSpanFull()
                    ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageArticles::route('/'),
        ];
    }
}
