<script setup lang="ts">
import { usePage } from '@inertiajs/vue3'
import type { HTMLAttributes } from "vue"
import { nextTick, onMounted, ref, watch } from 'vue'
import { cn } from "@/lib/utils"

const props = defineProps<{
  class?: HTMLAttributes["class"]
}>()

const SIDEBAR_SCROLL_STORAGE_KEY = 'sidebar_content_scroll_top'

const page = usePage()
const contentRef = ref<HTMLElement | null>(null)

function saveScrollPosition() {
  if (typeof window === 'undefined' || !contentRef.value) return

  window.sessionStorage.setItem(SIDEBAR_SCROLL_STORAGE_KEY, String(contentRef.value.scrollTop))
}

async function restoreScrollPosition() {
  if (typeof window === 'undefined' || !contentRef.value) return

  const saved = window.sessionStorage.getItem(SIDEBAR_SCROLL_STORAGE_KEY)
  if (saved === null) return

  const parsed = Number(saved)
  if (!Number.isFinite(parsed) || parsed < 0) return

  await nextTick()
  if (contentRef.value) {
    contentRef.value.scrollTop = parsed
  }
}

onMounted(async () => {
  await restoreScrollPosition()
})

watch(
  () => page.url,
  async () => {
    await restoreScrollPosition()
  },
)
</script>

<template>
  <div
    ref="contentRef"
    data-slot="sidebar-content"
    data-sidebar="content"
    @scroll.passive="saveScrollPosition"
    :class="cn('no-scrollbar flex min-h-0 flex-1 flex-col gap-2 overflow-auto group-data-[collapsible=icon]:overflow-y-auto', props.class)"
  >
    <slot />
  </div>
</template>
