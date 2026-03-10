<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property int $user_id
 * @property int $wedding_organizer_id
 * @property int|null $package_id
 * @property int $rating
 * @property string|null $comment
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\Package|null $package
 * @property-read \App\Models\User $user
 * @property-read \App\Models\WeddingOrganizer $weddingOrganizer
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereComment($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review wherePackageId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereRating($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereUpdatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereUserId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Review whereWeddingOrganizerId($value)
 * @mixin \Eloquent
 */
class Review extends Model
{
    protected $fillable = [
        'user_id',
        'wedding_organizer_id',
        'package_id',
        'rating',
        'comment',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function weddingOrganizer()
    {
        return $this->belongsTo(WeddingOrganizer::class);
    }

    public function package()
    {
        return $this->belongsTo(Package::class);
    }
}
