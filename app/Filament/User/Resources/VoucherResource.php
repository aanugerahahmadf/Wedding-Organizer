<?php

namespace App\Filament\User\Resources;

use App\Models\Voucher;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Resources\Resource;
use Filament\Support\Enums\FontWeight;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Builder;

class VoucherResource extends Resource
{
    protected static ?string $model = Voucher::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';

    public static function getGloballySearchableAttributes(): array
    {
        return ['code', 'description'];
    }



    public static function getNavigationGroup(): ?string
    {
        return __('Belanja & Jelajahi');
    }

    public static function getNavigationLabel(): string
    {
        return __('Deals & Coupons');
    }


    public static function getNavigationBadge(): ?string
    {
        return (string) static::getEloquentQuery()->count();
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return static::getNavigationLabel();
    }

    public static function getEloquentQuery(): Builder
    {
        $userId = auth()->id();

        return parent::getEloquentQuery()
            ->with(['users' => fn ($q) => $q->where('users.id', $userId)])
            ->where('is_active', true)
            ->where(function (Builder $q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            })
            ->where(function (Builder $q) use ($userId) {
                $q->where('is_global', true)
                  ->orWhereHas('users', fn (Builder $u) => $u->where('users.id', $userId));
            })
            ->whereDoesntHave('users', function (Builder $q) use ($userId) {
                $q->where('users.id', $userId)->whereNotNull('user_vouchers.used_at');
            });
    }

    public static function table(Table $table): Table
    {
        return $table
            ->emptyStateHeading(__('Belum ada promo baru'))
            ->emptyStateDescription(__('Voucher spesial dari kami akan otomatis muncul di sini. Coba tanyakan admin untuk promo menarik!'))
            ->emptyStateIcon('heroicon-o-ticket')
            ->emptyStateActions([
                Tables\Actions\Action::make('chat_admin')
                    ->label(__('Tanya Admin'))
                    ->url(MessagesPage::getUrl())
                    ->button()
                    ->color('primary')
                    ->size('lg')
                    ->icon('heroicon-m-chat-bubble-bottom-center-text'),
            ])
            ->contentGrid([
                'sm' => 1,
                'md' => 2,
                'xl' => 3,
            ])
            ->columns([
                Tables\Columns\Layout\Stack::make([
                    
                    // -- NOMINAL BESAR DI TENGAH --
                    Tables\Columns\TextColumn::make('discount_amount')
                        ->formatStateUsing(function ($state, \App\Models\Voucher $record) {
                            if ($record->discount_type === \App\Enums\DiscountType::PERCENTAGE) {
                                return number_format((float) $state, 0) . '%';
                            }
                            return 'Rp ' . number_format((float) $state, 2, ',', '.');
                        })
                        ->weight(FontWeight::Bold)
                        ->color('warning')
                        ->alignCenter()
                        ->extraAttributes(['class' => '!text-5xl mt-2 tracking-tight']),
                        
                    // -- JENIS VOUCHER & MIN BELANJA DI TENGAH --
                    Tables\Columns\TextColumn::make('type_label')
                        ->state(fn (\App\Models\Voucher $record) => $record->discount_type === \App\Enums\DiscountType::PERCENTAGE ? 'VOUCHER DISKON' : 'VOUCHER CASHBACK')
                        ->weight(FontWeight::Bold)
                        ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                        ->color('primary')
                        ->alignCenter()
                        ->extraAttributes(['class' => 'tracking-widest opacity-80 mt-2']),
                        
                    Tables\Columns\TextColumn::make('min_purchase')
                        ->formatStateUsing(fn ($state) => $state > 0 ? 'Min. Blj Rp' . number_format((float) $state, 2, ',', '.') : 'Tanpa Minimum Belanja')
                        ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                        ->color('gray')
                        ->alignCenter(),

                    // -- GARIS PUTUS-PUTUS (DIVIDER NATIVE) --
                    Tables\Columns\TextColumn::make('divider')
                        ->default('')
                        ->extraAttributes(['class' => 'border-t-2 border-dashed border-gray-200 dark:border-gray-800 my-4 h-0 w-full pointer-events-none']),

                    // -- DESKRIPSI DAN TANGGAL --
                    Tables\Columns\TextColumn::make('description')
                        ->weight(FontWeight::Bold)
                        ->size(Tables\Columns\TextColumn\TextColumnSize::Medium)
                        ->color('gray')
                        ->alignCenter()
                        ->searchable()
                        ->extraAttributes(['class' => 'text-center mb-1 text-gray-900 dark:text-gray-100']),
                        
                    Tables\Columns\TextColumn::make('expires_at')
                        ->formatStateUsing(fn ($state) => $state ? 'Berlaku s/d ' . \Carbon\Carbon::parse($state)->translatedFormat('d M Y') : 'Berlaku Selamanya')
                        ->size(Tables\Columns\TextColumn\TextColumnSize::ExtraSmall)
                        ->color(fn ($state) => $state && \Carbon\Carbon::parse($state)->diffInDays(now()) <= 3 ? 'danger' : 'gray')
                        ->icon('heroicon-o-clock')
                        ->alignCenter(),

                    // -- KODE VOUCHER (KOTAK BERWARNA DI TENGAH) --
                    Tables\Columns\TextColumn::make('code')
                        ->weight(FontWeight::Bold)
                        ->size(Tables\Columns\TextColumn\TextColumnSize::Small)
                        ->color('warning')
                        ->copyable()
                        ->copyMessage('Kode Disalin!')
                        ->icon('heroicon-m-clipboard-document')
                        ->alignCenter()
                        ->searchable()
                        ->extraAttributes([
                            'class' => 'mt-4 bg-warning-50 flex dark:bg-warning-950/40 text-warning-600 dark:text-warning-400 px-4 py-2 rounded-xl border border-warning-200 dark:border-warning-800/60 justify-center items-center w-full mx-auto transition hover:bg-warning-100 dark:hover:bg-warning-900/60 cursor-pointer',
                        ]),
                        
                ])->space(0),
            ])
            ->actions([
                Tables\Actions\Action::make('klaim')
                    ->label(__('Klaim Voucher'))
                    ->icon('heroicon-m-plus-circle')
                    ->color('primary')
                    ->button()
                    ->size('lg')
                    ->visible(fn ($record) => ! $record->users->contains(auth()->id()))
                    ->extraAttributes([
                        'class' => 'w-full justify-center shadow-md font-bold mx-auto ring-1 ring-white/10',
                        'style' => 'width: 100%; max-width: 100%; display: flex; align-items: center; justify-content: center;',
                    ])
                    ->action(function ($record) {
                        if (! $record->users->contains(auth()->id())) {
                            $record->users()->attach(auth()->id(), [
                                'claimed_at' => now(), 
                                'created_at' => now(), 
                                'updated_at' => now()
                            ]);
                            \Filament\Notifications\Notification::make()
                                ->title(__('Voucher Berhasil Diklaim!'))
                                ->body(__('Kini Anda bisa menggunakan voucher ini pada saat Checkout.'))
                                ->icon('heroicon-o-check-circle')
                                ->iconColor('success')
                                ->success()
                                ->send();
                        }
                    }),
                
                Tables\Actions\Action::make('pakai')
                    ->label(__('Gunakan'))
                    ->icon('heroicon-m-shopping-bag')
                    ->color('warning')
                    ->button()
                    ->size('lg')
                    ->visible(fn ($record) => $record->users->contains(auth()->id()))
                    ->url(fn () => \App\Filament\User\Resources\PackageResource::getUrl('index'))
                    ->extraAttributes([
                        'class' => 'w-full justify-center shadow-md font-bold mx-auto ring-1 ring-white/10',
                        'style' => 'width: 100%; max-width: 100%; display: flex; align-items: center; justify-content: center;',
                    ])
            ])
            ->paginated(false);
    }

    public static function getPages(): array
    {
        return [
            'index' => \App\Filament\User\Resources\VoucherResource\Pages\ManageVouchers::route('/'),
        ];
    }
}
