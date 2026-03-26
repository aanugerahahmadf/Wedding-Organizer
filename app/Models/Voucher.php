<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property string $code
 * @property string|null $description
 * @property float $discount_amount
 * @property string $discount_type
 * @property float $min_purchase
 * @property Carbon|null $expires_at
 * @property bool $is_active
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereCode($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereDescription($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereDiscountAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereDiscountType($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereExpiresAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Voucher whereIsActive($value)
 * @method static \App\Models\Voucher|null find(mixed $id, array|string $columns = ['*'])
 * @method static \App\Models\Voucher findOrFail(mixed $id, array|string $columns = ['*'])
 * @method static \App\Models\Voucher|null first(array|string $columns = ['*'])
 * @method static \App\Models\Voucher firstOrFail(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Collection<int, \App\Models\Voucher> get(array|string $columns = ['*'])
 * @property numeric $discountAmount
 * @property string $discountType
 * @property numeric $minPurchase
 * @property \Illuminate\Support\Carbon|null $expiresAt
 * @property bool $isActive
 * @property \Illuminate\Support\Carbon|null $createdAt
 * @property \Illuminate\Support\Carbon|null $updatedAt
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Voucher whereMinPurchase($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Voucher whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Voucher extends Model
{
    protected $fillable = [
        'code',
        'description',
        'discount_amount',
        'discount_type',
        'min_purchase',
        'expires_at',
        'is_active',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
        'discount_amount' => 'decimal:2',
        'min_purchase' => 'decimal:2',
        'discount_type' => \App\Enums\DiscountType::class,
    ];
}
