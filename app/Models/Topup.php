<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

/**
 * @property int $id
 * @property int $user_id
 * @property string $reference_number
 * @property float $amount
 * @property float $admin_fee
 * @property float $total_amount
 * @property string|null $payment_method
 * @property string $status
 * @property string|null $payment_url
 * @property string|null $snap_token
 * @property string|null $payment_proof
 * @property Carbon|null $paid_at
 * @property string|null $notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereAdminFee($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup wherePaidAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup wherePaymentMethod($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup wherePaymentProof($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup wherePaymentUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereReferenceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereSnapToken($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereStatus($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereTotalAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Topup whereUpdatedAt($value)
 * @method static \App\Models\Topup|null find(mixed $id, array|string $columns = ['*'])
 * @method static \App\Models\Topup findOrFail(mixed $id, array|string $columns = ['*'])
 * @method static \App\Models\Topup|null first(array|string $columns = ['*'])
 * @method static \App\Models\Topup firstOrFail(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Collection<int, \App\Models\Topup> get(array|string $columns = ['*'])
 * @property int $userId
 * @property string $referenceNumber
 * @property numeric $adminFee
 * @property numeric $totalAmount
 * @property string|null $paymentMethod
 * @property string|null $paymentUrl
 * @property string|null $snapToken
 * @property string|null $paymentProof
 * @property \Illuminate\Support\Carbon|null $paidAt
 * @property \Illuminate\Support\Carbon|null $createdAt
 * @property \Illuminate\Support\Carbon|null $updatedAt
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Topup whereUserId($value)
 * @mixin \Eloquent
 */
class Topup extends Model
{
    use HasFactory;

    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [
        'user_id',
        'bank_id',
        'reference_number',
        'amount',
        'admin_fee',
        'total_amount',
        'payment_method',
        'status',
        'payment_url',
        'snap_token',
        'payment_proof',
        'paid_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'admin_fee' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'status' => \App\Enums\TopupStatus::class,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function bank(): BelongsTo
    {
        return $this->belongsTo(Bank::class);
    }

    protected static function boot()
    {
        parent::boot();
        static::creating(function ($model): void {
            if (! $model->reference_number) {
                $model->reference_number = 'TOPUP-'.strtoupper(Str::random(10));
            }
        });
    }
}
