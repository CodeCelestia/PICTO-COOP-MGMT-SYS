export interface HomepageCarouselPhoto {
    id: number;
    filename: string;
    imageUrl: string;
    isDefault: boolean;
    isEnabled: boolean;
    isCore: boolean;
    uploadedAt: string | null;
}

export interface HomepageWhatsNewEntry {
    id: number;
    version: string;
    description: string;
    createdAt: string | null;
    updatedAt: string | null;
}

export interface HomepageDisplayPayload {
    carouselPhotos: HomepageCarouselPhoto[];
    whatsNewEntries: HomepageWhatsNewEntry[];
    syncedAt: string;
}

export const HOMEPAGE_DISPLAY_SYNC_EVENT = 'homepage-display-updated';
export const HOMEPAGE_DISPLAY_STORAGE_KEY = 'homepage-display-payload-v1';

const isObjectRecord = (value: unknown): value is Record<string, unknown> => {
    return typeof value === 'object' && value !== null;
};

const toStringOrNull = (value: unknown): string | null => {
    return typeof value === 'string' ? value : null;
};

const toBoolean = (value: unknown): boolean => {
    return value === true;
};

const toNumber = (value: unknown): number => {
    return typeof value === 'number' ? value : 0;
};

const normalizeCarouselPhoto = (value: unknown): HomepageCarouselPhoto | null => {
    if (!isObjectRecord(value)) {
        return null;
    }

    return {
        id: toNumber(value.id),
        filename: typeof value.filename === 'string' ? value.filename : '',
        imageUrl: typeof value.imageUrl === 'string' ? value.imageUrl : '',
        isDefault: toBoolean(value.isDefault),
        isEnabled: toBoolean(value.isEnabled),
        isCore: toBoolean(value.isCore),
        uploadedAt: toStringOrNull(value.uploadedAt),
    };
};

const normalizeWhatsNewEntry = (value: unknown): HomepageWhatsNewEntry | null => {
    if (!isObjectRecord(value)) {
        return null;
    }

    return {
        id: toNumber(value.id),
        version: typeof value.version === 'string' ? value.version : '',
        description: typeof value.description === 'string' ? value.description : '',
        createdAt: toStringOrNull(value.createdAt),
        updatedAt: toStringOrNull(value.updatedAt),
    };
};

export const normalizeHomepageDisplayPayload = (value: unknown): HomepageDisplayPayload | null => {
    if (!isObjectRecord(value)) {
        return null;
    }

    const carouselPhotosRaw = Array.isArray(value.carouselPhotos) ? value.carouselPhotos : [];
    const whatsNewEntriesRaw = Array.isArray(value.whatsNewEntries) ? value.whatsNewEntries : [];
    const syncedAt = typeof value.syncedAt === 'string' ? value.syncedAt : new Date().toISOString();

    const carouselPhotos = carouselPhotosRaw
        .map((item) => normalizeCarouselPhoto(item))
        .filter((item): item is HomepageCarouselPhoto => item !== null);

    const whatsNewEntries = whatsNewEntriesRaw
        .map((item) => normalizeWhatsNewEntry(item))
        .filter((item): item is HomepageWhatsNewEntry => item !== null);

    return {
        carouselPhotos,
        whatsNewEntries,
        syncedAt,
    };
};

export const readHomepageDisplayPayloadFromStorage = (): HomepageDisplayPayload | null => {
    if (typeof window === 'undefined') {
        return null;
    }

    const raw = window.localStorage.getItem(HOMEPAGE_DISPLAY_STORAGE_KEY);
    if (!raw) {
        return null;
    }

    try {
        return normalizeHomepageDisplayPayload(JSON.parse(raw));
    } catch {
        return null;
    }
};

export const publishHomepageDisplayPayload = (payload: HomepageDisplayPayload) => {
    if (typeof window === 'undefined') {
        return;
    }

    window.localStorage.setItem(HOMEPAGE_DISPLAY_STORAGE_KEY, JSON.stringify(payload));
    window.dispatchEvent(new CustomEvent(HOMEPAGE_DISPLAY_SYNC_EVENT, { detail: payload }));
};
