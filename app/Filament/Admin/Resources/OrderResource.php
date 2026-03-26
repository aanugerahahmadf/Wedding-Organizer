<?php

namespace App\Filament\Admin\Resources;

use App\Filament\Admin\Pages\MessagesPage;
use App\Filament\Admin\Resources\OrderResource\Pages;
use App\Filament\Admin\Resources\OrderResource\RelationManagers;
use App\Models\Inbox;
use App\Models\Message;
use App\Models\Order;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * @mixin \Eloquent
 * @property-read \App\Models\Order $record
 */
class OrderResource extends Resource

{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function getModelLabel(): string
    {
        return __('Pesanan');
    }

    public static function getPluralModelLabel(): string
    {
        return __('Pesanan');
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['order_number'];
    }

    public static function getNavigationGroup(): ?string
    {
        return __('Transaksi');
    }

    public static function getNavigationLabel(): string
    {
        return __('Daftar Pesanan');
    }

    public static function getNavigationBadge(): ?string
    {
        /** @var Builder $query */
        $query = static::$model::query();

        return (string) $query->count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return __('Manajemen Pesanan Pelanggan');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make(__('Informasi Pelanggan & Layanan'))
                            ->description(__('Hubungkan pesanan ke pelanggan dan paket yang dipilih.'))
                            ->icon('heroicon-o-shopping-bag')
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label(__('Pelanggan'))
                                    ->options(User::query()->pluck('full_name', 'id')->toArray())
                                    ->searchable()
                                    ->preload()
                                    ->prefixIcon('heroicon-o-user')
                                    ->required(),
                                Forms\Components\Select::make('package_id')
                                    ->label(__('Paket Layanan'))
                                    ->relationship('package', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->prefixIcon('heroicon-o-gift')
                                    ->required(),
                            ])->columns(2),

                        Forms\Components\Section::make(__('Detail Eksekusi & Acara'))
                            ->description(__('Jadwal, referensi, dan instruksi penanganan dari pelanggan.'))
                            ->icon('heroicon-o-calendar-days')
                            ->schema([
                                Forms\Components\TextInput::make('order_number')
                                    ->label(__('Nomor Referensi'))
                                    ->required()
                                    ->maxLength(255)
                                    ->prefixIcon('heroicon-o-hashtag'),
                                Forms\Components\DatePicker::make('booking_date')
                                    ->label(__('Tanggal Acara (Booking)'))
                                    ->required()
                                    ->prefixIcon('heroicon-o-calendar'),
                                Forms\Components\RichEditor::make('notes')
                                    ->label(__('Catatan / Permintaan Khusus'))
                                    ->columnSpanFull()
                                    ->toolbarButtons(['bold', 'italic', 'underline', 'bulletList', 'orderedList']),
                            ])->columns(2),
                    ])->columnSpan(['lg' => 2]),

                Forms\Components\Group::make()
                    ->schema([
                        Forms\Components\Section::make(__('Status & Keuangan'))
                            ->description(__('Pantau dan update perkembangan pembayaran dan layanan.'))
                            ->icon('heroicon-o-banknotes')
                            ->schema([
                                Forms\Components\TextInput::make('total_price')
                                    ->label(__('Total Harga (Tagihan)'))
                                    ->required()
                                    ->numeric()
                                    ->prefix('Rp')
                                    ->extraInputAttributes(['class' => 'font-bold text-2xl text-primary-600']),
                                Forms\Components\Select::make('status')
                                    ->label(__('Status Pengerjaan'))
                                    ->options(\App\Enums\OrderStatus::class)
                                    ->searchable()
                                    ->native(false)
                                    ->required(),
                                Forms\Components\Select::make('payment_status')
                                    ->label(__('Status Pembayaran'))
                                    ->options(\App\Enums\OrderPaymentStatus::class)
                                    ->searchable()
                                    ->native(false)
                                    ->required(),
                            ]),
                    ])->columnSpan(['lg' => 1]),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.full_name')
                    ->label(__('Pelanggan'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('package.name')
                    ->label(__('Paket Layanan'))
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('order_number')
                    ->label(__('No. Pesanan'))
                    ->searchable()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('total_price')
                    ->label(__('Harga'))
                    ->money('IDR')
                    ->sortable()
                    ->alignment('right'),
                Tables\Columns\TextColumn::make('status')
                    ->label(__('Status'))
                    ->badge()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label(__('Pembayaran'))
                    ->badge()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('booking_date')
                    ->label(__('Tanggal Acara'))
                    ->date()
                    ->sortable()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('notes')
                    ->label(__('Catatan'))
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Tanggal Pesan'))
                    ->dateTime()
                    ->sortable()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label(__('Terakhir Diperbarui'))
                    ->dateTime()
                    ->sortable()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\Action::make('chat')
                    ->label(__('Hubungi'))
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->button()
                    ->url(function (Order $record) {
                        $authId = Auth::id();
                        $customerId = $record->user_id;
                        /** @var User|null $admin */
                        $admin = User::query()->role('super_admin')->first(['id']);
                        $adminId = $admin?->id ?? 1;

                        $targetId = ($authId == $customerId) ? $adminId : $customerId;

                        $inbox = Inbox::query()
                            ->whereJsonContains('user_ids', (int) $authId, 'and', false)
                            ->whereJsonContains('user_ids', (int) $targetId, 'and', false)
                            ->get(['*'])
                            /** @param Inbox $inbox */
                            ->first(function ($inbox) use ($authId, $targetId) {
                                $ids = collect($inbox->user_ids)->unique();

                                return $ids->contains($authId) && $ids->contains($targetId) && $ids->count() <= 2;
                            });

                        if (! $inbox) {
                            $inbox = Inbox::create([
                                'user_ids' => collect([(int) $authId, (int) $targetId])->unique()->values()->toArray(),
                                'title' => __('Diskusi Order #').$record->order_number,
                            ]);

                            Message::create([
                                'inbox_id' => $inbox->id,
                                'user_id' => $authId,
                                'message' => __('Halo, saya ingin mendiskusikan Pesanan #').$record->order_number.'.',
                                'read_by' => [$authId],
                            ]);
                        }

                        return MessagesPage::getUrl(['id' => $inbox->id]);
                    }),
                Tables\Actions\ViewAction::make()
                    ->slideOver()
                    ->button()
                    ->color('info')
                    ->size('lg'),
                Tables\Actions\EditAction::make()
                    ->slideOver()
                    ->button()
                    ->color('warning')
                    ->size('lg')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('Pesanan diperbarui'))
                            ->body(__('Pesanan telah berhasil diperbarui.'))
                    ),
                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->color('danger')
                    ->size('lg')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title(__('Pesanan dihapus'))
                            ->body(__('Pesanan telah berhasil dihapus.'))
                    ),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\PaymentsRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageOrders::route('/'),
        ];
    }
}
