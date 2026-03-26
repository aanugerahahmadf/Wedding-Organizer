<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $package_id
 * @property string $order_number
 * @property float $total_price
 * @property string $status
 * @property string $payment_status
 * @property \Illuminate\Support\Carbon $booking_date
 * @property string|null $notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read mixed $event_date
 * @property-read Payment|null $latestPayment
 * @property-read Package $package
 * @property-read Collection<int, Payment> $payments
 * @property-read int|null $payments_count
 * @property-read User $user
 * @property-read WeddingOrganizer|null $weddingOrganizer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereBookingDate($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereOrderNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order wherePaymentStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Order whereTotalPrice($value)
 * @method static \App\Models\Order|null find(mixed $id, array|string $columns = ['*'])
 * @method static \App\Models\Order findOrFail(mixed $id, array|string $columns = ['*'])
 * @method static \App\Models\Order|null first(array|string $columns = ['*'])
 * @method static \App\Models\Order firstOrFail(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Collection<int, \App\Models\Order> get(array|string $columns = ['*'])
 * @property int $userId
 * @property int $packageId
 * @property string $orderNumber
 * @property numeric $totalPrice
 * @property string $paymentStatus
 * @property \Illuminate\Support\Carbon $bookingDate
 * @property \Illuminate\Support\Carbon|null $createdAt
 * @property \Illuminate\Support\Carbon|null $updatedAt
 * @property-read mixed $eventDate
 * @property-read int|null $paymentsCount
 * @property-read bool|null $paymentsExists
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Order whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Order whereUserId($value)
 * @mixin \Eloquent
 */
class Order extends Model
{
    protected $fillable = [
        'user_id',
        'package_id',
        'order_number',
        'total_price',
        'status',
        'payment_status',
        'booking_date',
        'notes',
    ];

    protected $casts = [
        'booking_date' => 'date',
        'total_price' => 'decimal:2',
        'status' => \App\Enums\OrderStatus::class,
        'payment_status' => \App\Enums\OrderPaymentStatus::class,
    ];

    protected $appends = ['event_date'];

    public function getEventDateAttribute()
    {
        return Carbon::parse($this->booking_date)->format('Y-m-d');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }

    public function weddingOrganizer()
    {
        return $this->hasOneThrough(
            WeddingOrganizer::class,
            Package::class,
            'id', // Foreign key on packages table...
            'id', // Foreign key on wedding_organizers table...
            'package_id', // Local key on orders table...
            'wedding_organizer_id' // Local key on packages table...
        );
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function latestPayment()
    {
        return $this->hasOne(Payment::class)->latestOfMany();
    }
}
