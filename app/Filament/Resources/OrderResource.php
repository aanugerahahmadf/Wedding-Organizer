<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Order;
use App\Models\User;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    protected static ?string $navigationLabel = 'Daftar Pesanan';

    protected static ?string $navigationGroup = 'Transactions';

    protected static ?int $navigationSort = 1;

    protected static ?string $recordTitleAttribute = 'order_number';

    public static function getGloballySearchableAttributes(): array
    {
        return ['order_number'];
    }

    public static function getNavigationBadge(): ?string
    {
        return static::$model::count();
    }

    public static function getNavigationBadgeColor(): ?string
    {
        return 'primary';
    }

    public static function getNavigationBadgeTooltip(): ?string
    {
        return 'Manajemen Pesanan Pelanggan';
    }

    public static function form(\Filament\Forms\Form $form): \Filament\Forms\Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Customer & Service')
                    ->description('Link the order to a customer and their selected package.')
                    ->schema([
                        Forms\Components\Select::make('user_id')
                            ->label('Customer')
                            ->options(User::all()->pluck('name', 'id'))
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('package_id')
                            ->label('Service Package')
                            ->relationship('package', 'name')
                            ->searchable()
                            ->preload()
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('Order Information')
                    ->description('Core details regarding the order number and schedule.')
                    ->schema([
                        Forms\Components\TextInput::make('order_number')
                            ->label('Order Reference')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\DatePicker::make('booking_date')
                            ->label('Event Date')
                            ->required(),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpan('full'),
                    ])->columns(2),

                Forms\Components\Section::make('Financial Status')
                    ->description('Pricing and payment tracking for this transaction.')
                    ->schema([
                        Forms\Components\TextInput::make('total_price')
                            ->label('Total Amount')
                            ->required()
                            ->numeric()
                            ->prefix('IDR'),
                        Forms\Components\Select::make('status')
                            ->label('Order Status')
                            ->options([
                                'pending' => 'Pending',
                                'confirmed' => 'Confirmed',
                                'cancelled' => 'Cancelled',
                                'completed' => 'Completed',
                            ])
                            ->searchable()
                            ->required(),
                        Forms\Components\Select::make('payment_status')
                            ->label('Payment Status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'failed' => 'Failed',
                            ])
                            ->searchable()
                            ->required(),
                    ])->columns(3),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->mobileCards()
            ->mobileCardFeatured('total_amount', 'rose')
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->numeric()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('package.name')
                    ->label('Service Package')
                    ->numeric()
                    ->sortable()
                    ->searchable(),
                Tables\Columns\TextColumn::make('order_number')
                    ->label('Order #')
                    ->searchable()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('total_price')
                    ->label('Amount')
                    ->money('IDR')
                    ->sortable()
                    ->alignment('right'),
                Tables\Columns\TextColumn::make('status')
                    ->label('Status')
                    ->badge()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('payment_status')
                    ->label('Payment')
                    ->badge()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('booking_date')
                    ->label('Event Date')
                    ->date()
                    ->sortable()
                    ->alignment('center'),
                Tables\Columns\TextColumn::make('notes')
                    ->label('Notes')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Order Date')
                    ->dateTime()
                    ->sortable()
                    ->alignment('center')
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->label('Updated At')
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
                    ->label('Hubungi')
                    ->icon('heroicon-o-chat-bubble-left-right')
                    ->color('success')
                    ->button()
                    ->url(function (Order $record) {
                        $authId = \Illuminate\Support\Facades\Auth::id();
                        $customerId = $record->user_id;
                        $adminId = \App\Models\User::role('super_admin')->first()?->id ?? 1;

                        // JIKA pesanan ini milik saya sendiri, chat ke diri sendiri.
                        // JIKA pesanan milik orang lain, chat ke orang tersebut.
                        $targetId = ($authId == $customerId) ? $adminId : $customerId;

                        // Cari atau buat Inbox secara otomatis
                        $inbox = \Jeddsaliba\FilamentMessages\Models\Inbox::query()
                            ->whereJsonContains('user_ids', (int) $authId)
                            ->whereJsonContains('user_ids', (int) $targetId)
                            ->get()
                            ->first(function ($inbox) use ($authId, $targetId) {
                                $ids = collect($inbox->user_ids)->unique();

                                return $ids->contains($authId) && $ids->contains($targetId) && $ids->count() <= 2;
                            });

                        if (! $inbox) {
                            $inbox = \Jeddsaliba\FilamentMessages\Models\Inbox::create([
                                'user_ids' => collect([(int) $authId, (int) $targetId])->unique()->values()->toArray(),
                                'title' => 'Diskusi Order #'.$record->order_number,
                            ]);

                            // Pesan Pembuka Otomatis
                            \Jeddsaliba\FilamentMessages\Models\Message::create([
                                'inbox_id' => $inbox->id,
                                'user_id' => $authId,
                                'message' => "Halo, saya ingin mendiskusikan Pesanan #{$record->order_number}.",
                                'read_by' => [$authId],
                            ]);
                        }

                        $slug = config('filament-messages.slug', 'filament-messages');

                        return "/admin/{$slug}/".$inbox->id;
                    }),
                Tables\Actions\ViewAction::make()
                    ->button()
                    ->color('info')
                    ->size('lg'),
                Tables\Actions\EditAction::make()
                    ->button()
                    ->color('warning')
                    ->size('lg')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Order updated')
                            ->body('The order has been updated successfully.')
                    ),
                Tables\Actions\DeleteAction::make()
                    ->button()
                    ->color('danger')
                    ->size('lg')
                    ->successNotification(
                        Notification::make()
                            ->success()
                            ->title('Order deleted')
                            ->body('The order has been deleted successfully.')
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
