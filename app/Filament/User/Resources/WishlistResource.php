<?php

namespace App\Filament\User\Resources;

use App\Models\Wishlist;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Support\Enums\FontWeight;

class WishlistResource extends Resource
{
    protected static ?string $model = Wishlist::class;

    protected static ?string $navigationIcon = 'heroicon-o-heart';

    protected static ?string $slug = 'wishlists';

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
                            ->relationship('package', 'name')
                            ->required()
                            ->searchable()
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
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    Tables\Columns\ImageColumn::make('package.image_url')
                        ->height('15rem')
                        ->width('100%')
                        ->extraImgAttributes(['class' => 'object-cover rounded-t-2xl']),
                    Tables\Columns\Layout\Stack::make([
                        Tables\Columns\TextColumn::make('package.name')
                            ->weight(FontWeight::Bold)
                            ->size('lg'),
                        Tables\Columns\TextColumn::make('package.category.name')
                            ->badge(),
                        Tables\Columns\TextColumn::make('package.is_featured')
                            ->state(fn ($record) => $record?->package?->is_featured ? '⭐ HOT DEAL' : null)
                            ->color('warning')
                            ->badge()
                            ->extraAttributes(['class' => 'mb-2 animate-pulse']),
                        Tables\Columns\TextColumn::make('package_price')
                            ->state(fn ($record) => $record?->package)
                            ->formatStateUsing(function ($state) {
                                if (! $state) return '-';
                                $price = 'Rp ' . number_format((float) $state->price, 0, ',', '.');
                                if ($state->discount_price > 0 && $state->discount_price < $state->price) {
                                    $discount = 'Rp ' . number_format((float) $state->discount_price, 0, ',', '.');
                                    return new \Illuminate\Support\HtmlString('
                                        <div class="flex flex-col items-center justify-center">
                                            <span class="text-xs text-gray-400 line-through">' . $price . '</span>
                                            <span class="text-md font-bold text-amber-500">' . $discount . '</span>
                                        </div>
                                    ');
                                }
                                return new \Illuminate\Support\HtmlString('<span class="text-md font-bold text-amber-500">' . $price . '</span>');
                            })
                            ->html(),
                    ])->space(3)->extraAttributes(['class' => 'p-6 flex flex-col items-center text-center']),
                ])->extraAttributes(['class' => 'bg-white dark:bg-gray-900 rounded-2xl shadow-sm border border-gray-100 dark:border-gray-800 overflow-hidden relative']),
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
                    ->extraAttributes(['class' => 'h-12 w-12 !px-0 flex items-center justify-center']),
                Tables\Actions\Action::make('checkout')
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
                                                        '<p class="font-mono text-3xl font-bold tracking-[0.2em] select-all text-center py-2">' .
                                                        (\App\Models\PaymentMethod::find($get('payment_method_id'))?->account_number ?? '-') .
                                                        '</p><p class="text-[10px] text-center mt-1 opacity-60">' . __('Tekan & tahan untuk menyalin') . '</p>'
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
                                                    . '<p class="text-[10px] text-center opacity-60 font-bold max-w-xs">'
                                                    . __('Simpan QRIS ini atau scan langsung dari aplikasi bank/e-wallet Anda.')
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
                                            ->content(fn($record) => $record->package->name ?? '-'),
                                        Forms\Components\Placeholder::make('price_summary')
                                            ->label(__('Total Harga'))
                                            ->content(function($record) {
                                                if (! $record->package) return '-';
                                                $price = ($record->package->discount_price > 0) ? $record->package->discount_price : $record->package->price;
                                                return 'Rp ' . number_format($price, 0, ',', '.');
                                            })
                                            ->extraAttributes(['class' => 'text-primary-600 font-bold text-2xl']),
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

                        $p = $record->package;
                        if (! $p) return;
                        
                        $price = ($p->discount_price > 0) ? $p->discount_price : $p->price;

                        $order = \App\Models\Order::create([
                            'user_id' => auth()->id(),
                            'package_id' => $p->id,
                            'order_number' => 'ORD-' . strtoupper(str()->random(8)),
                            'total_price' => $price,
                            'status' => \App\Enums\OrderStatus::PENDING,
                            'payment_status' => \App\Enums\OrderPaymentStatus::PENDING,
                            'booking_date' => $data['booking_date'],
                            'notes' => $data['notes'],
                        ]);

                        $method = \App\Models\PaymentMethod::find($data['payment_method_id']);
                        
                        if ($method) {
                            \App\Models\Payment::create([
                                'order_id' => $order->id,
                                'payment_number' => 'PAY-' . strtoupper(str()->random(8)),
                                'payment_method' => $method->code,
                                'amount' => $price,
                                'admin_fee' => $method->fee ?? 0,
                                'total_amount' => $price + ($method->fee ?? 0),
                                'status' => 'pending',
                            ]);
                        }

                        // Remove from wishlist
                        $record->delete();

                        \Filament\Notifications\Notification::make()
                            ->title(__('Checkout Berhasil!'))
                            ->body(__('Silahkan unggah bukti transfer di menu Pesanan Saya.'))
                            ->success()
                            ->send();
                            
                        // Redirect to orders
                        redirect('/user/my-orders');
                    }),
            ])
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
