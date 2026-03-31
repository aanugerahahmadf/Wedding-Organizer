<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $user_id
 * @property string $reference_number
 * @property float $amount
 * @property string $bank_name
 * @property string $account_number
 * @property string $account_holder
 * @property string $status
 * @property string|null $notes
 * @property string|null $admin_notes
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property-read User $user
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Withdrawal newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Withdrawal newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Withdrawal query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Withdrawal whereAccountHolder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Withdrawal whereAccountNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Withdrawal whereAdminNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Withdrawal whereAmount($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Withdrawal whereBankName($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Withdrawal whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Withdrawal whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Withdrawal whereNotes($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Withdrawal whereReferenceNumber($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Withdrawal whereStatus($value)
 * @method static \App\Models\Withdrawal|null find(mixed $id, array|string $columns = ['*'])
 * @method static \App\Models\Withdrawal findOrFail(mixed $id, array|string $columns = ['*'])
 * @method static \App\Models\Withdrawal|null first(array|string $columns = ['*'])
 * @method static \App\Models\Withdrawal firstOrFail(array|string $columns = ['*'])
 * @method static \Illuminate\Database\Eloquent\Collection<int, \App\Models\Withdrawal> get(array|string $columns = ['*'])
 * @property int $userId
 * @property string $referenceNumber
 * @property string $bankName
 * @property string $accountNumber
 * @property string $accountHolder
 * @property string|null $adminNotes
 * @property \Illuminate\Support\Carbon|null $createdAt
 * @property \Illuminate\Support\Carbon|null $updatedAt
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Withdrawal whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|\App\Models\Withdrawal whereUserId($value)
 * @mixin \Eloquent
 */
class Withdrawal extends Model
{
    protected $attributes = [
        'status' => 'pending',
    ];

    protected $fillable = [
        'user_id',
        'bank_id',
        'reference_number',
        'amount',
        'bank_name',
        'account_number',
        'account_holder',
        'status',
        'notes',
        'admin_notes',
    ];

    protected $casts = [
        'status' => \App\Enums\WithdrawalStatus::class,
        'amount' => 'decimal:2',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function bank()
    {
        return $this->belongsTo(Bank::class);
    }
}
