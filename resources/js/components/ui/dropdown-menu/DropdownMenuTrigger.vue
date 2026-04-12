<script setup lang="ts">
import type { DropdownMenuTriggerProps } from "reka-ui"
import { DropdownMenuTrigger, useForwardProps } from "reka-ui"

const props = defineProps<DropdownMenuTriggerProps>()

const forwardedProps = useForwardProps(props)
</script>

<template>
  <DropdownMenuTrigger
    data-slot="dropdown-menu-trigger"
    v-bind="forwardedProps"
    :class="props.asChild
      ? undefined
      : 'rounded-md border border-gray-400 bg-white text-gray-700 shadow-sm transition-all duration-150 ease-in-out hover:border-gray-500 hover:bg-gray-100 hover:text-gray-900 focus-visible:ring-1 focus-visible:ring-gray-300 dark:border-zinc-500 dark:bg-zinc-800 dark:text-gray-200 dark:hover:border-zinc-400 dark:hover:bg-zinc-700 dark:hover:text-white dark:focus-visible:ring-zinc-600'"
  >
    <slot />
  </DropdownMenuTrigger>
</template>
