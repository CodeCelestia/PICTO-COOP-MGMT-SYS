<script setup lang="ts">
import { Check, CheckSquare, X } from 'lucide-vue-next';
import { computed, ref, watch } from 'vue';
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

interface Props {
    open: boolean;
    selectedValues: string[];
    options?: string[];
    title?: string;
    description?: string;
}

const props = withDefaults(defineProps<Props>(), {
    options: () => ['All Members', 'Officers Only', 'Women', 'Youth', 'Farmers', 'FisherFolk', 'New Members', 'Other'],
    title: 'Select Target Groups',
    description: 'Choose one or more target groups for this training.',
});

const emit = defineEmits<{
    (e: 'update:open', value: boolean): void;
    (e: 'confirm', value: string[]): void;
}>();

const draftValues = ref<string[]>([]);

watch(
    () => props.open,
    (isOpen) => {
        if (!isOpen) {
            return;
        }

        draftValues.value = [...new Set(props.selectedValues || [])];
    }
);

const selectedSet = computed(() => new Set(draftValues.value));
const selectedCountLabel = computed(() => `${draftValues.value.length} selected`);
const optionCountLabel = computed(() => `${props.options.length} available`);

const toggleValue = (value: string, checked: boolean | 'indeterminate') => {
    if (checked === true) {
        if (!selectedSet.value.has(value)) {
            draftValues.value = [...draftValues.value, value];
        }
        return;
    }

    draftValues.value = draftValues.value.filter((item) => item !== value);
};

const selectAll = () => {
    draftValues.value = [...props.options];
};

const clearAll = () => {
    draftValues.value = [];
};

const closeDialog = () => {
    emit('update:open', false);
};

const confirmSelection = () => {
    emit('confirm', [...draftValues.value]);
    emit('update:open', false);
};
</script>

<template>
    <Dialog :open="open" @update:open="(value) => emit('update:open', value)">
        <DialogContent class="w-full max-w-3xl overflow-hidden">
            <DialogHeader>
                <DialogTitle>{{ title }}</DialogTitle>
                <DialogDescription>{{ description }}</DialogDescription>
            </DialogHeader>

            <div class="space-y-4 py-1">
                <div class="flex flex-wrap items-center justify-between gap-2 rounded-lg border border-border bg-muted/30 px-3 py-2">
                    <div class="flex items-center gap-2 text-sm text-muted-foreground">
                        <CheckSquare class="h-4 w-4" />
                        <span>{{ optionCountLabel }}</span>
                    </div>
                    <Badge variant="secondary">{{ selectedCountLabel }}</Badge>
                </div>

                <div class="grid gap-2 sm:grid-cols-2">
                    <label
                        v-for="option in props.options"
                        :key="option"
                        class="flex cursor-pointer items-start gap-3 rounded-xl border border-border px-3 py-3 transition-colors hover:bg-muted/40"
                        :class="selectedSet.has(option) ? 'border-blue-200 bg-blue-50 dark:border-blue-500/30 dark:bg-blue-900/20' : ''"
                    >
                        <Checkbox
                            :model-value="selectedSet.has(option)"
                            @update:model-value="(checked) => toggleValue(option, checked)"
                            class="mt-0.5"
                        />
                        <div class="min-w-0 flex-1">
                            <p class="text-sm font-medium text-foreground">{{ option }}</p>
                            <p class="text-xs text-muted-foreground">Include participants from this target group.</p>
                        </div>
                        <Check v-if="selectedSet.has(option)" class="h-4 w-4 shrink-0 text-blue-600" />
                    </label>
                </div>
            </div>

            <DialogFooter>
                <Button type="button" variant="outline" @click="clearAll">
                    <X class="mr-2 h-4 w-4" />
                    Clear All
                </Button>
                <div class="ml-auto flex gap-2">
                    <Button type="button" variant="outline" @click="closeDialog">Cancel</Button>
                    <Button type="button" @click="confirmSelection">Apply Selection</Button>
                </div>
            </DialogFooter>
        </DialogContent>
    </Dialog>
</template>