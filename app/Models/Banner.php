<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * @property int $id
 * @property string|null $title
 * @property string $image_url
 * @property string|null $link_url
 * @property bool $is_active
 * @property int $sort_order
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereIsActive($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereLinkUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereSortOrder($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Banner whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Banner extends Model
{
    protected $fillable = [
        'title',
        'image_url',
        'link_url',
        'is_active',
        'sort_order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];
}
