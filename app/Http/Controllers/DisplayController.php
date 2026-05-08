<?php

namespace App\Http\Controllers;

use App\Models\HomepageCarouselPhoto;
use App\Models\HomepageWhatsNewEntry;
use App\Services\HomepageDisplayService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use Inertia\Response;

class DisplayController extends Controller
{
    public function __construct(
        protected HomepageDisplayService $homepageDisplayService,
    ) {
    }

    private function authorizePermission(string $permission): void
    {
        if (! auth()->user()?->can($permission)) {
            abort(403);
        }
    }

    public function index(): Response
    {
        $this->authorizePermission('view display');

        return Inertia::render('Display', array_merge($this->homepageDisplayService->payload(), [
            'canManageCarousel' => auth()->user()?->can('manage carousel'),
            'canManageWhatsNew' => auth()->user()?->can('manage whats new'),
        ]));
    }

    public function storeCarouselPhoto(Request $request): RedirectResponse
    {
        $this->authorizePermission('manage carousel');

        $validated = $request->validate([
            'photo' => ['required', 'file', 'mimes:jpg,jpeg,png,webp', 'max:5120'],
        ], [
            'photo.mimes' => 'Invalid file type. Allowed: JPG, JPEG, PNG, WEBP.',
            'photo.max' => 'File too large. Maximum upload size is 5MB.',
        ]);

        $file = $validated['photo'];
        $path = $file->store('homepage-carousel', 'public');

        $photo = HomepageCarouselPhoto::query()->create([
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'is_default' => false,
            'is_enabled' => true,
            'is_core' => false,
        ]);

        if (! HomepageCarouselPhoto::query()->where('is_default', true)->exists()) {
            $this->homepageDisplayService->setDefaultPhoto($photo);
        }

        return to_route('display.index')->with('success', 'Photo uploaded successfully.');
    }

    public function setDefaultCarouselPhoto(HomepageCarouselPhoto $carouselPhoto): RedirectResponse
    {
        $this->authorizePermission('manage carousel');

        $this->homepageDisplayService->setDefaultPhoto($carouselPhoto);

        return to_route('display.index')->with('success', 'Default photo updated successfully.');
    }

    public function toggleCarouselPhoto(HomepageCarouselPhoto $carouselPhoto): RedirectResponse
    {
        $this->authorizePermission('manage carousel');

        $carouselPhoto->forceFill([
            'is_enabled' => ! $carouselPhoto->is_enabled,
        ])->save();

        return to_route('display.index')->with('success', 'Photo visibility updated successfully.');
    }

    public function destroyCarouselPhoto(HomepageCarouselPhoto $carouselPhoto): RedirectResponse
    {
        $this->authorizePermission('manage carousel');

        if ($carouselPhoto->is_core) {
            return to_route('display.index')->with('error', 'Default photo cannot be deleted.');
        }

        $wasDefault = (bool) $carouselPhoto->is_default;

        Storage::disk('public')->delete($carouselPhoto->path);
        $carouselPhoto->delete();

        if ($wasDefault) {
            $this->homepageDisplayService->ensureDefaultPhotoExists();
        }

        return to_route('display.index')->with('success', 'Photo deleted successfully.');
    }

    public function storeWhatsNew(Request $request): RedirectResponse
    {
        $this->authorizePermission('manage whats new');

        $validated = $request->validate([
            'version' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        HomepageWhatsNewEntry::query()->create($validated);

        return to_route('display.index')->with('success', 'Update saved successfully.');
    }

    public function updateWhatsNew(Request $request, HomepageWhatsNewEntry $whatsNewEntry): RedirectResponse
    {
        $this->authorizePermission('manage whats new');

        $validated = $request->validate([
            'version' => ['required', 'string', 'max:120'],
            'description' => ['required', 'string', 'max:5000'],
        ]);

        $whatsNewEntry->update($validated);

        return to_route('display.index')->with('success', 'Update saved successfully.');
    }

    public function destroyWhatsNew(HomepageWhatsNewEntry $whatsNewEntry): RedirectResponse
    {
        $this->authorizePermission('manage whats new');

        $whatsNewEntry->delete();

        return to_route('display.index')->with('success', 'Update deleted successfully.');
    }
}
