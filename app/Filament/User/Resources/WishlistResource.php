<?php

namespace App\Filament\User\Resources;

use App\Models\Wishlist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;
use App\Filament\User\Resources\PackageResource;

class WishlistResource extends Resource
{
    protected static ?string $model = Wishlist::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    public static function getGloballySearchableAttributes(): array
    {
        return ['package.name', 'package.category.name'];
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
        return __('Favorit Saya');
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()->where('user_id', auth()->id());
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Favorit Baru'))
                    ->description(__('Pilih paket yang ingin disimpan ke daftar wishlist Anda.'))
                    ->icon('heroicon-o-heart')
                    ->schema([
                        Forms\Components\Select::make('package_id')
                            ->searchable()
                            ->relationship('package', 'name')
                            ->required()
                            ->preload()
                            ->live()
                            ->prefixIcon('heroicon-o-gift')
                            ->label(__('Pilih Paket')),

                        Forms\Components\Placeholder::make('package_preview')
                            ->hiddenLabel()
                            ->content(function (Forms\Get $get) {
                                $packageId = $get('package_id');
                                if (! $packageId) return null;

                                $package = \App\Models\Package::with('category')->find($packageId);
                                if (! $package) return null;

                                $imageUrl = $package->image_url;
                                $imageHtml = $imageUrl 
                                    ? '<img src="' . $imageUrl . '" style="height: 15rem; width: 100%; object-fit: cover;" class="rounded-t-2xl">' 
                                    : '<div style="height: 15rem; width: 100%;" class="bg-gray-100 dark:bg-gray-800 rounded-t-2xl flex items-center justify-center"><svg class="w-12 h-12 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"></path></svg></div>';

                                $categoryName = $package->category ? $package->category->name : 'Uncategorized';
                                $price = 'IDR ' . number_format((float) $package->price, 2, '.', ',');

                                return new \Illuminate\Support\HtmlString('
                                    <div class="mt-4 bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden w-full max-w-sm mx-auto flex flex-col items-center">
                                        ' . $imageHtml . '
                                        <div class="p-6 flex flex-col items-center justify-center space-y-3 w-full text-center">
                                            <h3 class="font-bold text-lg text-gray-950 dark:text-white leading-tight">' . e($package->name) . '</h3>
                                            <span class="inline-flex items-center justify-center px-2 py-0.5 rounded-md text-sm font-medium ring-1 ring-inset ring-amber-600/20 text-amber-600 bg-amber-50 dark:ring-amber-500/30 dark:text-amber-500 dark:bg-amber-500/10">
                                                ' . e($categoryName) . '
                                            </span>
                                            <p class="font-bold text-md text-amber-600 dark:text-amber-500">' . $price . '</p>
                                        </div>
                                    </div>
                                ');
                            })
                            ->visible(fn (Forms\Get $get) => filled($get('package_id')))
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('Belum ada paket favorit'))
            ->emptyStateDescription(__('Temukan paket impian Anda dan simpan di sini untuk dipesan nanti.'))
            ->emptyStateIcon('heroicon-o-heart')
            ->emptyStateActions([
                Tables\Actions\Action::make('explore')
                    ->label(__('Cari Paket'))
                    ->url(PackageResource::getUrl())
                    ->button()
                    ->color('rose')
                    ->size('lg'),
            ])
            ->contentGrid([
                'sm' => 2,
                'md' => 3,
                'lg' => 4,
                'xl' => 6,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    // Image with absolute discount badge placeholder logic
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\ImageColumn::make('package.image_url')
                            ->label('')
                            ->height('10rem')
                            ->width('100%')
                            ->extraAttributes(['class' => 'w-full flex justify-center items-center bg-white p-4 rounded-t-xl overflow-hidden'])
                            ->extraImgAttributes([
                                'class' => 'object-contain transition-all duration-500 group-hover:scale-110 !mx-auto',
                                'style' => 'max-height: 100%; width: auto;'
                            ]),
                        
                        Tables\Columns\TextColumn::make('discount_pct')
                            ->state(fn($record) => ($record?->package?->discount_price > 0 && $record?->package?->price > 0) ? '-' . round((($record->package->price - $record->package->discount_price) / $record->package->price) * 100) . '%' : null)
                            ->size('xs')
                            ->weight(FontWeight::Bold)
                            ->color('danger')
                            ->extraAttributes([
                                'class' => 'absolute top-0 right-0 bg-red-100/90 dark:bg-red-900/40 px-1.5 py-0.5 rounded-bl-lg backdrop-blur-xs border-l border-b border-red-500/20',
                                'style' => 'margin: 0 !important;'
                            ])
                            ->visible(fn($record) => $record?->package?->discount_price > 0),
                    ])->extraAttributes(['class' => 'relative overflow-hidden']),

                    Tables\Columns\Layout\Stack::make([
                        // Category & Name
                        Tables\Columns\TextColumn::make('package.category.name')
                            ->searchable()
                            ->badge(),
                        Tables\Columns\TextColumn::make('package.name')
                            ->weight(FontWeight::Bold)
                            ->size('sm')
                            ->lineClamp(2)
                            ->searchable(),
                        // Price Row
                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('price_display')
                                ->state(fn ($record) => $record?->package?->discount_price > 0 ? $record->package->discount_price : $record?->package?->price)
                                ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                                ->weight(FontWeight::Bold)
                                ->color('primary')
                                ->size('md'),
                            
                            Tables\Columns\TextColumn::make('original_price')
                                ->state(fn ($record) => $record?->package?->discount_price > 0 ? $record->package->price : null)
                                ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 0, ',', '.'))
                                ->size('xs')
                                ->color('gray')
                                ->extraAttributes(['class' => 'line-through opacity-70'])
                                ->visible(fn ($record) => (bool)($record?->package?->discount_price > 0)),
                        ])->space(0),

                        // Stats Footer
                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\TextColumn::make('avg_rating')
                                ->state(fn ($record) => $record?->package ? number_format($record->package->reviews()->avg('rating') ?: 0, 1) : null)
                                ->icon('heroicon-m-star')
                                ->iconColor('warning')
                                ->size('xs')
                                ->color('gray'),
                                
                            Tables\Columns\TextColumn::make('sold_count')
                                ->state(fn ($record) => $record?->package ? $record->package->orders()->count() . ' ' . __('Terjual') : null)
                                ->size('xs')
                                ->color('gray')
                                ->alignEnd(),
                        ])->extraAttributes(['class' => 'pt-2 border-t border-gray-100 dark:border-gray-800']),

                    ])->space(2)->extraAttributes(['class' => 'p-3']),
                ])->extraAttributes([
                    'class' => 'bg-white dark:bg-gray-900 rounded-xl shadow-sm hover:shadow-xl hover:ring-1 hover:ring-primary-500/30 transition-all duration-300 overflow-hidden group border border-gray-100 dark:border-gray-800'
                ]),
            ])
            ->actions([
                Tables\Actions\DeleteAction::make()
                    ->hiddenLabel()
                    ->tooltip(__('Hapus'))
                    ->icon('heroicon-o-trash')
                    ->button()
                    ->color('danger')
                    ->outlined()
                    ->size('lg')
                    ->extraAttributes(['class' => 'flex-1 justify-center']),
                Tables\Actions\Action::make('checkout')
                    ->label(__('Beli'))
                    ->button()
                    ->color('primary')
                    ->size('lg')
                    ->icon('heroicon-m-shopping-cart')
                    ->extraAttributes(['class' => 'flex-1 justify-center'])
                    ->slideOver()
                    ->modalHeading(__('Checkout Layanan'))
                    ->steps(fn ($record) => PackageResource::getCheckoutWizardSteps($record->package))
                    ->action(function ($record, array $data) {
                        PackageResource::handleCheckout($record->package, $data);
                        
                        // Remove from wishlist
                        $record->delete();

                        // Redirect to orders
                        return redirect()->route('filament.user.resources.orders.index');
                    }),
            ])
            ->actionsAlignment('center')
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->slideOver()
                    ->label(__('Tambah Favorit'))
                    ->button()
                    ->size('lg')
                    ->color('primary')
                    ->icon('heroicon-m-plus-circle')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        return $data;
                    }),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\User\Resources\WishlistResource\Pages\ManageWishlists::route('/'),
        ];
    }
}
