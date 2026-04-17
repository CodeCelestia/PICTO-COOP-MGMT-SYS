<script setup lang="ts">
import { Head, router, usePage } from '@inertiajs/vue3';
import { ChevronLeft, ChevronRight, Megaphone, Sparkles } from 'lucide-vue-next';
import { computed, onBeforeUnmount, onMounted, ref, watch } from 'vue';
import AppLayout from '@/layouts/AppLayout.vue';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardDescription, CardHeader, CardTitle } from '@/components/ui/card';
import { Separator } from '@/components/ui/separator';
import {
    HOMEPAGE_DISPLAY_STORAGE_KEY,
    HOMEPAGE_DISPLAY_SYNC_EVENT,
    normalizeHomepageDisplayPayload,
    publishHomepageDisplayPayload,
    readHomepageDisplayPayloadFromStorage,
    type HomepageCarouselPhoto,
    type HomepageDisplayPayload,
    type HomepageWhatsNewEntry,
} from '@/composables/useHomepageDisplaySync';
import type { BreadcrumbItem } from '@/types';

interface Props {
    carouselPhotos: HomepageCarouselPhoto[];
    whatsNewEntries: HomepageWhatsNewEntry[];
    syncedAt: string;
}

type AuthShape = {
    user?: {
        name?: string | null;
    };
};

type ParsedWhatsNewEntry = HomepageWhatsNewEntry & {
    introLines: string[];
    bulletLines: string[];
};

const props = defineProps<Props>();
const page = usePage();

const breadcrumbs: BreadcrumbItem[] = [
    {
        title: 'Homepage',
        href: '/homepage',
    },
];

const carouselPhotos = ref<HomepageCarouselPhoto[]>(props.carouselPhotos || []);
const whatsNewEntries = ref<HomepageWhatsNewEntry[]>(props.whatsNewEntries || []);
const syncedAt = ref<string>(props.syncedAt || new Date().toISOString());

const currentIndex = ref(0);
const isHoveringCarousel = ref(false);
const manilaNow = ref(new Date());
let carouselInterval: number | null = null;
let dateTimeInterval: number | null = null;
let homepagePollingInterval: number | null = null;

const userName = computed(() => {
    const auth = (page.props.auth as AuthShape | undefined) || {};
    const rawName = auth.user?.name;

    if (typeof rawName !== 'string') {
        return 'User';
    }

    const trimmedName = rawName.trim();
    return trimmedName.length > 0 ? trimmedName : 'User';
});

const welcomeBackText = computed(() => `Welcome Back, ${userName.value}`);

const philippineDateTime = computed(() => {
    const datePart = new Intl.DateTimeFormat('en-US', {
        weekday: 'long',
        month: 'long',
        day: '2-digit',
        year: 'numeric',
        timeZone: 'Asia/Manila',
    }).format(manilaNow.value);

    const timePart = new Intl.DateTimeFormat('en-US', {
        hour: '2-digit',
        minute: '2-digit',
        second: '2-digit',
        hour12: true,
        timeZone: 'Asia/Manila',
    }).format(manilaNow.value);

    return `${datePart}  |  ${timePart}`;
});

const visibleCarouselPhotos = computed(() => {
    return carouselPhotos.value
        .filter((photo) => photo.isEnabled)
        .sort((a, b) => {
            if (a.isDefault !== b.isDefault) {
                return Number(b.isDefault) - Number(a.isDefault);
            }

            return new Date(b.uploadedAt || 0).getTime() - new Date(a.uploadedAt || 0).getTime();
        });
});

const hasMultipleSlides = computed(() => visibleCarouselPhotos.value.length > 1);
const activeCarouselPhoto = computed(() => visibleCarouselPhotos.value[currentIndex.value] || null);

const sortedWhatsNewEntries = computed(() => {
    return [...whatsNewEntries.value].sort((a, b) => {
        return new Date(b.createdAt || 0).getTime() - new Date(a.createdAt || 0).getTime();
    });
});

const parseDescriptionLines = (description: string) => {
    const normalizedLines = description
        .split(/\r?\n/)
        .map((line) => line.trim())
        .filter((line) => line.length > 0);

    const introLines: string[] = [];
    const bulletLines: string[] = [];

    normalizedLines.forEach((line) => {
        if (/^[•*-]\s*/.test(line)) {
            bulletLines.push(line.replace(/^[•*-]\s*/, ''));
            return;
        }

        introLines.push(line);
    });

    return { introLines, bulletLines };
};

const parsedWhatsNewEntries = computed<ParsedWhatsNewEntry[]>(() => {
    return sortedWhatsNewEntries.value.map((entry) => {
        const parsed = parseDescriptionLines(entry.description);

        return {
            ...entry,
            introLines: parsed.introLines,
            bulletLines: parsed.bulletLines,
        };
    });
});

const hydrateFromPayload = (payload: HomepageDisplayPayload) => {
    carouselPhotos.value = payload.carouselPhotos;
    whatsNewEntries.value = payload.whatsNewEntries;
    syncedAt.value = payload.syncedAt;
};

const hydrateFromStorageIfNewer = () => {
    const storedPayload = readHomepageDisplayPayloadFromStorage();
    if (!storedPayload) {
        return;
    }

    const incoming = new Date(storedPayload.syncedAt).getTime();
    const current = new Date(syncedAt.value).getTime();

    if (Number.isFinite(incoming) && incoming >= current) {
        hydrateFromPayload(storedPayload);
    }
};

const syncPayloadLocally = () => {
    publishHomepageDisplayPayload({
        carouselPhotos: carouselPhotos.value,
        whatsNewEntries: whatsNewEntries.value,
        syncedAt: syncedAt.value,
    });
};

const goToNextSlide = () => {
    if (!hasMultipleSlides.value) {
        return;
    }

    currentIndex.value = (currentIndex.value + 1) % visibleCarouselPhotos.value.length;
};

const goToPreviousSlide = () => {
    if (!hasMultipleSlides.value) {
        return;
    }

    currentIndex.value = (currentIndex.value - 1 + visibleCarouselPhotos.value.length) % visibleCarouselPhotos.value.length;
};

const goToSlide = (index: number) => {
    currentIndex.value = index;
};

const stopCarousel = () => {
    if (carouselInterval !== null) {
        window.clearInterval(carouselInterval);
        carouselInterval = null;
    }
};

const stopDateTimeTicker = () => {
    if (dateTimeInterval !== null) {
        window.clearInterval(dateTimeInterval);
        dateTimeInterval = null;
    }
};

const stopHomepagePolling = () => {
    if (homepagePollingInterval !== null) {
        window.clearInterval(homepagePollingInterval);
        homepagePollingInterval = null;
    }
};

const startCarousel = () => {
    stopCarousel();

    if (!hasMultipleSlides.value || isHoveringCarousel.value) {
        return;
    }

    carouselInterval = window.setInterval(() => {
        goToNextSlide();
    }, 5000);
};

const refreshHomepageData = () => {
    router.reload({
        only: ['carouselPhotos', 'whatsNewEntries', 'syncedAt'],
        onSuccess: () => {
            const payload = normalizeHomepageDisplayPayload({
                carouselPhotos: page.props.carouselPhotos,
                whatsNewEntries: page.props.whatsNewEntries,
                syncedAt: page.props.syncedAt,
            });

            if (payload) {
                hydrateFromPayload(payload);
            }
        },
    });
};

const startDateTimeTicker = () => {
    stopDateTimeTicker();

    dateTimeInterval = window.setInterval(() => {
        manilaNow.value = new Date();
    }, 1000);
};

const startHomepagePolling = () => {
    stopHomepagePolling();

    homepagePollingInterval = window.setInterval(() => {
        refreshHomepageData();
    }, 30000);
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

const onHomepageDisplayUpdated = (event: Event) => {
    const customEvent = event as CustomEvent<HomepageDisplayPayload>;
    const payload = normalizeHomepageDisplayPayload(customEvent.detail);
    if (!payload) {
        return;
    }

    hydrateFromPayload(payload);
};

const onStorageSynced = (event: StorageEvent) => {
    if (event.key !== HOMEPAGE_DISPLAY_STORAGE_KEY || !event.newValue) {
        return;
    }

    try {
        const payload = normalizeHomepageDisplayPayload(JSON.parse(event.newValue));
        if (!payload) {
            return;
        }

        hydrateFromPayload(payload);
    } catch {
        // Ignore malformed localStorage values.
    }
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

watch(
    visibleCarouselPhotos,
    (photos) => {
        if (photos.length === 0) {
            currentIndex.value = 0;
            return;
        }

        if (currentIndex.value >= photos.length) {
            currentIndex.value = 0;
        }
    },
    { deep: true },
);

watch(
    [hasMultipleSlides, isHoveringCarousel],
    () => {
        startCarousel();
    },
);

watch([carouselPhotos, whatsNewEntries, syncedAt], syncPayloadLocally, { deep: true });

onMounted(() => {
    hydrateFromStorageIfNewer();
    startCarousel();
    startDateTimeTicker();
    startHomepagePolling();

    window.addEventListener(HOMEPAGE_DISPLAY_SYNC_EVENT, onHomepageDisplayUpdated as EventListener);
    window.addEventListener('storage', onStorageSynced);
});

onBeforeUnmount(() => {
    stopCarousel();
    stopDateTimeTicker();
    stopHomepagePolling();

    window.removeEventListener(HOMEPAGE_DISPLAY_SYNC_EVENT, onHomepageDisplayUpdated as EventListener);
    window.removeEventListener('storage', onStorageSynced);
});
</script>

<template>
    <AppLayout :breadcrumbs="breadcrumbs">
        <Head title="Homepage" />

        <div class="space-y-6 p-4 sm:p-6">
            <Card class="flex min-h-150 flex-col overflow-hidden border border-border/70">
                <CardHeader class="space-y-1">
                    <CardTitle class="flex flex-col gap-1 text-base sm:flex-row sm:items-center sm:justify-between sm:gap-3 sm:text-xl">
                        <span>{{ welcomeBackText }}</span>
                        <span class="w-84 shrink-0 whitespace-nowrap text-left text-xs font-medium text-muted-foreground tabular-nums sm:w-100 sm:text-right sm:text-sm">
                            {{ philippineDateTime }}
                        </span>
                    </CardTitle>
                    <CardDescription>
                        Stay updated with cooperative highlights and latest announcements.
                    </CardDescription>
                </CardHeader>

                <CardContent class="flex-1 p-0">
                    <div
                        class="relative h-full min-h-120 w-full overflow-hidden bg-muted sm:min-h-125 lg:min-h-130"
                        @mouseenter="isHoveringCarousel = true"
                        @mouseleave="isHoveringCarousel = false"
                    >
                        <transition name="homepage-carousel-fade" mode="out-in">
                            <img
                                v-if="activeCarouselPhoto"
                                :key="`${activeCarouselPhoto.id}-${currentIndex}`"
                                :src="activeCarouselPhoto.imageUrl"
                                :alt="activeCarouselPhoto.filename"
                                class="absolute inset-0 h-full w-full object-cover object-center"
                            />

                            <div
                                v-else
                                key="carousel-empty"
                                class="flex h-full w-full items-center justify-center bg-muted px-6 text-center text-sm text-muted-foreground"
                            >
                                No enabled carousel photos available.
                            </div>
                        </transition>

                        <template v-if="hasMultipleSlides">
                            <Button
                                variant="secondary"
                                size="icon"
                                class="absolute top-1/2 left-3 -translate-y-1/2 rounded-full bg-white/85 text-slate-900 hover:bg-white"
                                @click="goToPreviousSlide"
                            >
                                <ChevronLeft class="h-4 w-4" />
                            </Button>

                            <Button
                                variant="secondary"
                                size="icon"
                                class="absolute top-1/2 right-3 -translate-y-1/2 rounded-full bg-white/85 text-slate-900 hover:bg-white"
                                @click="goToNextSlide"
                            >
                                <ChevronRight class="h-4 w-4" />
                            </Button>

                            <div class="absolute right-0 bottom-3 left-0 flex items-center justify-center gap-2">
                                <button
                                    v-for="(_, index) in visibleCarouselPhotos"
                                    :key="`dot-${index}`"
                                    type="button"
                                    class="h-2.5 w-2.5 rounded-full transition-all"
                                    :class="index === currentIndex ? 'bg-white' : 'bg-white/45 hover:bg-white/70'"
                                    @click="goToSlide(index)"
                                />
                            </div>
                        </template>
                    </div>
                </CardContent>
            </Card>

            <div class="grid gap-6 md:grid-cols-2">
                <Card class="border border-border/70">
                    <CardHeader>
                        <CardTitle class="text-lg sm:text-xl">About Coop SDN-MIS</CardTitle>
                    </CardHeader>

                    <CardContent>
                        <div class="flex items-start gap-4 sm:gap-5">
                            <img
                                src="/SDN_Logo.png"
                                alt="Coop SDN-MIS Logo"
                                class="h-24 w-24 shrink-0 object-contain sm:h-28 sm:w-28"
                                style="filter: drop-shadow(0 6px 12px rgba(15, 23, 42, 0.18));"
                            />

                            <div class="space-y-3">
                                <div>
                                    <h3 class="text-lg font-semibold text-foreground sm:text-xl">Coop SDN-MIS</h3>
                                    <p class="mt-1 text-sm leading-6 text-muted-foreground sm:text-base">
                                        Coop SDN-MIS is a cooperative management information system designed to support
                                        records management, operations monitoring, and service delivery across cooperative programs.
                                    </p>
                                </div>

                                <div class="space-y-2">
                                    <p class="text-sm text-muted-foreground">For assistance, contact us:</p>
                                    <div class="space-y-1">
                                        <p>
                                            <span class="text-sm text-muted-foreground">Phone:</span>
                                            <span class="ml-2 text-base text-foreground">(+63) 900-000-0000</span>
                                        </p>
                                        <p>
                                            <span class="text-sm text-muted-foreground">Email:</span>
                                            <span class="ml-2 text-base text-foreground">support@coop-sdnmis.gov.ph</span>
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card class="border border-border/70">
                    <CardHeader>
                        <div class="flex items-center gap-2">
                            <Megaphone class="h-5 w-5 text-primary" />
                            <CardTitle class="text-lg sm:text-xl">What's New</CardTitle>
                        </div>
                        <CardDescription>Latest improvements and release highlights.</CardDescription>
                    </CardHeader>

                    <CardContent>
                        <div class="max-h-90 space-y-3 overflow-y-auto pr-2">
                            <template v-if="parsedWhatsNewEntries.length">
                                <div v-for="(entry, index) in parsedWhatsNewEntries" :key="entry.id" class="space-y-2">
                                    <div class="flex items-center justify-between gap-2">
                                        <div class="inline-flex items-center gap-2 rounded-md bg-primary/10 px-2.5 py-1 text-primary">
                                            <Sparkles class="h-4 w-4" />
                                            <span class="text-base font-semibold sm:text-lg">Version {{ entry.version }}</span>
                                        </div>
                                        <span class="text-xs text-muted-foreground">{{ formatTimestamp(entry.updatedAt || entry.createdAt) }}</span>
                                    </div>

                                    <div class="space-y-2 text-sm leading-6 text-muted-foreground">
                                        <p v-for="(line, lineIndex) in entry.introLines" :key="`${entry.id}-intro-${lineIndex}`">{{ line }}</p>
                                        <ul v-if="entry.bulletLines.length" class="list-disc space-y-1 pl-5">
                                            <li v-for="(line, lineIndex) in entry.bulletLines" :key="`${entry.id}-bullet-${lineIndex}`">{{ line }}</li>
                                        </ul>
                                    </div>

                                    <Separator v-if="index < parsedWhatsNewEntries.length - 1" />
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

<style scoped>
.homepage-carousel-fade-enter-active,
.homepage-carousel-fade-leave-active {
    transition: opacity 0.35s ease;
}

.homepage-carousel-fade-enter-from,
.homepage-carousel-fade-leave-to {
    opacity: 0;
}
</style>
