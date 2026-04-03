<?php

namespace App\Filament\User\Resources;

use App\Models\Package;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use App\Models\Order;
use App\Filament\User\Resources\ArticleResource;
use App\Filament\User\Pages\MessagesPage;
use Illuminate\Database\Eloquent\Builder;

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationIcon = 'heroicon-o-gift';

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'description', 'category.name', 'weddingOrganizer.name'];
    }

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
        return __('Service Catalog');
    }



    public static function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Tables\Actions\Action::make('visual_search')
                    ->label(__('Cari Visual Cerdas'))
                    ->icon('heroicon-o-sparkles')
                    ->color('primary')
                    ->slideOver()
                    ->modalWidth('xl')
                    ->modalHeading(__('Pencarian Visual Cerdas'))
                    ->modalDescription(__('Ubah cara Anda mencari layanan. Unggah foto atau ambil gambar langsung untuk menemukan layanan terbaik dari Devi Make Up.'))
                    ->action(fn() => null) // empty action just to allow submit to close modal and trigger table reload
                    ->modalSubmitActionLabel(__('Tampilkan di Katalog Utama'))
                    ->modalCancelActionLabel(__('Tutup'))
                    ->extraModalWindowAttributes(['class' => 'bg-gray-50/50 backdrop-blur-3xl'])
                    ->form([
                        Forms\Components\Section::make()
                            ->compact()
                            ->schema([
                                Forms\Components\TextInput::make('search_keyword')
                                    ->hiddenLabel()
                                    ->placeholder(__('Cari paket atau upload foto...'))
                                    ->prefixIcon('heroicon-m-magnifying-glass')
                                    ->prefixIconColor('gray')
                                    ->live()
                                    ->extraAttributes(['class' => 'rounded-3xl shadow-sm border-gray-200 focus:ring-primary-500'])
                                    ->suffixActions([
                                        Forms\Components\Actions\Action::make('toggle_camera_search')
                                            ->icon('heroicon-o-camera')
                                            ->color('gray')
                                            ->tooltip(__('Ambil Foto'))
                                            ->action(fn(Forms\Set $set, Forms\Get $get) => $set('show_camera', ! $get('show_camera'))),
                                        Forms\Components\Actions\Action::make('toggle_gallery_search')
                                            ->icon('heroicon-o-photo')
                                            ->color('gray')
                                            ->tooltip(__('Pilih Galeri'))
                                            ->action(fn(Forms\Set $set, Forms\Get $get) => $set('show_upload', ! $get('show_upload'))),
                                    ]),
                            ]),

                        Forms\Components\Grid::make(1)
                            ->schema([
                                \emmanpbarrameda\FilamentTakePictureField\Forms\Components\TakePicture::make('camera_image')
                                    ->hiddenLabel()
                                    ->visible(fn(Forms\Get $get) => $get('show_camera'))
                                    ->live()
                                    ->disk('public')
                                    ->directory('cbir-camera')
                                    // ->extraAttributes(['class' => 'rounded-3xl overflow-hidden ring-4 ring-primary-500/20'])
                                    ->afterStateUpdated(function ($state, Forms\Set $set, \App\Services\CBIRService $cbirService, Forms\Get $get) {
                                        if (!$state) return;
                                        $filePath = storage_path('app/public/' . $state);
                                        if (!file_exists($filePath)) return;
                                        $file = new \Symfony\Component\HttpFoundation\File\File($filePath);
                                        $results = $cbirService->searchByImage($file, 20);
                                        
                                        $scores = collect($results)->filter(fn($item) => ($item['score'] ?? 0) >= 0.4)->pluck('score', 'owner_id')->all();
                                            if (!empty($scores)) {
                                                $topScore = static::formatSimilarityPct($results[0]['score']);
                                                session()->put('cbir_results_with_scores', $scores);
                                                session()->put('cbir_results', array_keys($scores));
                                                $set('status_message', __('Hasil ditemukan! Akurasi tertinggi :score%', ['score' => $topScore]));
                                                $livewire->dispatch('refresh_catalog'); // Sycn background table instantly
                                            }
                                        }),

                                Forms\Components\FileUpload::make('search_image')
                                    ->hiddenLabel()
                                    ->image()
                                    ->imageEditor()
                                    ->visible(fn(Forms\Get $get) => $get('show_upload'))
                                    ->directory('cbir-queries')
                                    ->live()
                                    // ->extraAttributes(['class' => 'rounded-3xl border-2 border-dashed border-primary-200 bg-primary-50/30'])
                                    ->afterStateUpdated(function (\Livewire\Component $livewire, $state, Forms\Set $set, \App\Services\CBIRService $cbirService, Forms\Get $get) {
                                        if (!$state) return;
                                        
                                        $fileObj = is_array($state) ? reset($state) : $state;
                                        if ($fileObj instanceof \Livewire\Features\SupportFileUploads\TemporaryUploadedFile) {
                                            $filePath = $fileObj->getRealPath();
                                        } else {
                                            $filePath = storage_path('app/public/' . $fileObj);
                                        }

                                        if (!file_exists($filePath)) {
                                            $set('status_message', __('Gagal membaca file upload. Silakan coba lagi.'));
                                            return;
                                        }
                                        
                                        $file = new \Symfony\Component\HttpFoundation\File\File($filePath);
                                        $results = $cbirService->searchByImage($file, 20);
                                        
                                        if (isset($results['error'])) {
                                            $set('status_message', $results['message'] ?? __('Server AI Offline.'));
                                            return;
                                        }

                                        // Threshold diturunkan ke 0.05 (5%) agar AI selalu menampilkan kembaran terdekat (ala Google Lens)
                                        $scores = collect($results)->filter(fn($item) => ($item['score'] ?? 0) >= 0.05)->pluck('score', 'owner_id')->all();
                                        if (!empty($scores)) {
                                            $topScore = static::formatSimilarityPct($results[0]['score']);
                                            session()->put('cbir_results_with_scores', $scores);
                                            session()->put('cbir_results', array_keys($scores));
                                            $set('status_message', __('Berhasil menemukan :count layanan! Akurasi kemiripan gambar: :score%', ['count' => count($scores), 'score' => $topScore]));
                                            $livewire->dispatch('refresh_catalog'); // Sync background table instantly
                                        } else {
                                            session()->forget(['cbir_results_with_scores', 'cbir_results']);
                                            $set('status_message', __('Tidak ada paket yang cocok di database. Image tidak dikenali sistem.'));
                                            $livewire->dispatch('refresh_catalog'); // Sync background table instantly
                                        }
                                    }),

                                Forms\Components\Placeholder::make('status_message')
                                    ->label('')
                                    ->content(fn(Forms\Get $get) => new \Illuminate\Support\HtmlString(
                                        '<div class="text-sm">' . e($get('status_message')) . '</div>'
                                    ))
                                    ->visible(fn(Forms\Get $get) => (bool) $get('status_message'))
                                    ->extraAttributes(['class' => 'text-center p-3 bg-gray-900/80 dark:bg-gray-800 rounded-xl text-white font-medium shadow-md']),

                                // ── CBIR Results ── Fully Filament-native components
                                Forms\Components\Group::make()
                                    ->schema(function () {
                                        $raw = session('cbir_results_with_scores', []);
                                        if (empty($raw)) return [];

                                        $packages = \App\Models\Package::whereIn('id', array_keys($raw))
                                            ->with(['category', 'weddingOrganizer'])
                                            ->get()
                                            ->map(fn($p) => tap($p, fn($p) => $p->similarity_score = $raw[$p->id] ?? 0))
                                            ->sortByDesc('similarity_score')
                                            ->take(8);

                                        $topScore = static::formatSimilarityPct($packages->first()?->similarity_score ?? 0);
                                        $components = [];

                                        // ── Header ──────────────────────────────────
                                        $components[] = Forms\Components\Section::make()
                                            ->compact()
                                            ->schema([
                                                Forms\Components\Placeholder::make('cbir_header_label')
                                                    ->hiddenLabel()
                                                    ->content(new \Illuminate\Support\HtmlString(
                                                        '<div class="flex items-center gap-1.5">'
                                                        . svg('heroicon-o-sparkles', 'w-4 h-4 text-primary-500')->toHtml()
                                                        . '<span class="font-bold">' . __('Hasil Mirip') . '</span>'
                                                        . '</div>'
                                                    )),
                                                Forms\Components\Placeholder::make('cbir_header_count')
                                                    ->label(__('Paket Ditemukan'))
                                                    ->content($packages->count() . ' ' . __('paket')),
                                                ...($topScore >= 70 ? [
                                                    Forms\Components\Placeholder::make('cbir_header_top')
                                                        ->label(__('Kecocokan Tertinggi'))
                                                        ->content($topScore . '% ' . __('akurasi visual')),
                                                ] : []),
                                            ])
                                            ->columns(3)
                                            ->extraAttributes(['class' => 'bg-gray-50 dark:bg-gray-900 mb-2']);

                                        // ── One Section per package ─────────────────
                                        foreach ($packages as $pkg) {
                                            $pct   = static::formatSimilarityPct($pkg->similarity_score);
                                            $color = $pkg->similarity_score >= 0.85 ? 'success' : ($pkg->similarity_score >= 0.65 ? 'warning' : 'gray');
                                            $price = 'Rp ' . number_format($pkg->price, 2, ',', '.');
                                            $wo    = $pkg->weddingOrganizer?->name ?? '';
                                            $cat   = $pkg->category?->name ?? '';

                                            $components[] = Forms\Components\Section::make()
                                                ->compact()
                                                ->schema([
                                                    // ── Image + Info ──
                                                    Forms\Components\Split::make([
                                                        // Thumbnail (hanya img, tidak ada Filament component untuk ini)
                                                        Forms\Components\Placeholder::make('img_' . $pkg->id)
                                                            ->hiddenLabel()
                                                            ->grow(false)
                                                            ->content(function () use ($pkg) {
                                                                $src = str_starts_with($pkg->image_url, 'http') ? $pkg->image_url : asset('storage/' . $pkg->image_url);
                                                                if (!$pkg->image_url) $src = asset('images/placeholder.png');
                                                                return new \Illuminate\Support\HtmlString(
                                                                    '<img src="' . $src . '" alt="' . e($pkg->name) . '" class="w-16 h-16 rounded-xl object-cover shadow-md" />'
                                                                );
                                                            }),
                                                            
                                                        // Meta info — native Filament Placeholders
                                                        Forms\Components\Group::make([
                                                            Forms\Components\Placeholder::make('cat_' . $pkg->id)
                                                                ->label(__('Kategori'))
                                                                ->content($cat ?: '-'),

                                                            Forms\Components\Placeholder::make('name_' . $pkg->id)
                                                                ->label(__('Paket'))
                                                                ->content($pkg->name),

                                                            ...($wo ? [
                                                                Forms\Components\Placeholder::make('wo_' . $pkg->id)
                                                                    ->label(__('Wedding Organizer'))
                                                                    ->content(new \Illuminate\Support\HtmlString(
                                                                        '<div class="flex items-center gap-1.5 text-gray-500 dark:text-gray-400">'
                                                                        . svg('govicon-building', 'w-7 h-7')->toHtml()
                                                                        . '<span class="text-sm font-medium">' . e($wo) . '</span>'
                                                                        . '</div>'
                                                                    )),
                                                            ] : []),

                                                            Forms\Components\Placeholder::make('price_' . $pkg->id)
                                                                ->label(__('Harga'))
                                                                ->content($price),
                                                        ])->columns(2),
                                                    ]),

                                                    // ── Similarity Score — native Placeholder + badge ──
                                                    Forms\Components\Placeholder::make('score_label_' . $pkg->id)
                                                        ->label(__('Kesamaan Visual'))
                                                        ->content($pct . '% ' . __('MIRIP')),

                                                    // Progress bar (inline style width — tidak bisa dihindari)
                                                    Forms\Components\Placeholder::make('score_bar_' . $pkg->id)
                                                        ->hiddenLabel()
                                                        ->content(new \Illuminate\Support\HtmlString(
                                                            '<div class="flex items-center gap-2">'
                                                            . '<div class="flex-1 h-1.5 bg-gray-100 dark:bg-gray-800 rounded-full overflow-hidden">'
                                                            . '<div class="h-full rounded-full '
                                                                . ($pkg->similarity_score >= 0.85 ? 'bg-emerald-500' : ($pkg->similarity_score >= 0.65 ? 'bg-amber-500' : 'bg-gray-400'))
                                                                . ' transition-all duration-700" style="width:' . $pct . '%"></div>'
                                                            . '</div>'
                                                            . '</div>'
                                                        )),

                                                    // ── Action buttons ──
                                                    Forms\Components\Actions::make([
                                                        Forms\Components\Actions\Action::make('wishlist_' . $pkg->id)
                                                            ->label(__('Favorit'))
                                                            ->icon('heroicon-o-heart')
                                                            ->color('danger')
                                                            ->outlined()
                                                            ->size('sm')
                                                            ->action(function () use ($pkg) {
                                                                \App\Models\Wishlist::updateOrCreate([
                                                                    'user_id'    => auth()->id(),
                                                                    'package_id' => $pkg->id,
                                                                ]);
                                                                \Filament\Notifications\Notification::make()
                                                                    ->title(__('Disimpan ke Favorit'))
                                                                    ->success()
                                                                    ->send();
                                                            }),
                                                        Forms\Components\Actions\Action::make('order_' . $pkg->id)
                                                            ->label(__('Beli'))
                                                            ->button()
                                                            ->color('primary')
                                                            ->size('lg')
                                                            ->icon('heroicon-m-shopping-cart')
                                                            ->extraAttributes(['class' => 'h-12 flex-1 whitespace-nowrap'])
                                                            ->slideOver()
                                                            ->modalWidth('2xl')
                                                            ->modalHeading(__('Checkout Layanan'))
                                                            ->steps(static::getCheckoutWizardSteps($pkg))
                                                            ->action(fn (array $data) => static::handleCheckout($pkg, $data)),
                                                    ])->fullWidth(),
                                                ])
                                                ->extraAttributes(['class' => 'ring-1 ring-gray-200 dark:ring-gray-700 rounded-2xl mb-2']);
                                        }

                                        // ── Show all button ──────────────────────────
                                        $components[] = Forms\Components\Actions::make([
                                            Forms\Components\Actions\Action::make('show_all_results')
                                                ->label(__('Tampilkan Semua di Katalog'))
                                                ->icon('heroicon-m-squares-2x2')
                                                ->color('primary')
                                                ->size('lg')
                                                ->button()
                                                ->extraAttributes(['class' => 'w-full'])
                                                ->action(function ($livewire) {
                                                    session()->forget(['cbir_results', 'cbir_results_with_scores']);
                                                    // Use redirect to clear all UI states and show full catalog accurately
                                                    return redirect()->to(static::getUrl('index'));
                                                }),
                                        ])->fullWidth();

                                        return $components;
                                    })
                                    ->visible(fn() => session()->has('cbir_results')),
                            ]),
                    ]),
                Tables\Actions\Action::make('clear_visual_search')
                    ->label(__('Reset'))
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->action(fn() => session()->forget('cbir_results'))
                    ->visible(fn() => session()->has('cbir_results')),
            ])
            ->emptyStateHeading(__('Belum ada paket tersedia'))
            ->emptyStateDescription(function() {
                if (session()->has('cbir_results')) {
                    return new \Illuminate\Support\HtmlString((string)__('Tidak ada paket yang cocok dengan foto Anda. Silakan coba foto lain.'));
                }
                return new \Illuminate\Support\HtmlString((string)__('Temukan paket impianmu di sini!'));
            })
            ->emptyStateActions([
                Tables\Actions\Action::make('reset_search')
                    ->label(__('Tampilkan Semua'))
                    ->action(fn() => session()->forget('cbir_results'))
                    ->visible(fn() => session()->has('cbir_results')),
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
                        Tables\Columns\ImageColumn::make('image_url')
                            ->label('')
                            ->height('200px')
                            ->width('100%')
                            ->url(fn ($record) => static::getUrl('view', ['record' => $record]))
                            ->extraAttributes(['class' => 'w-full flex justify-center items-center bg-gray-50 dark:bg-gray-800 rounded-t-2xl overflow-hidden'])
                            ->extraImgAttributes([
                                'class' => 'aspect-square object-contain transition-all duration-500 group-hover:scale-110 !mx-auto',
                                'style' => 'height: 200px; width: 100%;'
                            ]),
                        
                        // Discount Badge overlay (top right)
                        Tables\Columns\TextColumn::make('discount_pct')
                            ->state(fn($record) => $record?->discount_price > 0 ? '-' . round((($record->price - $record->discount_price) / $record->price) * 100) . '%' : null)
                            ->size('xs')
                            ->weight(FontWeight::ExtraBold)
                            ->color('danger')
                            ->extraAttributes([
                                'class' => 'absolute top-0 right-0 bg-red-500 text-white px-2 py-1 rounded-bl-xl shadow-sm z-10',
                                'style' => 'margin: 0 !important; font-size: 0.75rem;'
                            ])
                            ->visible(fn($record) => $record?->discount_price > 0),
                    ])->extraAttributes(['class' => 'relative overflow-hidden']),

                    Tables\Columns\Layout\Stack::make([
                        // Category & Name
                        Tables\Columns\TextColumn::make('category.name')
                            ->badge(),
                            
                        Tables\Columns\TextColumn::make('name')
                            ->weight(FontWeight::Bold)
                            ->size('sm')
                            ->lineClamp(2)
                            ->url(fn ($record) => static::getUrl('view', ['record' => $record])),

                        // Price Row
                        Tables\Columns\Layout\Stack::make([
                            Tables\Columns\TextColumn::make('price_display')
                                ->state(fn ($record) => $record?->discount_price > 0 ? $record->discount_price : $record?->price)
                                ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 2, ',', '.') : '')
                                ->weight(FontWeight::Bold)
                                ->color('primary')
                                ->size('md'),
                            
                            Tables\Columns\TextColumn::make('original_price')
                                ->state(fn ($record) => $record?->discount_price > 0 ? $record->price : null)
                                ->formatStateUsing(fn ($state) => $state ? 'Rp ' . number_format($state, 2, ',', '.') : '')
                                ->size('xs')
                                ->color('gray')
                                ->extraAttributes(['class' => 'line-through opacity-70'])
                                ->visible(fn ($record) => (bool)($record?->discount_price > 0)),
                        ])->space(0),

                        // Stats Footer
                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\TextColumn::make('avg_rating')
                                ->state(fn ($record) => $record ? number_format($record->reviews()->avg('rating') ?: 0, 1) : null)
                                ->icon('heroicon-m-star')
                                ->iconColor('warning')
                                ->size('xs')
                                ->color('gray'),
                                
                            Tables\Columns\TextColumn::make('sold_count')
                                ->state(fn ($record) => $record ? $record->orders()->count() . ' ' . __('Terjual') : null)
                                ->size('xs')
                                ->color('gray')
                                ->alignEnd(),
                        ])->extraAttributes(['class' => 'pt-2 border-t border-gray-100 dark:border-gray-800']),

                    ])->space(2)->extraAttributes(['class' => 'p-3']),
                ])->extraAttributes([
                    'class' => 'bg-white dark:bg-gray-900 rounded-2xl shadow-sm hover:shadow-xl transition-all duration-300 overflow-hidden group border border-gray-100 dark:border-gray-800'
                ]),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->searchable()
                    ->label(__('Kategori'))
                    ->relationship('category', 'name')
                    ->native(false)
                    ->preload(),
                Tables\Filters\Filter::make('sort_by')
                    ->form([
                        Forms\Components\Select::make('sort_by')
                            ->label(__('Urutkan'))
                            ->options([
                                'latest' => __('Terbaru'),
                                'price_asc' => __('Harga: Terendah'),
                                'price_desc' => __('Harga: Tertinggi'),
                            ])
                            ->searchable()
                            ->native(false),
                    ])
                    ->query(function (Builder $query, array $data) {
                        if (! ($data['sort_by'] ?? null)) {
                            return $query;
                        }
                 
                        return match ($data['sort_by']) {
                            'price_asc' => $query->reorder('price', 'asc'),
                            'price_desc' => $query->reorder('price', 'desc'),
                            'latest' => $query->reorder('created_at', 'desc'),
                            default => $query,
                        };
                    })
                    ->indicateUsing(function (array $data): ?string {
                        if (! ($data['sort_by'] ?? null)) {
                            return null;
                        }
                 
                        return __('Urutan') . ': ' . match ($data['sort_by']) {
                            'price_asc' => __('Harga Terendah'),
                            'price_desc' => __('Harga Tertinggi'),
                            'latest' => __('Terbaru'),
                            default => null,
                        };
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('view_detail')
                    ->label(__('Lihat Detail'))
                    ->button()
                    ->size('lg')
                    ->extraAttributes(['class' => 'w-full justify-center rounded-xl'])
                    ->url(fn ($record) => static::getUrl('view', ['record' => $record])) 
            ])
            ->actionsAlignment('center')
            ->defaultSort('created_at', 'desc')
            ->persistSortInSession()
            ->persistFiltersInSession();
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\Grid::make(12)
                            ->schema([
                                // LEFT: PRODUCT IMAGE
                                \Filament\Infolists\Components\Group::make([
                                    Infolists\Components\ImageEntry::make('image_url')
                                        ->label('')
                                        ->hiddenLabel()
                                        ->alignCenter()
                                        ->height('22rem')
                                        ->extraAttributes(['class' => 'flex items-center justify-center bg-white/5 rounded-3xl overflow-hidden border border-white/10 shadow-inner'])
                                        ->extraImgAttributes([
                                            'class' => 'max-w-full max-h-full object-contain mx-auto transition-transform hover:scale-105 duration-500 p-2',
                                        ]),
                                ])->columnSpan([
                                    'default' => 12,
                                    'md' => 5,
                                ]),

                                // RIGHT: PRODUCT IDENTITY
                                \Filament\Infolists\Components\Group::make([
                                    // CATEGORY BADGE
                                    Infolists\Components\TextEntry::make('category.name')
                                        ->label('')
                                        ->badge()
                                        ->color('info')
                                        ->icon('heroicon-m-tag')
                                        ->extraAttributes(['class' => 'mb-2']),

                                    // PKG NAME
                                    Infolists\Components\TextEntry::make('name')
                                        ->label('')
                                        ->hiddenLabel()
                                        ->weight(FontWeight::Black)
                                        ->size('4xl')
                                        ->extraAttributes(['class' => 'tracking-tight text-transparent bg-clip-text bg-gradient-to-r from-primary-600 to-primary-400 mb-4 uppercase leading-tight']),

                                    // PRICE DISPLAY
                                    \Filament\Infolists\Components\Group::make([
                                        Infolists\Components\TextEntry::make('final_price')
                                            ->label('')
                                            ->formatStateUsing(fn ($state) => 'Rp ' . number_format($state, 2, ',', '.'))
                                            ->size('4xl')
                                            ->weight(FontWeight::Black)
                                            ->color('success')
                                            ->extraAttributes(['class' => 'drop-shadow-sm']),
                                        
                                        Infolists\Components\TextEntry::make('price')
                                            ->label('')
                                            ->formatStateUsing(fn ($record) => $record->discount_price > 0 ? 'Rp ' . number_format($record->price, 2, ',', '.') : '')
                                            ->size('lg')
                                            ->color('gray')
                                            ->extraAttributes(['class' => 'line-through opacity-50 ml-4'])
                                            ->visible(fn ($record) => $record->discount_price > 0),
                                    ])->extraAttributes(['class' => 'flex items-baseline mb-6']),

                                    // DESCRIPTION
                                    Infolists\Components\Section::make(__('Tentang Layanan Ini'))
                                        ->compact()
                                        ->schema([
                                            Infolists\Components\TextEntry::make('description')
                                                ->label('')
                                                ->html()
                                                ->prose()
                                                ->extraAttributes(['class' => 'text-gray-600 dark:text-gray-300 leading-relaxed text-lg']),
                                        ])->icon('heroicon-o-document-text')->iconColor('primary'),

                                    // PRIMARY CTA: BUY
                                    \Filament\Infolists\Components\Actions::make([
                                        \Filament\Infolists\Components\Actions\Action::make('buy_now_detail')
                                            ->label(__('Pesan Paket Sekarang'))
                                            ->icon('heroicon-m-shopping-cart')
                                            ->button()
                                            ->color('success')
                                            ->size(\Filament\Support\Enums\ActionSize::Large)
                                            ->extraAttributes(['class' => 'w-full py-4 text-xl rounded-2xl shadow-xl shadow-emerald-500/20 hover:scale-[1.01] transition-all'])
                                            ->slideOver()
                                            ->modalWidth('2xl')
                                            ->modalHeading(__('Checkout Layanan'))
                                            ->steps(fn ($record) => static::getCheckoutWizardSteps($record))
                                            ->action(function ($record, array $data) {
                                                static::handleCheckout($record, $data);
                                                return redirect()->route('filament.user.resources.orders.index');
                                            }),
                                    ])->fullWidth(),

                                    // SECONDARY: CHAT & WISHLIST
                                    \Filament\Infolists\Components\Actions::make([
                                        \Filament\Infolists\Components\Actions\Action::make('chat_admin')
                                            ->label(__('Chat Admin'))
                                            ->icon('heroicon-m-chat-bubble-left-right')
                                            ->button()
                                            ->color('info')
                                            ->outlined()
                                            ->extraAttributes(['class' => 'flex-1 rounded-xl py-3'])
                                            ->url(fn() => MessagesPage::getUrl()),

                                        \Filament\Infolists\Components\Actions\Action::make('wishlist_detail')
                                            ->label(__('Favorit'))
                                            ->icon('heroicon-m-heart')
                                            ->button()
                                            ->color('danger')
                                            ->outlined()
                                            ->extraAttributes(['class' => 'flex-1 rounded-xl py-3'])
                                            ->action(function ($record) {
                                                \App\Models\Wishlist::updateOrCreate([
                                                    'user_id' => auth()->id(),
                                                    'package_id' => $record->id,
                                                ]);
                                                Notification::make()
                                                    ->title(__('Disimpan ke Favorit'))
                                                    ->success()
                                                    ->icon('heroicon-o-heart')
                                                    ->iconColor('danger')
                                                    ->send();
                                            }),
                                    ])->fullWidth()->extraAttributes(['class' => 'mt-4']),
                                ])->columnSpan([
                                    'default' => 12,
                                    'md' => 7,
                                ]),
                            ])
                        ->extraAttributes(['class' => 'gap-10 p-2']),
                    ])
                    ->extraAttributes(['class' => 'border-none bg-transparent shadow-none']),

                // RELATED ARTICLE (WISDOM & TIPS)
                Infolists\Components\Section::make(__('Wawasan & Tips Terkait'))
                    ->icon('heroicon-o-book-open')
                    ->iconColor('info')
                    ->visible(fn ($record) => $record->article_id !== null)
                    ->schema([
                        Infolists\Components\TextEntry::make('article.title')
                            ->label(__('Judul Artikel'))
                            ->weight(FontWeight::Bold)
                            ->color('info')
                            ->size('lg')
                            ->url(fn ($record) => $record->article_id ? ArticleResource::getUrl('index') . '?tableFilters[id][value]=' . $record->article_id : null),
                        Infolists\Components\TextEntry::make('article.excerpt')
                            ->label('')
                            ->prose()
                            ->extraAttributes(['class' => 'italic opacity-80 mt-2']),
                    ])->compact(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\User\Resources\PackageResource\Pages\ManagePackages::route('/'),
            'view' => \App\Filament\User\Resources\PackageResource\Pages\ViewPackage::route('/{record}'),
        ];
    }

    public static function getCheckoutWizardSteps(\App\Models\Package $package): array
    {
        return [
            Forms\Components\Wizard\Step::make(__('Detail Acara'))
                ->icon('heroicon-o-calendar-days')
                ->schema([
                    Forms\Components\Section::make(__('Pilih Waktu & Kebutuhan'))
                        ->schema([
                            Forms\Components\DatePicker::make('booking_date')
                                ->label(__('Rencana Tanggal Acara'))
                                ->required()
                                ->native(false)
                                ->minDate(now()->addDays(7))
                                ->prefixIcon('heroicon-o-calendar-days')
                                ->columnSpanFull(),
                            Forms\Components\Textarea::make('notes')
                                ->label(__('Catatan Khusus / Alamat Lokasi'))
                                ->placeholder(__('Contoh: Gedung Graha Sabha, dekorasi dominan warna Pastel...'))
                                ->rows(4)
                                ->required()
                                ->columnSpanFull(),
                        ]),
                ]),
            Forms\Components\Wizard\Step::make(__('Info Kontak'))
                ->icon('heroicon-o-user-circle')
                ->schema([
                    Forms\Components\Section::make(__('Verifikasi Data Anda'))
                        ->schema([
                            Forms\Components\TextInput::make('customer_name')
                                ->label(__('Nama Lengkap'))
                                ->default(fn() => auth()->user()->full_name)
                                ->disabled(),
                            Forms\Components\TextInput::make('phone')
                                ->label(__('Nomor WhatsApp'))
                                ->tel()
                                ->default(fn() => auth()->user()->phone)
                                ->required(),
                        ])->columns(2),
                ]),
            Forms\Components\Wizard\Step::make(__('Voucher & Diskon'))
                ->icon('heroicon-o-ticket')
                ->schema([
                    Forms\Components\Section::make(__('Pilih Voucher Anda'))
                        ->description(__('Gunakan voucher yang telah Anda klaim di menu Voucher.'))
                        ->icon('heroicon-o-ticket')
                        ->schema([
                            Forms\Components\Select::make('voucher_id')
                                ->searchable()
                                ->label(__('Voucher Tersedia'))
                                ->placeholder(__('Pilih voucher untuk mendapatkan diskon'))
                                ->prefixIcon('heroicon-o-ticket')
                                ->options(function () use ($package) {
                                    $user = auth()->user();
                                    $vouchers = \App\Models\Voucher::where('is_active', true)
                                        ->where(fn ($q) => $q->whereNull('expires_at')->orWhere('expires_at', '>', now()))
                                        ->whereHas('users', fn ($q) => $q->where('users.id', $user->id)->whereNull('user_vouchers.used_at'))
                                        ->get()
                                        ->filter(fn (\App\Models\Voucher $v) => $v->isValidFor($package->final_price));

                                    return $vouchers->mapWithKeys(function (\App\Models\Voucher $v) {
                                        $amount = $v->discount_type === \App\Enums\DiscountType::PERCENTAGE 
                                            ? number_format($v->discount_amount, 2, ',', '.') . '%' 
                                            : 'Rp ' . number_format($v->discount_amount, 2, ',', '.');
                                        return [$v->id => $v->code . ' - Diskon ' . $amount];
                                    });
                                })
                                ->searchable()
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get, ?string $state) use ($package) {
                                    if (! $state) {
                                        $set('voucher_discount', 0);
                                        $set('_voucher_info', null);
                                        return;
                                    }
                                    $voucher = \App\Models\Voucher::find($state);
                                    if ($voucher && $voucher->isValidFor($package->final_price)) {
                                        $discount = $voucher->calculateDiscount($package->final_price);
                                        $set('voucher_discount', $discount);
                                        $set('_voucher_info', 'valid:' . $voucher->id . ':' . $discount . ':' . $voucher->description);
                                    } else {
                                        $set('voucher_id', null);
                                        $set('voucher_discount', 0);
                                        $set('_voucher_info', 'invalid');
                                    }
                                })
                                ->hint(fn (Forms\Get $get) => match(true) {
                                    str_starts_with((string)$get('_voucher_info'), 'valid:') => __('Voucher Berhasil Dipasang!'),
                                    $get('_voucher_info') === 'invalid' => __('Voucher tidak valid'),
                                    default => null,
                                })
                                ->hintIcon(fn (Forms\Get $get) => match(true) {
                                    str_starts_with((string)$get('_voucher_info'), 'valid:') => 'heroicon-m-check-circle',
                                    $get('_voucher_info') === 'invalid' => 'heroicon-m-x-circle',
                                    default => null,
                                })
                                ->hintColor(fn (Forms\Get $get) => str_starts_with((string)$get('_voucher_info'), 'valid:') ? 'success' : 'danger')
                                ->helperText(__('Hanya voucher yang memenuhi syarat minimum belanja yang akan muncul di sini. Jika kosong, silakan ke menu Voucher untuk Klaim.')),

                            Forms\Components\Hidden::make('voucher_discount')->default(0),
                            Forms\Components\Hidden::make('_voucher_info'),

                            Forms\Components\Placeholder::make('_discount_preview')
                                ->hiddenLabel()
                                ->visible(fn (Forms\Get $get) => str_starts_with((string)$get('_voucher_info'), 'valid:'))
                                ->content(function (Forms\Get $get) use ($package) {
                                    $discount = (float) $get('voucher_discount');
                                    $final = $package->final_price - $discount;
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="flex flex-col gap-2 p-4 bg-success-50 dark:bg-success-950 rounded-xl border border-success-200 dark:border-success-800">' .
                                            '<div class="flex justify-between text-sm">' .
                                                '<span class="text-gray-600 dark:text-gray-400">' . __('Harga Paket') . '</span>' .
                                                '<span class="font-semibold">Rp ' . number_format($package->final_price, 2, ',', '.') . '</span>' .
                                            '</div>' .
                                            '<div class="flex justify-between text-sm text-success-600 dark:text-success-400">' .
                                                '<span class="flex items-center gap-1"><svg xmlns="http://www.w3.org/2000/svg" class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" d="M16.5 6v.75m0 3v.75m0 3v.75m0 3V18m-9-5.25h5.25M7.5 15h3M3.375 5.25c-.621 0-1.125.504-1.125 1.125v3.026a2.999 2.999 0 0 1 0 5.198v3.026c0 .621.504 1.125 1.125 1.125h17.25c.621 0 1.125-.504 1.125-1.125v-3.026a2.999 2.999 0 0 1 0-5.198V6.375c0-.621-.504-1.125-1.125-1.125H3.375Z" /></svg> ' . __('Diskon Voucher') . '</span>' .
                                                '<span class="font-bold">- Rp ' . number_format($discount, 2, ',', '.') . '</span>' .
                                            '</div>' .
                                            '<div class="flex justify-between text-base font-bold border-t border-success-300 dark:border-success-700 pt-2">' .
                                                '<span>' . __('Total Bayar') . '</span>' .
                                                '<span class="text-success-700 dark:text-success-300">Rp ' . number_format(max(0, $final), 2, ',', '.') . '</span>' .
                                            '</div>' .
                                        '</div>'
                                    );
                                }),
                        ]),
                ]),
            Forms\Components\Wizard\Step::make(__('Pilih Pembayaran'))
                ->icon('heroicon-o-credit-card')
                ->schema([
                    Forms\Components\Section::make(__('Metode Pembayaran'))
                        ->description(__('Pilih metode pembayaran yang Anda inginkan.'))
                        ->schema([
                            Forms\Components\Select::make('payment_method_id')
                                ->searchable()
                                ->label(__('Metode'))
                                ->options(function() use ($package) {
                                    $user = auth()->user();
                                    $methods = \App\Models\PaymentMethod::where('is_active', true)->get();
                                    return $methods->mapWithKeys(function ($method) use ($user, $package) {
                                        $label = $method->name;
                                        if ($method->type === \App\Enums\PaymentMethodType::WALLET) {
                                             $label .= " (Aktif: Rp " . number_format((float)($user->balance ?? 0), 2, ',', '.') . ")";
                                             if ($user->balance < $package->final_price) {
                                                  $label .= "  " . __('Saldo Kurang');
                                             }
                                        }
                                        return [$method->id => $label];
                                    });
                                })
                                ->required()
                                ->searchable()
                                ->preload()
                                ->live()
                                ->rules([
                                    fn (Forms\Get $get): \Closure => function (string $attribute, $value, \Closure $fail) use ($package) {
                                        $method = \App\Models\PaymentMethod::find($value);
                                        if ($method && $method->type === \App\Enums\PaymentMethodType::WALLET) {
                                            if (auth()->user()->balance < $package->final_price) {
                                                $fail(__('Saldo Anda tidak cukup. Silakan top up terlebih dahulu.'));
                                            }
                                        }
                                    },
                                ])
                                ->prefixIcon('heroicon-o-wallet'),
                            Forms\Components\Section::make(fn(Forms\Get $get) => \App\Models\PaymentMethod::find($get('payment_method_id'))?->name ?? __('Informasi Pembayaran'))
                                ->description(fn(Forms\Get $get) => \App\Models\PaymentMethod::find($get('payment_method_id'))?->type?->getLabel())
                                ->icon(function(Forms\Get $get) {
                                    $type = \App\Models\PaymentMethod::find($get('payment_method_id'))?->type;
                                    return match($type) {
                                        \App\Enums\PaymentMethodType::BANK_TRANSFER => 'heroicon-o-building-library',
                                        \App\Enums\PaymentMethodType::WALLET => 'heroicon-o-credit-card',
                                        default => 'heroicon-o-device-phone-mobile',
                                    };
                                })
                                ->iconColor('primary')
                                ->visible(fn(Forms\Get $get) => (bool) $get('payment_method_id'))
                                ->columns(2)
                                ->schema([
                                    // Saldo Preview
                                    Forms\Components\Placeholder::make('saldo_summary')
                                        ->hiddenLabel()
                                        ->columnSpanFull()
                                        ->visible(fn(Forms\Get $get) => \App\Models\PaymentMethod::find($get('payment_method_id'))?->type === \App\Enums\PaymentMethodType::WALLET)
                                        ->content(function() use ($package) {
                                            $user = auth()->user();
                                            $kurang = $package->final_price - $user->balance;
                                            return new \Illuminate\Support\HtmlString(
                                                '<div class="flex flex-col items-center gap-3 py-4 bg-gray-50 dark:bg-white/5 rounded-2xl border-2 border-dashed border-gray-200 dark:border-gray-800">' .
                                                    '<div class="flex items-center gap-2 px-3 py-1 bg-primary-100 dark:bg-primary-950 text-primary-600 dark:text-primary-400 rounded-full text-[10px] font-bold uppercase tracking-wider">' .
                                                        '<span class="relative flex h-2 w-2"><span class="animate-ping absolute inline-flex h-full w-full rounded-full bg-primary-400 opacity-75"></span><span class="relative inline-flex rounded-full h-2 w-2 bg-primary-500"></span></span>' .
                                                        __('Pembayaran Instan') .
                                                    '</div>' .
                                                    '<div class="text-center">' .
                                                        '<p class="text-xs text-gray-500 dark:text-gray-400">' . __('Saldo Anda Saat Ini') . '</p>' .
                                                        '<p class="text-2xl font-bold text-gray-950 dark:text-white">Rp ' . number_format((float)($user->balance ?? 0), 0, ',', '.') . '</p>' .
                                                    '</div>' .
                                                    ($kurang > 0 
                                                        ? '<div class="px-4 py-2 bg-danger-50 dark:bg-danger-950 text-danger-600 dark:text-danger-400 rounded-xl text-xs font-bold border border-danger-200 dark:border-danger-800 text-center mx-4">' .
                                                            __('Kekurangan Saldo:') . ' Rp ' . number_format($kurang, 0, ',', '.') . 
                                                          '</div>'
                                                        : '<div class="px-4 py-2 bg-success-50 dark:bg-success-950 text-success-600 dark:text-success-400 rounded-xl text-xs font-bold border border-success-200 dark:border-success-800 text-center mx-4">' .
                                                            __('Saldo cukup untuk transaksi ini') . 
                                                          '</div>') .
                                                '</div>'
                                            );
                                        }),

                                    Forms\Components\Placeholder::make('_account_number')
                                        ->label(__('Nomor Rekening / Tujuan'))
                                        ->visible(fn(Forms\Get $get) => \App\Models\PaymentMethod::find($get('payment_method_id'))?->type === \App\Enums\PaymentMethodType::BANK_TRANSFER)
                                        ->content(fn(Forms\Get $get) => new \Illuminate\Support\HtmlString(
                                            '<p class="text-3xl font-bold tracking-[0.2em] select-all text-center py-2 text-gray-950 dark:text-white">' .
                                            (\App\Models\PaymentMethod::find($get('payment_method_id'))?->account_number ?? '-') .
                                            '</p><p class="text-[10px] text-center mt-1 opacity-60 text-gray-500 dark:text-gray-400">' . __('Tekan & tahan untuk menyalin') . '</p>'
                                        ))
                                        ->extraAttributes(['class' => 'rounded-xl border-2 border-primary-200 dark:border-primary-800 bg-primary-50 dark:bg-primary-950 text-center p-1'])
                                        ->columnSpanFull(),
                                    Forms\Components\Placeholder::make('_account_holder')
                                        ->label(__('Atas Nama'))
                                        ->visible(fn(Forms\Get $get) => \App\Models\PaymentMethod::find($get('payment_method_id'))?->type === \App\Enums\PaymentMethodType::BANK_TRANSFER)
                                        ->content(fn(Forms\Get $get) => \App\Models\PaymentMethod::find($get('payment_method_id'))?->account_holder ?? '-'),
                                    Forms\Components\Placeholder::make('_admin_fee')
                                        ->label(__('Biaya Admin'))
                                        ->content(fn(Forms\Get $get) => ((\App\Models\PaymentMethod::find($get('payment_method_id'))?->fee ?? 0) > 0)
                                            ? 'Rp ' . number_format(\App\Models\PaymentMethod::find($get('payment_method_id'))?->fee ?? 0, 2, ',', '.')
                                            : __('Gratis')),
                                ]),
                            Forms\Components\Placeholder::make('qris_preview')
                                ->label(__('Scan QRIS Berikut'))
                                ->visible(function(Forms\Get $get) {
                                    $method = \App\Models\PaymentMethod::find($get('payment_method_id'));
                                    return $method && $method->type === \App\Enums\PaymentMethodType::QRIS;
                                })
                                ->content(function(Forms\Get $get) {
                                    $method = \App\Models\PaymentMethod::find($get('payment_method_id'));
                                    $url = $method?->qris_image_url;
                                    if (!$url) return new \Illuminate\Support\HtmlString('<div class="flex flex-col items-center gap-2 py-4"><span class="text-sm text-danger-500">' . __('Gambar QRIS tidak tersedia.') . '</span></div>');
                                    return new \Illuminate\Support\HtmlString(
                                        '<div class="flex flex-col items-center py-3">' .
                                            '<div class="p-3 bg-white dark:bg-gray-950 rounded-2xl shadow-xl border border-gray-100 dark:border-gray-800 inline-block">' .
                                                '<img src="' . e($url) . '" class="w-64 h-64 object-contain" alt="QRIS" onerror="this.style.display=\'none\'" />' .
                                            '</div>' .
                                        '</div>'
                                    );
                                })
                                ->columnSpanFull(),
                            Forms\Components\Actions::make([
                                Forms\Components\Actions\Action::make('unduh_qris')
                                    ->label(__('Unduh Gambar QRIS'))
                                    ->icon('heroicon-m-arrow-down-tray')
                                    ->color('primary')
                                    ->size('lg')
                                    ->button()
                                    ->url(function(Forms\Get $get) {
                                        $method = \App\Models\PaymentMethod::find($get('payment_method_id'));
                                        return $method?->qris_image_url;
                                    }, true)
                                    ->extraAttributes([
                                        'download' => 'qris.png',
                                    ]),
                            ])
                                ->alignCenter()
                                ->visible(function(Forms\Get $get) {
                                    $method = \App\Models\PaymentMethod::find($get('payment_method_id'));
                                    return $method && $method->type === \App\Enums\PaymentMethodType::QRIS && $method->qris_image_url;
                                })
                                ->columnSpanFull(),
                            Forms\Components\Placeholder::make('qris_hint')
                                ->hiddenLabel()
                                ->content(__('Simpan QRIS ini atau scan langsung dari ponsel Anda.'))
                                ->visible(function(Forms\Get $get) {
                                    $method = \App\Models\PaymentMethod::find($get('payment_method_id'));
                                    return $method && $method->type === \App\Enums\PaymentMethodType::QRIS;
                                })
                                ->extraAttributes(['class' => 'text-[10px] text-center font-bold opacity-60 text-gray-500 dark:text-gray-400'])
                                ->columnSpanFull(),
                        ]),
                ]),
            Forms\Components\Wizard\Step::make(__('Konfirmasi'))
                ->icon('heroicon-o-check-badge')
                ->schema([
                    Forms\Components\Section::make(__('Ringkasan Pembayaran'))
                        ->schema([
                            Forms\Components\Placeholder::make('pkg_summary')
                                ->label(__('Layanan'))
                                ->content($package->name),
                            Forms\Components\Placeholder::make('price_summary')
                                ->label(__('Total Harga'))
                                ->content('Rp ' . number_format($package->final_price, 0, ',', '.'))
                                ->extraAttributes(['class' => 'text-primary-600 dark:text-primary-400 font-bold text-2xl']),
                            Forms\Components\Placeholder::make('terms')
                                ->label('')
                                ->content(__('Dengan menekan tombol pesan, Anda setuju dengan Syarat & Ketentuan layanan kami.')),
                        ]),
                ]),
        ];
    }

    public static function handleCheckout(\App\Models\Package $package, array $data): void
    {
        $user = auth()->user();

        // Update user phone if changed
        if ($data['phone'] !== $user->phone) {
            $user->update(['phone' => $data['phone']]);
        }

        $method = \App\Models\PaymentMethod::find($data['payment_method_id']);

        // Voucher discount
        $voucherId = $data['voucher_id'] ?? null;
        $voucherDiscount = (float) ($data['voucher_discount'] ?? 0);
        $finalPrice = max(0, $package->final_price - $voucherDiscount);

        // Default statuses
        $orderStatus = \App\Enums\OrderStatus::PENDING;
        $orderPaymentStatus = \App\Enums\OrderPaymentStatus::PENDING;
        $paymentStatus = \App\Enums\PaymentStatus::PENDING;
        $paidAt = null;

        // Handle Saldo (Wallet) Payment
        if ($method->type === \App\Enums\PaymentMethodType::WALLET) {
            if ($user->balance >= $finalPrice) {
                $user->decrement('balance', $finalPrice);
                $orderStatus = \App\Enums\OrderStatus::CONFIRMED;
                $orderPaymentStatus = \App\Enums\OrderPaymentStatus::PAID;
                $paymentStatus = \App\Enums\PaymentStatus::SUCCESS;
                $paidAt = now();
            }
        }

        $order = \App\Models\Order::create([
            'user_id'        => $user->id,
            'package_id'     => $package->id,
            'order_number'   => 'ORD-' . strtoupper(str()->random(8)),
            'total_price'    => $finalPrice,
            'status'         => $orderStatus,
            'payment_status' => $orderPaymentStatus,
            'booking_date'   => $data['booking_date'],
            'notes'          => $data['notes'],
        ]);

        \App\Models\Payment::create([
            'order_id'       => $order->id,
            'payment_number' => 'PAY-' . strtoupper(str()->random(8)),
            'payment_method' => $method->code,
            'amount'         => $finalPrice,
            'admin_fee'      => $method->fee ?? 0,
            'total_amount'   => $finalPrice + ($method->fee ?? 0),
            'status'         => $paymentStatus,
            'paid_at'        => $paidAt,
        ]);

        // Mark voucher as used
        if ($voucherId) {
            $voucher = \App\Models\Voucher::find($voucherId);
            $voucher?->markAsUsedBy($user->id, $order->id);
        }

        $notification = \Filament\Notifications\Notification::make()
            ->title(__('Checkout Berhasil!'))
            ->success()
            ->icon('heroicon-o-shopping-bag')
            ->iconColor('success');

        if ($paymentStatus === \App\Enums\PaymentStatus::SUCCESS) {
            $notification->body(__('Pembayaran menggunakan Saldo berhasil. Pesanan Anda telah dikonfirmasi.'));
        } else {
            $notification->body(__('Silahkan unggah bukti transfer di menu Pesanan Saya.'));
        }

        $notification->send();
    }

    /**
     * Format similarity score to specific percentage steps:
     * 0, 5, 10, 15, 20, 25, 35, 40, 45, 50, 55, 60, 65, 70, 75, 80, 85, 90, 95, 100
     */
    public static function formatSimilarityPct(float $score): int
    {
        $pct = (int) (round($score * 100 / 5) * 5);
        
        // Skip 30 as requested (25 -> 35 jump)
        if ($pct === 30) {
            // If raw score is >= 0.3 then round up to 35, else round down to 25?
            // Usually, if it hit 30, it means it was in [27.5, 32.5). 
            // We'll just push it to 35 to follow the list's next available step.
            $pct = 35;
        }
        
        return min(100, max(0, $pct));
    }
}
