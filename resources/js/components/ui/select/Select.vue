<script setup lang="ts">
import type { SelectRootEmits, SelectRootProps } from "reka-ui"
import { SelectRoot, useForwardProps } from "reka-ui"
import { computed } from "vue"

const EMPTY_SELECT_VALUE = "__REKA_EMPTY_SELECT_VALUE__"

const props = defineProps<SelectRootProps>()
const emits = defineEmits<SelectRootEmits>()

function normalizeEmptyToSentinel<T>(value: T): T {
  if (value === "") {
    return EMPTY_SELECT_VALUE as T
  }

  if (Array.isArray(value)) {
    return value.map((item) => normalizeEmptyToSentinel(item)) as T
  }

  return value
}

function normalizeSentinelToEmpty<T>(value: T): T {
  if (value === EMPTY_SELECT_VALUE) {
    return "" as T
  }

  if (Array.isArray(value)) {
    return value.map((item) => normalizeSentinelToEmpty(item)) as T
  }

  return value
}

const normalizedProps = computed<SelectRootProps>(() => ({
  ...props,
  modelValue: normalizeEmptyToSentinel(props.modelValue),
  defaultValue: normalizeEmptyToSentinel(props.defaultValue),
}))

const forwarded = useForwardProps(normalizedProps)

function handleModelValueUpdate(value: unknown) {
  emits("update:modelValue", normalizeSentinelToEmpty(value) as any)
}

function handleOpenUpdate(value: boolean) {
  emits("update:open", value)
}
</script>

<template>
  <SelectRoot
    v-slot="slotProps"
    data-slot="select"
    v-bind="forwarded"
    @update:modelValue="handleModelValueUpdate"
    @update:open="handleOpenUpdate"
  >
    <slot v-bind="slotProps" />
  </SelectRoot>
</template>
