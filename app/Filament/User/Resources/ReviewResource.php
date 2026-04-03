<?php

namespace App\Filament\User\Resources;

use App\Models\Review;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;

class ReviewResource extends Resource
{
    protected static ?string $model = Review::class;

    protected static ?string $navigationIcon = 'heroicon-o-star';

    public static function getGloballySearchableAttributes(): array
    {
        return ['package.name', 'comment'];
    }



    public static function getNavigationGroup(): ?string
    {
        return __('Transaksi & Aktivitas');
    }

    public static function getNavigationBadge(): ?string
    {
        return (string) static::getModel()::where('user_id', auth()->id())->count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return static::getNavigationLabel();
    }

    public static function getNavigationLabel(): string
    {
        return __('Ulasan Saya');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Pilih Layanan'))
                    ->description(__('Silahkan pilih paket yang ingin Anda beri ulasan.'))
                    ->schema([
                        Forms\Components\Select::make('package_id')
                            ->searchable()
                            ->relationship('package', 'name', fn($query) => $query->whereHas('orders', fn($q) => $q->where('user_id', auth()->id())))
                            ->required()
                            ->preload()
                            ->label(__('Layanan Paket'))
                            ->prefixIcon('heroicon-o-gift')
                            ->columnSpanFull(),
                    ]),
                Forms\Components\Section::make(__('Rating & Ceritakan Pengalaman Anda'))
                    ->schema([
                        Forms\Components\Placeholder::make('organizer_info')
                            ->label(__('Informasi Studio'))
                            ->content('Devi Make Up & Wedding'),
                        Forms\Components\Select::make('rating')
                            ->searchable()
                            ->label(__('Berikan Rating Bintang'))
                            ->options([
                                5 => '⭐⭐⭐⭐⭐ (Sangat Puas)',
                                4 => '⭐⭐⭐⭐ (Puas)',
                                3 => '⭐⭐⭐ (Cukup)',
                                2 => '⭐⭐ (Kurang)',
                                1 => '⭐ (Sangat Kurang)',
                            ])
                            ->required()
                            ->native(false)
                            ->prefixIcon('heroicon-o-star')
                            ->extraAttributes(['class' => 'text-warning-600 font-bold']),
                        Forms\Components\Textarea::make('comment')
                            ->required()
                            ->label(__('Ceritakan ulasan Anda'))
                            ->placeholder(__('Bagikan pengalaman berkesan Anda bersama paket ini...'))
                            ->rows(5)
                            ->columnSpanFull(),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('Belum ada ulasan'))
            ->emptyStateDescription(__('Bagikan pengalamanmu dengan kami!'))
            ->contentGrid([
                'md' => 1,
                'lg' => 2,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    // Header (Package info & Rating)
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('package.name')
                            ->weight(FontWeight::Bold)
                            ->size('md')
                            ->icon('heroicon-s-briefcase')
                            ->color('gray')
                            ->grow(false),
                        Tables\Columns\TextColumn::make('rating')
                            ->badge()
                            ->icon('emoji-star')
                            ->color('warning')
                            ->alignEnd(),
                    ])->extraAttributes(['class' => 'mb-2 border-b border-gray-100 dark:border-gray-800 pb-2']),

                    // Middle Box (The Review Content)
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('comment')
                            ->size('sm')

                    ])->extraAttributes(['class' => 'bg-gray-50 dark:bg-gray-900 rounded-xl p-3']),

                    // Footer (Date & Meta)
                    Tables\Columns\Layout\Split::make([
                        Tables\Columns\TextColumn::make('created_at')
                            ->date('d M Y, H:i')
                            ->size('xs')
                            ->color('gray')
                            ->icon('heroicon-o-clock'),
                    ])->extraAttributes(['class' => 'mt-2 pt-2']),

                ])->space(3)->extraAttributes(['class' => 'p-4 bg-white dark:bg-gray-950 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800']),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->label(__('Ubah'))
                    ->button()
                    ->color('warning')
                    ->size('lg')
                    ->slideOver(),
                Tables\Actions\DeleteAction::make()
                    ->label(__('Hapus'))
                    ->button()
                    ->outlined()
                    ->size('lg'),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label(__('Tulis Ulasan'))
                    ->button()
                    ->color('primary')
                    ->size('lg')
                    ->icon('heroicon-m-pencil-square')
                    ->slideOver()
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        return $data;
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\User\Resources\ReviewResource\Pages\ManageReviews::route('/'),
        ];
    }
}
