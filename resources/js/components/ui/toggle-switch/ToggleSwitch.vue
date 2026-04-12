<script setup lang="ts">
import type { HTMLAttributes } from "vue"
import { cn } from "@/lib/utils"

const props = withDefaults(defineProps<{
  modelValue: boolean
  disabled?: boolean
  class?: HTMLAttributes["class"]
}>(), {
  disabled: false,
})

const emits = defineEmits<{
  (e: "update:modelValue", value: boolean): void
}>()

const toggle = () => {
  if (props.disabled) return
  emits("update:modelValue", !props.modelValue)
}
</script>

<template>
  <button
    type="button"
    role="switch"
    :aria-checked="props.modelValue"
    :disabled="props.disabled"
    :class="
      cn(
        'relative inline-flex h-6 w-11 shrink-0 items-center rounded-full border border-transparent transition-all duration-300 ease-in-out focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-ring focus-visible:ring-offset-2 disabled:cursor-not-allowed disabled:opacity-60',
        props.modelValue ? 'bg-green-500' : 'bg-red-500',
        props.class,
      )"
    @click="toggle"
  >
    <span
      :class="
        cn(
              'pointer-events-none block h-5 w-5 rounded-full bg-white shadow-sm transition-all duration-300 ease-in-out',
              props.modelValue ? 'translate-x-5' : 'translate-x-0.5',
        )"
    />
  </button>
</template>
