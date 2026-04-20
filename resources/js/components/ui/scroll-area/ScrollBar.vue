<script setup lang="ts">
import type { ScrollAreaScrollbarProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { ScrollAreaScrollbar, ScrollAreaThumb, useForwardProps } from "reka-ui"
import { cn } from "@/lib/utils"

const props = withDefaults(
  defineProps<ScrollAreaScrollbarProps & { class?: HTMLAttributes["class"] }>(),
  {
    orientation: "vertical",
  },
)

const delegatedProps = reactiveOmit(props, "class")
const forwarded = useForwardProps(delegatedProps)
</script>

<template>
  <ScrollAreaScrollbar
    data-slot="scroll-area-scrollbar"
    v-bind="forwarded"
    :class="cn(
      'flex touch-none select-none transition-colors',
      props.orientation === 'vertical' && 'h-full w-2.5 border-l border-l-transparent p-px',
      props.orientation === 'horizontal' && 'h-2.5 flex-col border-t border-t-transparent p-px',
      props.class,
    )"
  >
    <ScrollAreaThumb data-slot="scroll-area-thumb" class="bg-border relative flex-1 rounded-full" />
  </ScrollAreaScrollbar>
</template>
