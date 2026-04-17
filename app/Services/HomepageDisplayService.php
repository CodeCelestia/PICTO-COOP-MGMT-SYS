<?php

namespace App\Services;

use App\Models\HomepageCarouselPhoto;
use App\Models\HomepageWhatsNewEntry;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class HomepageDisplayService
{
    public const CORE_PHOTO_PATH = 'Photo1.png';

    public function ensureCorePhotoExists(): HomepageCarouselPhoto
    {
        $corePhoto = HomepageCarouselPhoto::query()->firstOrCreate(
            [
                'path' => self::CORE_PHOTO_PATH,
                'is_core' => true,
            ],
            [
                'original_name' => self::CORE_PHOTO_PATH,
                'is_default' => true,
                'is_enabled' => true,
            ],
        );

        $this->ensureDefaultPhotoExists();

        return $corePhoto;
    }

    public function payload(): array
    {
        $this->ensureCorePhotoExists();

        return [
            'carouselPhotos' => $this->carouselPhotos(),
            'whatsNewEntries' => $this->whatsNewEntries(),
            'syncedAt' => now()->toIso8601String(),
        ];
    }

    public function carouselPhotos(): Collection
    {
        return HomepageCarouselPhoto::query()
            ->orderByDesc('is_default')
            ->orderByDesc('created_at')
            ->get()
            ->map(function (HomepageCarouselPhoto $photo) {
                return [
                    'id' => $photo->id,
                    'filename' => $photo->original_name,
                    'imageUrl' => $this->resolveImageUrl($photo),
                    'isDefault' => (bool) $photo->is_default,
                    'isEnabled' => (bool) $photo->is_enabled,
                    'isCore' => (bool) $photo->is_core,
                    'uploadedAt' => optional($photo->created_at)?->toIso8601String(),
                ];
            })
            ->values();
    }

    public function whatsNewEntries(): Collection
    {
        return HomepageWhatsNewEntry::query()
            ->orderByDesc('created_at')
            ->get()
            ->map(function (HomepageWhatsNewEntry $entry) {
                return [
                    'id' => $entry->id,
                    'version' => $entry->version,
                    'description' => $entry->description,
                    'createdAt' => optional($entry->created_at)?->toIso8601String(),
                    'updatedAt' => optional($entry->updated_at)?->toIso8601String(),
                ];
            })
            ->values();
    }

    public function setDefaultPhoto(HomepageCarouselPhoto $photo): void
    {
        HomepageCarouselPhoto::query()->update(['is_default' => false]);

        $photo->forceFill(['is_default' => true])->save();
    }

    public function ensureDefaultPhotoExists(): void
    {
        if (HomepageCarouselPhoto::query()->where('is_default', true)->exists()) {
            return;
        }

        $fallback = HomepageCarouselPhoto::query()
            ->where('is_enabled', true)
            ->orderByDesc('created_at')
            ->first();

        if (! $fallback) {
            $fallback = HomepageCarouselPhoto::query()
                ->where('is_core', true)
                ->first();
        }

        if (! $fallback) {
            return;
        }

        $fallback->forceFill(['is_default' => true])->save();
    }

    protected function resolveImageUrl(HomepageCarouselPhoto $photo): string
    {
        if ($photo->is_core) {
            return asset($photo->path);
        }

        return Storage::disk('public')->url($photo->path);
    }
}
