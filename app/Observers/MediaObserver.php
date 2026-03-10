<?php

namespace App\Observers;

use App\Models\WeddingOrganizer;
use App\Services\CBIRService;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class MediaObserver
{
    protected $cbirService;

    public function __construct(CBIRService $cbirService)
    {
        $this->cbirService = $cbirService;
    }

    public function created(Media $media)
    {
        if ($media->collection_name === 'gallery' && $media->model_type === WeddingOrganizer::class) {
            // Index the image for CBIR
            $this->cbirService->indexMedia($media);
        }
    }
}
