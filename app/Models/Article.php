<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;

/**
 * @property int $id
 * @property int $author_id
 * @property string $title
 * @property string $slug
 * @property string $content
 * @property string|null $image_url
 * @property string|null $video_url
 * @property bool $is_published
 * @property \Illuminate\Support\Carbon|null $published_at
 * @property \Illuminate\Support\Carbon|null $created_at
 * @property \Illuminate\Support\Carbon|null $updated_at
 * @property-read \App\Models\User $author
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article newModelQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article newQuery()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article query()
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereAuthorId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereContent($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereCreatedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereId($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereImageUrl($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereIsPublished($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article wherePublishedAt($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereSlug($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereTitle($value)
 * @method static \Illuminate\Database\Eloquent\Builder<static>|Article whereUpdatedAt($value)
 * @mixin \Eloquent
 */
class Article extends Model implements HasMedia
{
    use InteractsWithMedia;

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('article-images')->singleFile();
        $this->addMediaCollection('videos')->singleFile();
    }

    protected $fillable = [
        'author_id',
        'title',
        'slug',
        'content',
        'image_url',
        'video_url',
        'is_published',
        'published_at',
    ];

    protected $casts = [
        'is_published' => 'boolean',
        'published_at' => 'datetime',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    public function getMediaVideoUrlAttribute(): ?string
    {
        return $this->getFirstMediaUrl('videos') ?: null;
    }
}
