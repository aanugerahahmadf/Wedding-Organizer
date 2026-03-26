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

class PackageResource extends Resource
{
    protected static ?string $model = Package::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $slug = 'catalog';

    protected static ?string $recordTitleAttribute = 'name';

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

    protected static ?int $navigationSort = 2;


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
                    ->modalDescription(__('Ubah cara Anda mencari layanan. Unggah foto atau ambil gambar langsung untuk menemukan Wedding Organizer terbaik.'))
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
                                            $topScore = round($results[0]['score'] * 100);
                                            session()->put('cbir_results_with_scores', $scores);
                                            session()->put('cbir_results', array_keys($scores));
                                            $set('status_message', __('Hasil ditemukan! Akurasi tertinggi :score%', ['score' => $topScore]));
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
                                            $topScore = round($results[0]['score'] * 100);
                                            session()->put('cbir_results_with_scores', $scores);
                                            session()->put('cbir_results', array_keys($scores));
                                            $set('status_message', __('Berhasil menemukan :count layanan! Akurasi kemiripan gambar: :score%', ['count' => count($scores), 'score' => $topScore]));
                                        } else {
                                            session()->forget(['cbir_results_with_scores', 'cbir_results']);
                                            $set('status_message', __('Tidak ada paket yang cocok di database. Image tidak dikenali sistem.'));
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

                                        $topScore = round(($packages->first()?->similarity_score ?? 0) * 100);
                                        $components = [];

                                        // ── Header ──────────────────────────────────
                                        $components[] = Forms\Components\Section::make()
                                            ->compact()
                                            ->schema([
                                                Forms\Components\Placeholder::make('cbir_header_label')
                                                    ->hiddenLabel()
                                                    ->content(__('✨ Hasil Mirip')),
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
                                            $pct   = round($pkg->similarity_score * 100);
                                            $color = $pkg->similarity_score >= 0.85 ? 'success' : ($pkg->similarity_score >= 0.65 ? 'warning' : 'gray');
                                            $price = 'Rp ' . number_format($pkg->price, 0, ',', '.');
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
                                                                        . svg('heroicon-m-building-storefront', 'w-4 h-4')->toHtml()
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
                                                            ->icon('heroicon-m-shopping-cart')
                                                            ->color('primary')
                                                            ->size('sm')
                                                            ->url('/user/my-orders/create?package_id=' . $pkg->id),
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
                                                ->action(fn($livewire) => $livewire->dispatch('refresh_catalog')),
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
                'md' => 2,
                'lg' => 3,
                'xl' => 4,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\ImageColumn::make('image_url')
                        ->label('')
                        ->height('14rem')
                        ->width('100%')
                        ->extraImgAttributes(['class' => 'object-cover rounded-t-3xl transition-all duration-500 hover:scale-105']),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('name')
                            ->weight(FontWeight::Bold)
                            ->size('lg')
                            ->searchable(),
                        Tables\Columns\TextColumn::make('category.name')
                            ->badge()
                            ->color('gray'),
                        Tables\Columns\Layout\Split::make([
                            Tables\Columns\TextColumn::make('price')
                                ->money('idr')
                                ->weight(FontWeight::Black)
                                ->color('primary')
                                ->size('xl'),
                            Tables\Columns\Layout\Stack::make([
                                Tables\Columns\TextColumn::make('avg_rating')
                                    ->state(fn ($record) => number_format($record->reviews()->avg('rating') ?: 0, 1))
                                    ->icon('heroicon-m-star')
                                    ->color('warning')
                                    ->size('xs')
                                    ->alignEnd(),
                                Tables\Columns\TextColumn::make('sold_count')
                                    ->state(fn ($record) => $record->orders()->count() . ' ' . __('Terjual'))
                                    ->size('xs')
                                    ->color('gray')
                                    ->alignEnd(),
                            ]),
                        ]),
                        Tables\Columns\TextColumn::make('is_featured')
                            ->state(fn ($record) => $record?->is_featured ? '🔥 ' . __('TOP DEAL') : null)
                            ->badge()
                            ->color('danger')
                            ->visible(fn ($record) => (bool) ($record?->is_featured ?? false))
                            ->extraAttributes(['class' => 'mt-2 animate-pulse']),
                    ])->space(3)->extraAttributes(['class' => 'p-6']),
                ])->extraAttributes(['class' => 'bg-white dark:bg-gray-950 rounded-3xl shadow-2xl border-0 overflow-hidden ring-1 ring-gray-200 dark:ring-gray-800']),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('category_id')
                    ->label(__('Kategori'))
                    ->searchable()
                    ->relationship('category', 'name')
                    ->native(false)
                    ->preload(),
            ])
            ->actions([
                Tables\Actions\Action::make('view_detail')
                    ->hiddenLabel()
                    ->tooltip(__('Lihat Detail'))
                    ->icon('heroicon-o-eye')
                    ->color('gray')
                    ->iconButton()
                    ->size('lg')
                    ->slideOver()
                    ->modalWidth('2xl')
                    ->modalHeading(fn($record) => $record->name)
                    ->modalDescription(fn($record) => $record->category?->name)
                    ->modalSubmitAction(false)
                    ->modalCancelActionLabel(__('Tutup'))
                    ->infolist([
                        // Hero image
                        Infolists\Components\ImageEntry::make('image_url')
                            ->label('')
                            ->height('20rem')
                            ->width('100%')
                            ->extraImgAttributes(['class' => 'object-cover w-full rounded-2xl shadow-xl'])
                            ->columnSpanFull(),
                        // Price & badges row
                        Infolists\Components\Group::make([
                            Infolists\Components\TextEntry::make('price')
                                ->label('')
                                ->money('idr')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large)
                                ->weight(\Filament\Support\Enums\FontWeight::Black)
                                ->color('primary'),
                            Infolists\Components\TextEntry::make('category.name')
                                ->label('')
                                ->badge()
                                ->color('info'),
                            Infolists\Components\TextEntry::make('is_featured')
                                ->label('')
                                ->badge()
                                ->formatStateUsing(fn($state) => $state ? __('Unggulan') : null)
                                ->color('warning')
                                ->visible(fn($state) => (bool) $state),
                        ])->columnSpanFull(),

                        // Stats row
                        Infolists\Components\Grid::make(3)->schema([
                            Infolists\Components\TextEntry::make('avg_rating')
                                ->label(__('Rating'))
                                ->icon('heroicon-m-star')
                                ->iconColor('warning')
                                ->weight(\Filament\Support\Enums\FontWeight::Bold),
                            Infolists\Components\TextEntry::make('min_capacity')
                                ->label(__('Kapasitas Min'))
                                ->icon('heroicon-o-users')
                                ->iconColor('gray')
                                ->suffix(' ' . __('tamu'))
                                ->placeholder('-'),
                            Infolists\Components\TextEntry::make('max_capacity')
                                ->label(__('Kapasitas Maks'))
                                ->icon('heroicon-o-users')
                                ->iconColor('gray')
                                ->suffix(' ' . __('tamu'))
                                ->placeholder('-'),
                        ])->columnSpanFull(),

                        // Description
                        Infolists\Components\Section::make(__('Deskripsi Paket'))
                            ->icon('heroicon-o-document-text')
                            ->iconColor('primary')
                            ->compact()
                            ->schema([
                                Infolists\Components\TextEntry::make('description')
                                    ->label('')
                                    ->html()
                                    ->placeholder(__('Tidak ada deskripsi.'))
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),

                        // Fitur / highlights
                        Infolists\Components\Section::make(__('Yang Termasuk dalam Paket'))
                            ->icon('heroicon-o-sparkles')
                            ->iconColor('warning')
                            ->compact()
                            ->schema([
                                Infolists\Components\TextEntry::make('features')
                                    ->label('')
                                    ->listWithLineBreaks()
                                    ->bulleted()
                                    ->placeholder('-')
                                    ->columnSpanFull(),
                            ])
                            ->columnSpanFull(),

                        // WO info
                        Infolists\Components\Section::make(__('Wedding Organizer'))
                            ->icon('heroicon-o-building-office-2')
                            ->iconColor('success')
                            ->compact()
                            ->columns(2)
                            ->schema([
                                Infolists\Components\TextEntry::make('weddingOrganizer.name')
                                    ->label(__('Nama WO'))
                                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                                    ->columnSpanFull(),
                                Infolists\Components\TextEntry::make('theme')
                                    ->label(__('Tema'))
                                    ->badge()
                                    ->color('success')
                                    ->placeholder('-'),
                                Infolists\Components\TextEntry::make('color')
                                    ->label(__('Warna Tema'))
                                    ->badge()
                                    ->color('gray')
                                    ->placeholder('-'),
                            ])
                            ->columnSpanFull(),
                    ]),
                Tables\Actions\Action::make('chat')
                    ->hiddenLabel()
                    ->tooltip(__('Chat Pembeli'))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('info')
                    ->iconButton()
                    ->size('lg')
                    ->url(fn() => '/user/messages'),
                Tables\Actions\Action::make('wishlist')
                    ->hiddenLabel()
                    ->tooltip(__('Favorit'))
                    ->icon('heroicon-o-heart')
                    ->color('danger')
                    ->iconButton()
                    ->size('lg')
                    ->action(function ($record) {
                        \App\Models\Wishlist::updateOrCreate([
                            'user_id' => auth()->id(),
                            'package_id' => $record->id,
                        ]);
                        Notification::make()->title(__('Disimpan ke Favorit'))->success()->send();
                    }),
                Tables\Actions\Action::make('book')
                    ->label(__('Beli'))
                    ->button()
                    ->color('primary')
                    ->size('lg')
                    ->icon('heroicon-m-shopping-cart')
                    ->extraAttributes(['class' => 'h-12 flex-1 whitespace-nowrap'])
                    ->slideOver()
                    ->modalWidth('2xl')
                    ->modalHeading(__('Checkout Layanan'))
                    ->steps([
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
                        Forms\Components\Wizard\Step::make(__('Pilih Pembayaran'))
                            ->icon('heroicon-o-credit-card')
                            ->schema([
                                Forms\Components\Section::make(__('Metode Pembayaran'))
                                    ->description(__('Pilih metode pembayaran yang Anda inginkan.'))
                                    ->schema([
                                        Forms\Components\Select::make('payment_method_id')
                                            ->label(__('Metode'))
                                            ->options(\App\Models\PaymentMethod::where('is_active', true)->pluck('name', 'id'))
                                            ->required()
                                            ->searchable()
                                            ->preload()
                                            ->live()
                                            ->prefixIcon('heroicon-o-wallet'),
                                        Forms\Components\Section::make(fn(Forms\Get $get) => \App\Models\PaymentMethod::find($get('payment_method_id'))?->name ?? __('Informasi Pembayaran'))
                                            ->description(fn(Forms\Get $get) => \App\Models\PaymentMethod::find($get('payment_method_id'))?->type?->getLabel())
                                            ->icon(fn(Forms\Get $get) => (\App\Models\PaymentMethod::find($get('payment_method_id'))?->type === \App\Enums\PaymentMethodType::BANK_TRANSFER)
                                                ? 'heroicon-o-building-library'
                                                : 'heroicon-o-device-phone-mobile')
                                            ->iconColor('primary')
                                            ->visible(fn(Forms\Get $get) => (bool) $get('payment_method_id'))
                                            ->columns(2)
                                            ->schema([
                                                // Nomor rekening — prominent box
                                                Forms\Components\Placeholder::make('_account_number')
                                                    ->label(__('Nomor Rekening / Tujuan'))
                                                    ->content(fn(Forms\Get $get) => new \Illuminate\Support\HtmlString(
                                                        '<p class="font-mono text-3xl font-bold tracking-[0.2em] select-all text-center py-2 text-gray-950 dark:text-white">' .
                                                        (\App\Models\PaymentMethod::find($get('payment_method_id'))?->account_number ?? '-') .
                                                        '</p><p class="text-[10px] text-center mt-1 opacity-60 text-gray-500 dark:text-gray-400">' . __('Tekan & tahan untuk menyalin') . '</p>'
                                                    ))
                                                    ->extraAttributes(['class' => 'rounded-xl border-2 border-primary-200 dark:border-primary-800 bg-primary-50 dark:bg-primary-950 text-center p-1'])
                                                    ->columnSpanFull(),
                                                // Atas nama
                                                Forms\Components\Placeholder::make('_account_holder')
                                                    ->label(__('Atas Nama (a/n)'))
                                                    ->content(fn(Forms\Get $get) => \App\Models\PaymentMethod::find($get('payment_method_id'))?->account_holder ?? '-'),
                                                // Biaya admin
                                                Forms\Components\Placeholder::make('_admin_fee')
                                                    ->label(__('Biaya Admin'))
                                                    ->content(fn(Forms\Get $get) => ((\App\Models\PaymentMethod::find($get('payment_method_id'))?->fee ?? 0) > 0)
                                                        ? 'Rp ' . number_format(\App\Models\PaymentMethod::find($get('payment_method_id'))?->fee ?? 0, 0, ',', '.')
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
                                                if (!$url) {
                                                    return new \Illuminate\Support\HtmlString(
                                                        '<div class="flex flex-col items-center gap-2 py-4">'
                                                        . '<span class="text-sm text-danger-500">' . __('Gambar QRIS tidak tersedia.') . '</span>'
                                                        . '</div>'
                                                    );
                                                }
                                                return new \Illuminate\Support\HtmlString(
                                                    '<div class="flex flex-col items-center gap-4 py-3">'
                                                    . '<div class="p-3 bg-white dark:bg-gray-900 rounded-2xl shadow-xl border-2 border-primary-200 dark:border-primary-700 inline-block">'
                                                    . '<img src="' . e($url) . '" '
                                                    . 'class="w-64 h-64 object-contain" '
                                                    . 'alt="QRIS" onerror="this.style.display=\'none\'" />'
                                                    . '</div>'
                                                    . '<p class="text-[10px] text-center opacity-70 font-bold max-w-xs text-gray-600 dark:text-gray-400">'
                                                    . __('Simpan QRIS ini atau scan langsung dari ponsel Anda.')
                                                    . '</p></div>'
                                                );
                                            })
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
                                            ->content(fn($record) => $record->name),
                                        Forms\Components\Placeholder::make('price_summary')
                                            ->label(__('Total Harga'))
                                            ->content(fn($record) => 'Rp ' . number_format($record->price, 0, ',', '.'))
                                            ->extraAttributes(['class' => 'text-primary-600 dark:text-primary-400 font-bold text-2xl']),
                                        Forms\Components\Placeholder::make('terms')
                                            ->label('')
                                            ->content(__('Dengan menekan tombol pesan, Anda setuju dengan Syarat & Ketentuan layanan kami.')),
                                    ]),
                            ]),
                    ])
                    ->action(function ($record, array $data) {
                        // Update user phone if changed
                        if ($data['phone'] !== auth()->user()->phone) {
                            auth()->user()->update(['phone' => $data['phone']]);
                        }

                        $order = Order::create([
                            'user_id' => auth()->id(),
                            'package_id' => $record->id,
                            'order_number' => 'ORD-' . strtoupper(str()->random(8)),
                            'total_price' => $record->price,
                            'status' => \App\Enums\OrderStatus::PENDING,
                            'booking_date' => $data['booking_date'],
                            'notes' => $data['notes'],
                        ]);

                        $method = \App\Models\PaymentMethod::find($data['payment_method_id']);
                        
                        \App\Models\Payment::create([
                            'order_id' => $order->id,
                            'payment_number' => 'PAY-' . strtoupper(str()->random(8)),
                            'payment_method' => $method->code,
                            'amount' => $record->price,
                            'admin_fee' => $method->fee ?? 0,
                            'total_amount' => $record->price + ($method->fee ?? 0),
                            'status' => 'pending',
                        ]);

                        Notification::make()
                            ->title(__('Checkout Berhasil!'))
                            ->body(__('Silahkan unggah bukti transfer di menu Pesanan Saya.'))
                            ->success()
                            ->send();
                    }),
            ]);
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Section::make()
                    ->schema([
                        Infolists\Components\ImageEntry::make('image_url')
                            ->label('')
                            ->height('20rem')
                            ->width('100%')
                            ->extraImgAttributes(['class' => 'object-cover rounded-2xl']),
                        Infolists\Components\TextEntry::make('name')
                            ->weight(FontWeight::Bold)
                            ->size('lg'),
                        Infolists\Components\TextEntry::make('description')
                            ->markdown()
                            ->prose(),
                    ])
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\User\Resources\PackageResource\Pages\ManagePackages::route('/'),
        ];
    }
}
