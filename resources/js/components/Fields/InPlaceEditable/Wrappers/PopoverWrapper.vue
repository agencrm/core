<script setup lang="ts">

// resources/js/components/Fields/InPlaceEditable/Wrappers/PopoverWrapper.vue

import { ref, watch } from 'vue'
import {
  Popover,
  PopoverTrigger,
  PopoverContent,
} from '@/components/ui/popover'

const props = defineProps<{
  modelValue: string | number | null
  loading?: boolean
}>()

const emit = defineEmits(['update:modelValue'])

const open = ref(false)
const internalValue = ref(props.modelValue)

watch(() => props.modelValue, (val) => {
  if (!open.value) internalValue.value = val
})

const start = () => {
  if (props.loading) return
  open.value = true
}

const stop = () => {
  open.value = false
  emit('update:modelValue', internalValue.value)
}
</script>

<template>
  <Popover v-model:open="open">
    <PopoverTrigger as-child>
      <div @click="start">
        <slot name="trigger" :value="internalValue" />
      </div>
    </PopoverTrigger>

    <PopoverContent class="w-auto min-w-[200px]">
      <slot name="content" :value="internalValue" :update="v => (internalValue = v)" :stop="stop" />
    </PopoverContent>
  </Popover>
</template>
