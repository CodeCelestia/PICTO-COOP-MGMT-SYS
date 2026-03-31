import { router } from '@inertiajs/vue3';
import { computed, ref, watch, type ComputedRef } from 'vue';

export type CheckedState = boolean | 'indeterminate';

interface HasId {
    id: number;
}

export const useBulkSelection = <T extends HasId>(items: ComputedRef<T[]>) => {
    const selectedIds = ref<number[]>([]);

    const selectedIdSet = computed(() => new Set(selectedIds.value));
    const selectedCount = computed(() => selectedIds.value.length);

    const allVisibleSelected = computed(() =>
        items.value.length > 0 && items.value.every((item) => selectedIdSet.value.has(item.id)),
    );

    const isSelected = (id: number) => selectedIdSet.value.has(id);

    const toggleAll = (checked: CheckedState) => {
        if (checked === true) {
            selectedIds.value = items.value.map((item) => item.id);
            return;
        }

        selectedIds.value = [];
    };

    const toggleOne = (id: number, checked: CheckedState) => {
        if (checked === true) {
            if (!selectedIdSet.value.has(id)) {
                selectedIds.value = [...selectedIds.value, id];
            }
            return;
        }

        selectedIds.value = selectedIds.value.filter((selectedId) => selectedId !== id);
    };

    const clearSelection = () => {
        selectedIds.value = [];
    };

    watch(items, (nextItems) => {
        const visibleIds = new Set(nextItems.map((item) => item.id));
        selectedIds.value = selectedIds.value.filter((selectedId) => visibleIds.has(selectedId));
    });

    return {
        allVisibleSelected,
        clearSelection,
        isSelected,
        selectedCount,
        selectedIds,
        toggleAll,
        toggleOne,
    };
};

export const runBulkDelete = async (ids: number[], endpointForId: (id: number) => string) => {
    for (const id of ids) {
        await new Promise<void>((resolve) => {
            router.delete(endpointForId(id), {
                preserveState: true,
                preserveScroll: true,
                onFinish: () => resolve(),
            });
        });
    }
};
