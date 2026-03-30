<script setup lang="ts">
import type { HTMLAttributes, Ref } from "vue"
import { defaultDocument, useEventListener, useMediaQuery, useStorage, useVModel } from "@vueuse/core"
import { TooltipProvider } from "reka-ui"
import { computed, ref } from "vue"
import { cn } from "@/lib/utils"
import {
  provideSidebarContext,
  SIDEBAR_COOKIE_MAX_AGE,
  SIDEBAR_COOKIE_NAME,
  SIDEBAR_KEYBOARD_SHORTCUT,
  SIDEBAR_LARGE_MODE_STORAGE_KEY,
  SIDEBAR_WIDTH,
  SIDEBAR_WIDTH_LARGE,
  SIDEBAR_WIDTH_ICON,
  SIDEBAR_WIDTH_ICON_LARGE,
} from "./utils"

const props = withDefaults(defineProps<{
  defaultOpen?: boolean
  open?: boolean
  class?: HTMLAttributes["class"]
}>(), {
  defaultOpen: !defaultDocument?.cookie.includes(`${SIDEBAR_COOKIE_NAME}=false`),
  open: undefined,
})

const emits = defineEmits<{
  "update:open": [open: boolean]
}>()

const isMobile = useMediaQuery("(max-width: 768px)")
const openMobile = ref(false)
const isLargeMode = useStorage(SIDEBAR_LARGE_MODE_STORAGE_KEY, false)

const open = useVModel(props, "open", emits, {
  defaultValue: props.defaultOpen ?? false,
  passive: (props.open === undefined) as false,
}) as Ref<boolean>

function setOpen(value: boolean) {
  open.value = value // emits('update:open', value)

  // This sets the cookie to keep the sidebar state.
  document.cookie = `${SIDEBAR_COOKIE_NAME}=${open.value}; path=/; max-age=${SIDEBAR_COOKIE_MAX_AGE}`
}

function setOpenMobile(value: boolean) {
  openMobile.value = value
}

function setLargeMode(value: boolean) {
  isLargeMode.value = value
}

function toggleLargeMode() {
  setLargeMode(!isLargeMode.value)
}

// Helper to toggle the sidebar.
function toggleSidebar() {
  return isMobile.value ? setOpenMobile(!openMobile.value) : setOpen(!open.value)
}

useEventListener("keydown", (event: KeyboardEvent) => {
  if (event.key === SIDEBAR_KEYBOARD_SHORTCUT && (event.metaKey || event.ctrlKey)) {
    event.preventDefault()
    toggleSidebar()
  }
})

// We add a state so that we can do data-state="expanded" or "collapsed".
// This makes it easier to style the sidebar with Tailwind classes.
const state = computed(() => open.value ? "expanded" : "collapsed")

provideSidebarContext({
  state,
  open,
  setOpen,
  isMobile,
  openMobile,
  setOpenMobile,
  toggleSidebar,
  isLargeMode,
  setLargeMode,
  toggleLargeMode,
})
</script>

<template>
  <TooltipProvider :delay-duration="0">
    <div
      data-slot="sidebar-wrapper"
      :data-a11y-size="isLargeMode ? 'large' : 'default'"
      :style="{
        '--sidebar-width': isLargeMode ? SIDEBAR_WIDTH_LARGE : SIDEBAR_WIDTH,
        '--sidebar-width-icon': isLargeMode ? SIDEBAR_WIDTH_ICON_LARGE : SIDEBAR_WIDTH_ICON,
        '--sidebar-menu-item-gap': isLargeMode ? '1rem' : '0.75rem',
        '--sidebar-menu-font-size': isLargeMode ? '1.125rem' : '1rem',
        '--sidebar-menu-item-height': isLargeMode ? '2.75rem' : '2.25rem',
        '--sidebar-menu-item-height-lg': isLargeMode ? '3.25rem' : '3rem',
        '--sidebar-menu-icon-size': isLargeMode ? '1.5rem' : '1.25rem',
        '--sidebar-menu-collapsed-size': isLargeMode ? '2.5rem' : '2rem',
        '--sidebar-menu-collapsed-padding': isLargeMode ? '0.625rem' : '0.5rem',
        '--sidebar-group-label-font-size': isLargeMode ? '1rem' : '0.875rem',
        '--sidebar-group-label-icon-size': isLargeMode ? '1.5rem' : '1.25rem',
      }"
      :class="cn('group/sidebar-wrapper has-data-[variant=inset]:bg-sidebar flex min-h-svh w-full', props.class)"
      v-bind="$attrs"
    >
      <slot />
    </div>
  </TooltipProvider>
</template>
