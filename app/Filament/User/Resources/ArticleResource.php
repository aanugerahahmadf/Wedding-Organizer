<?php

namespace App\Filament\User\Resources;

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
    protected static ?string $model = Article::class;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    protected static ?string $slug = 'tips';

    public static function getNavigationGroup(): ?string
    {
        return __('Belanja & Jelajahi');
    }

    public static function getNavigationLabel(): string
    {
        return __('Tips & Inspiration');
    }

    protected static ?int $navigationSort = 1;

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
                'md' => 1,
                'lg' => 2,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\ImageColumn::make('image_url')
                        ->label('')
                        ->height('16rem')
                        ->width('100%')
                        ->extraImgAttributes(['class' => 'object-cover rounded-t-2xl']),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('title')
                            ->weight(FontWeight::Bold)
                            ->size('lg')
                            ->searchable()
                            ->lineClamp(2),
                        Tables\Columns\TextColumn::make('created_at')
                            ->formatStateUsing(fn($state) => 'Dipublikasikan: ' . \Carbon\Carbon::parse($state)->translatedFormat('d F Y'))
                            ->size('xs')
                            ->color('gray')
                            ->icon('heroicon-o-clock'),
                    ])->space(3)->extraAttributes(['class' => 'p-6']),
                ])->extraAttributes(['class' => 'bg-white dark:bg-gray-950 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 transition-all hover:shadow-md']),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label(__('Baca Artikel'))
                    ->button()
                    ->color('primary')
                    ->size('lg')
                    ->icon('heroicon-m-book-open')
                    ->slideOver()
                    ->modalWidth('2xl')
                    ->modalHeading(__('Membaca Artikel')),
            ]);
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
                            ->weight(\Filament\Support\Enums\FontWeight::Black)
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
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageArticles::route('/'),
        ];
    }
}
