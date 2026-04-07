<script setup lang="ts">
import { computed, ref } from 'vue';
import { Button } from '@/components/ui/button';
import { Filter, ChevronDown, ChevronUp } from 'lucide-vue-next';

const props = defineProps<{
  title?: string;
  description?: string;
  initiallyOpen?: boolean;
  showLabel?: string;
  hideLabel?: string;
}>();

const open = ref(props.initiallyOpen ?? false);
const toggleLabel = computed(() => open.value ? (props.hideLabel || 'Hide filters') : (props.showLabel || 'Show filters'));
</script>

<template>
  <div class="rounded-xl border border-border/80 bg-card shadow-sm">
    <div class="p-4 sm:p-5">
      <div class="mb-4 flex flex-col gap-3 md:flex-row md:items-center md:justify-between">
        <div>
          <h2 class="text-base font-semibold text-foreground">{{ title || 'Filters' }}</h2>
          <p v-if="description" class="mt-1 text-sm text-muted-foreground">{{ description }}</p>
        </div>
        <Button
          variant="outline"
          size="sm"
          class="w-full justify-center md:w-auto gap-2"
          type="button"
          @click="open = !open"
          :aria-expanded="String(open)"
        >
          <Filter class="h-4 w-4" />
          {{ toggleLabel }}
          <span v-if="open"><ChevronUp class="h-4 w-4" /></span>
          <span v-else><ChevronDown class="h-4 w-4" /></span>
        </Button>
      </div>

      <div v-show="open" class="space-y-4" data-filter-panel>
        <slot />
      </div>
    </div>
  </div>
</template>
