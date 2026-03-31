<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $order_id
 * @property string $payment_number
 * @property string $payment_method
 * @property string $status
 * @property float $amount
 * @property float $admin_fee
 * @property float $total_amount
 * @property string|null $payment_proof
 * @property Carbon|null $paid_at
 * @property Carbon|null $expired_at
 * @property Carbon|null $cancelled_at
 * @property string|null $notes
 * @property array<array-key, mixed>|null $metadata
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read string $payment_method_label
 * @property-read string $status_color
 * @property-read string $status_label
 * @property-read PaymentMethod|null $methodDetails
 * @property-read Order $order
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAdminFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCancelledAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereExpiredAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereMetadata($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereOrderId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment wherePaymentProof($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Payment whereStatus($value)
 * @method static \App\Models\Payment|null find(mixed $id, array|string $columns = ['*'])
 * @method static \App\Models\Payment findOrFail(mixed $id, array|string $columns = ['*'])
 * @method static \App\Models\Payment|null first(array|string $columns = ['*'])
 * @method static \App\Models\Payment firstOrFail(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Collection<int, \App\Models\Payment> get(array|string $columns = ['*'])
 * @property int $orderId
 * @property string $paymentNumber
 * @property string $paymentMethod
 * @property numeric $adminFee
 * @property numeric $totalAmount
 * @property string|null $paymentProof
 * @property \Illuminate\Support\Carbon|null $paidAt
 * @property \Illuminate\Support\Carbon|null $expiredAt
 * @property \Illuminate\Support\Carbon|null $cancelledAt
 * @property \Illuminate\Support\Carbon|null $createdAt
 * @property \Illuminate\Support\Carbon|null $updatedAt
 * @property-read string $paymentMethodLabel
 * @property-read string $statusColor
 * @property-read string $statusLabel
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Payment whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Payment whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Payment extends Model
{
    use HasFactory;

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [
        'order_id',
        'payment_number',
        'payment_method',
        'status',
        'amount',
        'admin_fee',
        'total_amount',
        'payment_proof',
        'paid_at',
        'expired_at',
        'cancelled_at',
        'notes',
        'metadata',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'expired_at' => 'datetime',
        'cancelled_at' => 'datetime',
        'metadata' => 'array',
        'status' => \App\Enums\PaymentStatus::class,
    ];

    /**
     * Payment method labels
     */
    public static function paymentMethodLabels(): array
    {
        return [
            'bank_transfer' => 'Transfer Bank',
            'credit_card' => 'Kartu Kredit',
            'gopay' => 'GoPay',
            'ovo' => 'OVO',
            'dana' => 'DANA',
            'shopeepay' => 'ShopeePay',
            'qris' => 'QRIS',
            'alfamart' => 'Alfamart',
            'indomaret' => 'Indomaret',
        ];
    }

    /**
     * Payment status labels
     */
    public static function statusLabels(): array
    {
        return [
            'pending' => 'Tertunda',
            'processing' => 'Diproses',
            'success' => 'Berhasil',
            'failed' => 'Gagal',
            'expired' => 'Kadaluarsa',
            'cancelled' => 'Dibatalkan',
            'refunded' => 'Dikembalikan',
        ];
    }

    /**
     * Payment status colors for UI
     */
    public static function statusColors(): array
    {
        return [
            'pending' => 'warning',
            'processing' => 'info',
            'success' => 'success',
            'failed' => 'danger',
            'expired' => 'gray',
            'cancelled' => 'gray',
            'refunded' => 'warning',
        ];
    }

    /**
     * Get the order that owns the payment
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Check if payment is successful
     */
    public function isSuccess(): bool
    {
        return $this->status === \App\Enums\PaymentStatus::SUCCESS || $this->status === 'success';
    }

    /**
     * Check if payment is pending
     */
    public function isPending(): bool
    {
        return $this->status === \App\Enums\PaymentStatus::PENDING || $this->status === 'pending';
    }

    /**
     * Check if payment is failed
     */
    public function isFailed(): bool
    {
        return in_array($this->status, [
            \App\Enums\PaymentStatus::FAILED, 
            \App\Enums\PaymentStatus::EXPIRED, 
            \App\Enums\PaymentStatus::CANCELLED,
            'failed', 'expired', 'cancelled'
        ], true);
    }

    /**
     * Mark payment as success
     */
    public function markAsSuccess(): void
    {
        $this->update([
            'status' => 'success',
            'paid_at' => now(),
        ]);

        // Update order payment status
        $this->order->update([
            'payment_status' => 'paid',
        ]);
    }

    /**
     * Mark payment as failed
     */
    public function markAsFailed(?string $reason = null): void
    {
        $this->update([
            'status' => 'failed',
            'notes' => $reason,
        ]);
    }

    /**
     * Get payment method label
     */
    public function getPaymentMethodLabelAttribute(): string
    {
        return self::paymentMethodLabels()[$this->payment_method] ?? $this->payment_method;
    }

    /**
     * Get status label
     */
    public function getStatusLabelAttribute(): string
    {
        return self::statusLabels()[$this->status] ?? $this->status;
    }

    /**
     * Get status color
     */
    public function getStatusColorAttribute(): string
    {
        return self::statusColors()[$this->status] ?? 'gray';
    }

    /**
     * Get the payment method details
     */
    public function methodDetails(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class, 'payment_method', 'code');
    }
}
