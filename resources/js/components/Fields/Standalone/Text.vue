<script setup lang="ts">

// resources/js/components/Fields/Standalone/Text.vue

import { ref, watch } from 'vue'

const props = defineProps<{
  modelValue: string | number | null
  label?: string
  placeholder?: string
  id?: string
  type?: string // text, email, number, etc.
}>()

const emit = defineEmits(['update:modelValue'])

const localValue = ref(props.modelValue ?? '')

watch(() => props.modelValue, (val) => {
  localValue.value = val ?? ''
})

const update = (e: Event) => {
  const value = (e.target as HTMLInputElement).value
  emit('update:modelValue', value)
}
</script>

<template>
  <div class="w-full space-y-1">
    <label v-if="props.label" :for="props.id" class="text-sm font-medium">
      {{ props.label }}
    </label>
    <input
      :id="props.id"
      :type="props.type || 'text'"
      class="mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm focus:outline-none focus:ring-2 focus:ring-primary"
      :placeholder="props.placeholder || ''"
      :value="localValue"
      @input="update"
    />
  </div>
</template>