<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property string $reference_number
 * @property numeric $amount
 * @property string $bank_name
 * @property string $account_number
 * @property string $account_holder
 * @property string $status
 * @property string|null $notes
 * @property string|null $admin_notes
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $user
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
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Withdrawal whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Withdrawal whereUserId($value)
 * @mixin \Eloquent
 */
class Withdrawal extends Model
{
    protected $fillable = [
        'user_id',
        'reference_number',
        'amount',
        'bank_name',
        'account_number',
        'account_holder',
        'status',
        'notes',
        'admin_notes',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
