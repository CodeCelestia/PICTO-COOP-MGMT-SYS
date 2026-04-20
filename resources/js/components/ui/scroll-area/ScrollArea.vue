<script setup lang="ts">
import type { ScrollAreaRootProps } from "reka-ui"
import type { HTMLAttributes } from "vue"
import { reactiveOmit } from "@vueuse/core"
import { ScrollAreaRoot, ScrollAreaViewport, useForwardProps } from "reka-ui"
import { cn } from "@/lib/utils"
import ScrollBar from "./ScrollBar.vue"

const props = defineProps<ScrollAreaRootProps & { class?: HTMLAttributes["class"] }>()

const delegatedProps = reactiveOmit(props, "class")
const forwarded = useForwardProps(delegatedProps)
</script>

<template>
  <ScrollAreaRoot
    data-slot="scroll-area"
    v-bind="forwarded"
    :class="cn('relative overflow-hidden', props.class)"
  >
    <ScrollAreaViewport data-slot="scroll-area-viewport" class="size-full rounded-[inherit]">
      <slot />
    </ScrollAreaViewport>
    <ScrollBar />
  </ScrollAreaRoot>
</template>
