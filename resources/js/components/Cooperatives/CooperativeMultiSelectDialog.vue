<script setup lang="ts">
import { computed, ref, watch } from 'vue';
import { Check, Search } from 'lucide-vue-next';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import { Checkbox } from '@/components/ui/checkbox';
import {
    Dialog,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { ScrollArea } from '@/components/ui/scroll-area';
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';

interface CooperativeOption {
    id: number;
    name: string;
    registration_number?: string | null;
    status?: string | null;
    region?: string | null;
    classification?: string | null;
}

interface Props {
    open: boolean;
    cooperatives: CooperativeOption[];
    selectedIds: Array<string | number>;
    title?: string;
    description?: string;
    confirmLabel?: string;
    cancelLabel?: string;
}

const props = withDefaults(defineProps<Props>(), {
    title: 'Choose Cooperative',
    description: 'Search and filter cooperatives, then select one or more entries.',
    confirmLabel: 'Confirm',
    cancelLabel: 'Cancel',
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm', value: string[]): void;
}>();

const searchQuery = ref('');
const statusFilter = ref('all');
const regionFilter = ref('all');
const classificationFilter = ref('all');
const draftSelectedIds = ref<string[]>([]);

const normalizeId = (id: string | number) => String(id);

watch(() => props.open, (isOpen) => {
    if (!isOpen) return;

    draftSelectedIds.value = props.selectedIds.map(normalizeId);
    searchQuery.value = '';
    statusFilter.value = 'all';
    regionFilter.value = 'all';
    classificationFilter.value = 'all';
});

const sortedCooperatives = computed(() =>
    [...props.cooperatives].sort((a, b) => a.name.localeCompare(b.name)),
);

const statusOptions = computed(() => {
    const statuses = sortedCooperatives.value
        .map((coop) => (coop.status || '').trim())
        .filter(Boolean);

    return Array.from(new Set(statuses)).sort((a, b) => a.localeCompare(b));
});

const regionOptions = computed(() => {
    const regions = sortedCooperatives.value
        .map((coop) => (coop.region || '').trim())
        .filter(Boolean);

    return Array.from(new Set(regions)).sort((a, b) => a.localeCompare(b));
});

const classificationOptions = computed(() => {
    const classifications = sortedCooperatives.value
        .map((coop) => (coop.classification || '').trim())
        .filter(Boolean);

    return Array.from(new Set(classifications)).sort((a, b) => a.localeCompare(b));
});

const filteredCooperatives = computed(() => {
    const query = searchQuery.value.trim().toLowerCase();

    return sortedCooperatives.value.filter((coop) => {
        const matchesSearch = query === ''
            || coop.name.toLowerCase().includes(query)
            || String(coop.id).includes(query)
            || (coop.registration_number || '').toLowerCase().includes(query);

        if (!matchesSearch) {
            return false;
        }

        const matchesStatus = statusFilter.value === 'all' || (coop.status || '') === statusFilter.value;
        if (!matchesStatus) {
            return false;
        }

        const matchesClassification = classificationFilter.value === 'all'
            || String(coop.classification || '') === classificationFilter.value;

        if (!matchesClassification) {
            return false;
        }

        return regionFilter.value === 'all' || (coop.region || '') === regionFilter.value;
    });
});

const visibleIds = computed(() => filteredCooperatives.value.map((coop) => String(coop.id)));
const hasVisible = computed(() => visibleIds.value.length > 0);

const allVisibleSelected = computed(() => (
    hasVisible.value
    && visibleIds.value.every((id) => draftSelectedIds.value.includes(id))
));

const selectedCountLabel = computed(() => {
    const count = draftSelectedIds.value.length;
    return `${count} cooperative${count === 1 ? '' : 's'} selected`;
});

const isSelected = (id: string | number) => draftSelectedIds.value.includes(normalizeId(id));

const toggleCooperative = (id: string | number, checked: boolean | 'indeterminate') => {
    const coopId = normalizeId(id);

    if (checked === true) {
        if (!draftSelectedIds.value.includes(coopId)) {
            draftSelectedIds.value.push(coopId);
        }
        return;
    }

    draftSelectedIds.value = draftSelectedIds.value.filter((selectedId) => selectedId !== coopId);
};

const toggleAllVisible = (checked: boolean | 'indeterminate') => {
    const visibleSet = new Set(visibleIds.value);

    if (checked === true) {
        const merged = new Set([...draftSelectedIds.value, ...visibleIds.value]);
        draftSelectedIds.value = Array.from(merged);
        return;
    }

    draftSelectedIds.value = draftSelectedIds.value.filter((id) => !visibleSet.has(id));
};

const closeDialog = () => {
    emit('update:open', false);
};

const confirmSelection = () => {
    emit('confirm', [...draftSelectedIds.value]);
    emit('update:open', false);
};

const getStatusBadgeClass = (status: string | null | undefined) => {
    const normalized = String(status || '').trim().toLowerCase();

    if (['active', 'approved'].includes(normalized)) {
        return 'rounded-full border border-green-200 bg-green-100 px-2 py-0.5 text-xs font-medium text-green-700 dark:border-green-500/50 dark:bg-green-500/20 dark:text-green-200';
    }

    if (['inactive', 'suspended'].includes(normalized)) {
        return 'rounded-full border border-red-200 bg-red-100 px-2 py-0.5 text-xs font-medium text-red-700 dark:border-red-500/50 dark:bg-red-500/20 dark:text-red-200';
    }

    if (['pending', 'under review'].includes(normalized)) {
        return 'rounded-full border border-amber-200 bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:border-amber-500/50 dark:bg-amber-500/20 dark:text-amber-200';
    }

    return 'rounded-full border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 dark:border-slate-500/50 dark:bg-slate-500/20 dark:text-slate-200';
};

const getClassificationBadgeClass = (classification: string | null | undefined) => {
    const normalized = String(classification || '').trim().toLowerCase();

    if (normalized === 'micro') {
        return 'rounded-full border border-purple-200 bg-purple-100 px-2 py-0.5 text-xs font-medium text-purple-700 dark:border-purple-500/50 dark:bg-purple-500/20 dark:text-purple-200';
    }

    if (normalized === 'small') {
        return 'rounded-full border border-indigo-200 bg-indigo-100 px-2 py-0.5 text-xs font-medium text-indigo-700 dark:border-indigo-500/50 dark:bg-indigo-500/20 dark:text-indigo-200';
    }

    if (normalized === 'medium') {
        return 'rounded-full border border-amber-200 bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-700 dark:border-amber-500/50 dark:bg-amber-500/20 dark:text-amber-200';
    }

    if (normalized === 'large') {
        return 'rounded-full border border-emerald-200 bg-emerald-100 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:border-emerald-500/50 dark:bg-emerald-500/20 dark:text-emerald-200';
    }

    return 'rounded-full border border-slate-200 bg-slate-100 px-2 py-0.5 text-xs font-medium text-slate-700 dark:border-slate-500/50 dark:bg-slate-500/20 dark:text-slate-200';
};

const regionBadgeClass =
    'rounded-full border border-blue-200 bg-blue-50 px-2 py-0.5 text-xs font-medium text-blue-600 dark:border-blue-500/40 dark:bg-blue-500/20 dark:text-blue-300';
</script>

<template>
    <Dialog :open="open" @update:open="(value) => emit('update:open', value)">
        <DialogContent class="w-full max-w-3xl max-h-[85vh] overflow-hidden">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>
                    {{ description }}
                </DialogDescription>
            </DialogHeader>

            <div class="grid gap-4 py-2">
<div class="grid gap-3 sm:grid-cols-5">
                    <div class="grid gap-2 sm:col-span-2">
                        <Label for="coop_search">Search</Label>
                        <div class="relative">
                            <Search class="pointer-events-none absolute left-3 top-1/2 h-4 w-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                id="coop_search"
                                v-model="searchQuery"
                                class="pl-9"
                                placeholder="Search by cooperative name, registration number, or ID"
                            />
                        </div>
                    </div>

                    <div class="grid gap-2">
                        <Label for="coop_status_filter">Status</Label>
                        <Select v-model="statusFilter">
                            <SelectTrigger id="coop_status_filter">
                                <SelectValue placeholder="All status" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All status</SelectItem>
                                <SelectItem v-for="status in statusOptions" :key="status" :value="status">
                                    {{ status }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="grid gap-2">
                        <Label for="coop_classification_filter">Classification</Label>
                        <Select v-model="classificationFilter">
                            <SelectTrigger id="coop_classification_filter">
                                <SelectValue placeholder="All classifications" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All classifications</SelectItem>
                                <SelectItem v-for="classification in classificationOptions" :key="classification" :value="classification">
                                    {{ classification.charAt(0).toUpperCase() + classification.slice(1) }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>

                    <div class="grid gap-2">
                        <Label for="coop_region_filter">Region</Label>
                        <Select v-model="regionFilter">
                            <SelectTrigger id="coop_region_filter">
                                <SelectValue placeholder="All regions" />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">All regions</SelectItem>
                                <SelectItem v-for="region in regionOptions" :key="region" :value="region">
                                    {{ region }}
                                </SelectItem>
                            </SelectContent>
                        </Select>
                    </div>
                </div>

                <div class="flex flex-wrap items-center justify-between gap-2 rounded-md border border-border bg-muted/30 px-3 py-2">
                    <label class="flex items-center gap-2 text-sm text-foreground">
                        <Checkbox
                            class="data-[state=checked]:border-blue-600 data-[state=checked]:bg-blue-600 data-[state=checked]:text-white"
                            :model-value="allVisibleSelected"
                            :disabled="!hasVisible"
                            @update:model-value="toggleAllVisible"
                        />
                        Select All Visible
                    </label>
                    <Badge variant="secondary">{{ selectedCountLabel }}</Badge>
                </div>

                <ScrollArea class="h-80 rounded-md border border-border">
                    <div v-if="!filteredCooperatives.length" class="px-4 py-8 text-center text-sm text-muted-foreground">
                        No cooperatives found.
                    </div>

                    <label
                        v-for="coop in filteredCooperatives"
                        :key="coop.id"
                        class="flex cursor-pointer items-center justify-between border-b border-border px-4 py-3 transition-colors last:border-b-0"
                        :class="isSelected(coop.id)
                            ? 'border-blue-100 bg-blue-50 dark:border-blue-500/30 dark:bg-blue-900/20'
                            : 'hover:bg-muted/40'"
                    >
                        <div class="flex min-w-0 items-start gap-3">
                            <Checkbox
                                class="data-[state=checked]:border-blue-600 data-[state=checked]:bg-blue-600 data-[state=checked]:text-white"
                                :model-value="isSelected(coop.id)"
                                @update:model-value="(checked) => toggleCooperative(coop.id, checked)"
                            />
                            <div class="min-w-0">
                                <div class="truncate text-sm font-medium text-foreground">{{ coop.name }}</div>
                                <div class="text-xs text-muted-foreground">
                                    Reg No: {{ coop.registration_number || 'N/A' }}
                                </div>
                                <div class="mt-1 flex flex-wrap items-center gap-1.5">
                                    <Badge v-if="coop.status" :class="getStatusBadgeClass(coop.status)">
                                        {{ coop.status }}
                                    </Badge>
                                    <Badge v-if="coop.classification" :class="getClassificationBadgeClass(coop.classification)">
                                        {{ coop.classification?.charAt(0).toUpperCase() + coop.classification?.slice(1) }}
                                    </Badge>
                                    <Badge v-if="coop.region" :class="regionBadgeClass">
                                        {{ coop.region }}
                                    </Badge>
                                </div>
                            </div>
                        </div>

                        <div
                            class="ml-3 flex items-center gap-1.5 text-xs font-medium"
                            :class="isSelected(coop.id) ? 'text-blue-700 dark:text-blue-300' : 'text-muted-foreground'"
                        >
                            <Check v-if="isSelected(coop.id)" class="h-4 w-4" />
                            <span>{{ isSelected(coop.id) ? 'Selected' : 'Select' }}</span>
                        </div>
                    </label>
                </ScrollArea>
            </div>

            <DialogFooter>
                <Button variant="outline" @click="closeDialog">
                    {{ cancelLabel }}
                </Button>
                <Button @click="confirmSelection">
                    {{ confirmLabel }}
                </Button>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>
