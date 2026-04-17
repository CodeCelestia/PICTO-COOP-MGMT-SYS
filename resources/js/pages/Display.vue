<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { Eye, EyeOff, Pencil, Star, Trash2, Upload } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Separator } from '@/components/ui/separator';
import { Textarea } from '@/components/ui/textarea';
import {
    publishHomepageDisplayPayload,
    type HomepageCarouselPhoto,
    type HomepageWhatsNewEntry,
} from '@/composables/useHomepageDisplaySync';
import { confirmAction, notifyError, notifySuccess } from '@/lib/alerts';
import type { BreadcrumbItem } from '@/types';

interface Props {
    carouselPhotos: HomepageCarouselPhoto[];
    whatsNewEntries: HomepageWhatsNewEntry[];
    syncedAt: string;
}

type PagePayload = {
    props: Record<string, unknown>;
};

const props = defineProps<Props>();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Display',
        href: '/display',
    },
];

const page = usePage();
const validationErrors = computed<Record<string, string>>(() => (page.props.errors as Record<string, string>) || {});

const carouselPhotos = ref<HomepageCarouselPhoto[]>(props.carouselPhotos || []);
const whatsNewEntries = ref<HomepageWhatsNewEntry[]>(props.whatsNewEntries || []);
const syncedAt = ref<string>(props.syncedAt || new Date().toISOString());

const versionInput = ref('');
const descriptionInput = ref('');
const editingEntryId = ref<number | null>(null);

const fileInputRef = ref<HTMLInputElement | null>(null);
const isUploadingPhoto = ref(false);
const isSavingEntry = ref(false);

const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/jpg', 'image/png', 'image/webp'];
const MAX_FILE_SIZE_BYTES = 2 * 1024 * 1024;

const sortedCarouselPhotos = computed(() => {
    return [...carouselPhotos.value].sort((a, b) => {
        if (a.isDefault !== b.isDefault) {
            return Number(b.isDefault) - Number(a.isDefault);
        }

        return new Date(b.uploadedAt || 0).getTime() - new Date(a.uploadedAt || 0).getTime();
    });
});

const sortedWhatsNewEntries = computed(() => {
    return [...whatsNewEntries.value].sort((a, b) => {
        return new Date(b.createdAt || 0).getTime() - new Date(a.createdAt || 0).getTime();
    });
});

const isEditingEntry = computed(() => editingEntryId.value !== null);

const applyServerPayload = (payload: Record<string, unknown>) => {
    const nextCarouselPhotos = Array.isArray(payload.carouselPhotos) ? (payload.carouselPhotos as HomepageCarouselPhoto[]) : [];
    const nextWhatsNewEntries = Array.isArray(payload.whatsNewEntries) ? (payload.whatsNewEntries as HomepageWhatsNewEntry[]) : [];
    const nextSyncedAt = typeof payload.syncedAt === 'string' ? payload.syncedAt : new Date().toISOString();

    carouselPhotos.value = nextCarouselPhotos;
    whatsNewEntries.value = nextWhatsNewEntries;
    syncedAt.value = nextSyncedAt;

    publishHomepageDisplayPayload({
        carouselPhotos: nextCarouselPhotos,
        whatsNewEntries: nextWhatsNewEntries,
        syncedAt: nextSyncedAt,
    });
};

const clearEntryForm = () => {
    editingEntryId.value = null;
    versionInput.value = '';
    descriptionInput.value = '';
};

const formatTimestamp = (value: string | null): string => {
    if (!value) {
        return 'Just now';
    }

    const date = new Date(value);
    if (Number.isNaN(date.getTime())) {
        return 'Just now';
    }

    return new Intl.DateTimeFormat('en-US', {
        month: 'short',
        day: 'numeric',
        year: 'numeric',
    }).format(date);
};

const triggerUpload = () => {
    fileInputRef.value?.click();
};

const clearSelectedFile = () => {
    if (fileInputRef.value) {
        fileInputRef.value.value = '';
    }
};

const handleDescriptionKeydown = (event: KeyboardEvent) => {
    if (event.key !== 'Enter') {
        return;
    }

    event.preventDefault();

    const target = event.target as HTMLTextAreaElement | null;
    if (!target) {
        return;
    }

    const start = target.selectionStart;
    const end = target.selectionEnd;
    const currentValue = descriptionInput.value;

    const before = currentValue.slice(0, start);
    const after = currentValue.slice(end);
    const lineIndex = before.split('\n').length - 1;
    const firstLineHasContent = ((currentValue.split('\n')[0] || '').trim().length > 0);
    const shouldInsertBullet = lineIndex > 0 || firstLineHasContent;
    const insertion = shouldInsertBullet ? '\n• ' : '\n';

    descriptionInput.value = `${before}${insertion}${after}`;

    const nextCursor = start + insertion.length;

    requestAnimationFrame(() => {
        target.selectionStart = nextCursor;
        target.selectionEnd = nextCursor;
    });
};

const getPhotoValidationMessage = (file: File): string | null => {
    if (!ALLOWED_MIME_TYPES.includes(file.type.toLowerCase())) {
        return 'Invalid file type. Allowed: JPG, JPEG, PNG, WEBP.';
    }

    if (file.size > MAX_FILE_SIZE_BYTES) {
        return 'File too large. Max size is 2MB.';
    }

    return null;
};

const handlePhotoSelected = (event: Event) => {
    const target = event.target as HTMLInputElement;
    const file = target.files?.[0];

    if (!file) {
        return;
    }

    const validationMessage = getPhotoValidationMessage(file);
    if (validationMessage) {
        notifyError(validationMessage);
        clearSelectedFile();
        return;
    }

    isUploadingPhoto.value = true;

    router.post(
        '/display/carousel-photos',
        {
            photo: file,
        },
        {
            forceFormData: true,
            preserveScroll: true,
            onSuccess: (responsePage: PagePayload) => {
                applyServerPayload(responsePage.props);
                notifySuccess('Photo uploaded successfully.');
            },
            onError: (errors) => {
                const fileError = errors.photo;
                notifyError(fileError || 'Invalid file type. Allowed: JPG, JPEG, PNG, WEBP.');
            },
            onFinish: () => {
                isUploadingPhoto.value = false;
                clearSelectedFile();
            },
        },
    );
};

const setDefaultPhoto = (photo: HomepageCarouselPhoto) => {
    if (photo.isDefault) {
        return;
    }

    router.patch(
        `/display/carousel-photos/${photo.id}/default`,
        {},
        {
            preserveScroll: true,
            onSuccess: (responsePage: PagePayload) => {
                applyServerPayload(responsePage.props);
                notifySuccess('Default photo updated successfully.');
            },
            onError: () => {
                notifyError('Unable to update default photo.');
            },
        },
    );
};

const togglePhotoEnabled = (photo: HomepageCarouselPhoto) => {
    router.patch(
        `/display/carousel-photos/${photo.id}/toggle`,
        {},
        {
            preserveScroll: true,
            onSuccess: (responsePage: PagePayload) => {
                applyServerPayload(responsePage.props);
                notifySuccess(photo.isEnabled ? 'Photo disabled successfully.' : 'Photo enabled successfully.');
            },
            onError: () => {
                notifyError('Unable to update photo visibility.');
            },
        },
    );
};

const deletePhoto = async (photo: HomepageCarouselPhoto) => {
    if (photo.isCore) {
        notifyError('Default photo cannot be deleted.');
        return;
    }

    const confirmed = await confirmAction({
        title: 'Delete photo?',
        text: 'This photo will be permanently removed.',
        confirmButtonText: 'Delete',
    });

    if (!confirmed) {
        return;
    }

    router.delete(`/display/carousel-photos/${photo.id}`, {
        preserveScroll: true,
        onSuccess: (responsePage: PagePayload) => {
            applyServerPayload(responsePage.props);
            notifySuccess('Photo deleted successfully.');
        },
        onError: () => {
            notifyError('Unable to delete photo.');
        },
    });
};

const editEntry = (entry: HomepageWhatsNewEntry) => {
    editingEntryId.value = entry.id;
    versionInput.value = entry.version;
    descriptionInput.value = entry.description;
};

const saveEntry = () => {
    if (!versionInput.value.trim() || !descriptionInput.value.trim()) {
        notifyError('Version and description are required.');
        return;
    }

    isSavingEntry.value = true;

    const payload = {
        version: versionInput.value.trim(),
        description: descriptionInput.value.trim(),
    };

    if (editingEntryId.value) {
        router.put(`/display/whats-new/${editingEntryId.value}`, payload, {
            preserveScroll: true,
            onSuccess: (responsePage: PagePayload) => {
                applyServerPayload(responsePage.props);
                notifySuccess('Update saved successfully.');
                clearEntryForm();
            },
            onError: (errors) => {
                const versionError = errors.version;
                const descriptionError = errors.description;
                notifyError(versionError || descriptionError || 'Unable to save update.');
            },
            onFinish: () => {
                isSavingEntry.value = false;
            },
        });

        return;
    }

    router.post('/display/whats-new', payload, {
        preserveScroll: true,
        onSuccess: (responsePage: PagePayload) => {
            applyServerPayload(responsePage.props);
            notifySuccess('Update saved successfully.');
            clearEntryForm();
        },
        onError: (errors) => {
            const versionError = errors.version;
            const descriptionError = errors.description;
            notifyError(versionError || descriptionError || 'Unable to save update.');
        },
        onFinish: () => {
            isSavingEntry.value = false;
        },
    });
};

const deleteEntry = async (entry: HomepageWhatsNewEntry) => {
    const confirmed = await confirmAction({
        title: 'Delete update?',
        text: `Version ${entry.version} will be removed from Homepage.`,
        confirmButtonText: 'Delete',
    });

    if (!confirmed) {
        return;
    }

    router.delete(`/display/whats-new/${entry.id}`, {
        preserveScroll: true,
        onSuccess: (responsePage: PagePayload) => {
            applyServerPayload(responsePage.props);
            notifySuccess('Update deleted successfully.');
            if (editingEntryId.value === entry.id) {
                clearEntryForm();
            }
        },
        onError: () => {
            notifyError('Unable to delete update.');
        },
    });
};

watch(
    () => props.carouselPhotos,
    (nextValue) => {
        carouselPhotos.value = nextValue || [];
    },
    { deep: true },
);

watch(
    () => props.whatsNewEntries,
    (nextValue) => {
        whatsNewEntries.value = nextValue || [];
    },
    { deep: true },
);

watch(
    () => props.syncedAt,
    (nextValue) => {
        syncedAt.value = nextValue || new Date().toISOString();
    },
);
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Display" />

        <div class="space-y-6 p-4 sm:p-6">
            <Card class="border border-border/70">
                <CardHeader>
                    <CardTitle class="text-xl sm:text-2xl">Homepage Display Settings</CardTitle>
                    <CardDescription>
                        Manage carousel photos and update cards shown on Homepage.
                    </CardDescription>
                </CardHeader>
            </Card>

            <div class="grid gap-6 xl:grid-cols-2">
                <Card class="border border-border/70">
                    <CardHeader class="space-y-3">
                        <div class="flex flex-wrap items-center justify-between gap-3">
                            <div>
                                <CardTitle>Carousel Photo Management</CardTitle>
                                <CardDescription>Upload and organize homepage carousel photos.</CardDescription>
                            </div>

                            <Button class="gap-2" :disabled="isUploadingPhoto" @click="triggerUpload">
                                <Upload class="h-4 w-4" />
                                {{ isUploadingPhoto ? 'Uploading...' : 'Upload Photo' }}
                            </Button>
                        </div>

                        <input
                            ref="fileInputRef"
                            type="file"
                            accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp"
                            class="hidden"
                            @change="handlePhotoSelected"
                        />

                        <p class="text-xs text-muted-foreground">
                            Accepted formats: JPG, JPEG, PNG, WEBP. Maximum size: 2MB.
                        </p>
                        <p class="text-xs text-muted-foreground">
                            Recommended image size: 1280×480px or wider (landscape orientation) for best display on the carousel.
                        </p>
                    </CardHeader>

                    <CardContent>
                        <div class="max-h-130 space-y-4 overflow-y-auto pr-2">
                            <template v-if="sortedCarouselPhotos.length">
                                <div v-for="photo in sortedCarouselPhotos" :key="photo.id" class="rounded-lg border border-border/70 p-3">
                                    <div class="grid gap-3 sm:grid-cols-[148px_1fr]">
                                        <img
                                            :src="photo.imageUrl"
                                            :alt="photo.filename"
                                            class="h-28 w-full rounded-md border border-border/60 object-cover sm:h-24"
                                        />

                                        <div class="space-y-3">
                                            <div class="space-y-1">
                                                <div class="flex flex-wrap items-center gap-2">
                                                    <p class="line-clamp-1 text-sm font-medium">{{ photo.filename }}</p>
                                                    <Badge v-if="photo.isDefault" class="bg-amber-500 text-white hover:bg-amber-500">
                                                        Default
                                                    </Badge>
                                                    <Badge v-if="photo.isCore" variant="secondary">
                                                        Core
                                                    </Badge>
                                                    <Badge :variant="photo.isEnabled ? 'default' : 'secondary'">
                                                        {{ photo.isEnabled ? 'Enabled' : 'Disabled' }}
                                                    </Badge>
                                                </div>
                                                <p class="text-xs text-muted-foreground">Uploaded {{ formatTimestamp(photo.uploadedAt) }}</p>
                                            </div>

                                            <div class="flex flex-wrap gap-2">
                                                <Button
                                                    size="sm"
                                                    variant="outline"
                                                    class="gap-1.5"
                                                    :disabled="photo.isDefault"
                                                    @click="setDefaultPhoto(photo)"
                                                >
                                                    <Star class="h-3.5 w-3.5" />
                                                    Set Default
                                                </Button>

                                                <Button size="sm" variant="outline" class="gap-1.5" @click="togglePhotoEnabled(photo)">
                                                    <EyeOff v-if="photo.isEnabled" class="h-3.5 w-3.5" />
                                                    <Eye v-else class="h-3.5 w-3.5" />
                                                    {{ photo.isEnabled ? 'Disable' : 'Enable' }}
                                                </Button>

                                                <Button
                                                    size="sm"
                                                    variant="destructive"
                                                    class="gap-1.5"
                                                    :disabled="photo.isCore"
                                                    @click="deletePhoto(photo)"
                                                >
                                                    <Trash2 class="h-3.5 w-3.5" />
                                                    Delete
                                                </Button>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </template>

                            <div v-else class="rounded-md border border-dashed border-border px-4 py-6 text-center text-sm text-muted-foreground">
                                No photos uploaded yet.
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border border-border/70">
                    <CardHeader>
                        <CardTitle>What's New Management</CardTitle>
                        <CardDescription>Add, edit, and remove updates shown on Homepage.</CardDescription>
                    </CardHeader>

                    <CardContent class="space-y-5">
                        <div class="space-y-3 rounded-lg border border-border/70 p-4">
                            <div>
                                <Label for="display_version">Version</Label>
                                <Input id="display_version" v-model="versionInput" class="mt-1" placeholder="e.g., 5.2.1" />
                                <p v-if="validationErrors.version" class="mt-1 text-xs text-red-600">{{ validationErrors.version }}</p>
                            </div>

                            <div>
                                <Label for="display_description">Description</Label>
                                <Textarea
                                    id="display_description"
                                    v-model="descriptionInput"
                                    class="mt-1 min-h-27.5"
                                    placeholder="Describe the update shown on Homepage"
                                    @keydown="handleDescriptionKeydown"
                                />
                                <p v-if="validationErrors.description" class="mt-1 text-xs text-red-600">{{ validationErrors.description }}</p>
                            </div>

                            <div class="flex flex-wrap gap-2">
                                <Button class="gap-2" :disabled="isSavingEntry" @click="saveEntry">
                                    <Pencil class="h-4 w-4" />
                                    {{ isSavingEntry ? 'Saving...' : isEditingEntry ? 'Save Changes' : 'Add Update' }}
                                </Button>

                                <Button v-if="isEditingEntry" variant="outline" @click="clearEntryForm">
                                    Cancel Edit
                                </Button>
                            </div>
                        </div>

                        <div class="max-h-90 space-y-3 overflow-y-auto pr-2">
                            <template v-if="sortedWhatsNewEntries.length">
                                <div v-for="(entry, index) in sortedWhatsNewEntries" :key="entry.id" class="space-y-3">
                                    <div class="flex items-start justify-between gap-3">
                                        <div>
                                            <p class="text-lg font-semibold">Version {{ entry.version }}</p>
                                            <p class="text-xs text-muted-foreground">{{ formatTimestamp(entry.updatedAt || entry.createdAt) }}</p>
                                        </div>

                                        <div class="flex gap-2">
                                            <Button size="sm" variant="outline" class="gap-1" @click="editEntry(entry)">
                                                <Pencil class="h-3.5 w-3.5" />
                                                Edit
                                            </Button>
                                            <Button size="sm" variant="destructive" class="gap-1" @click="deleteEntry(entry)">
                                                <Trash2 class="h-3.5 w-3.5" />
                                                Delete
                                            </Button>
                                        </div>
                                    </div>

                                    <p class="text-sm leading-6 text-muted-foreground">{{ entry.description }}</p>

                                    <Separator v-if="index < sortedWhatsNewEntries.length - 1" />
                                </div>
                            </template>

                            <div v-else class="rounded-md border border-dashed border-border px-4 py-6 text-center text-sm text-muted-foreground">
                                No updates posted yet.
                            </div>
                        </div>
                    </CardContent>
                </Card>
            </div>
        </div>
    </AppLayout>
</template>
