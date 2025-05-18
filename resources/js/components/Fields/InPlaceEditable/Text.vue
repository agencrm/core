<script setup lang="ts">

// resources/js/components/Fields/InPlaceEditable/Text.vue

import { ref, watch, computed } from 'vue'
import { patchField } from '@/utils/patchField'
import InlineWrapper from '@/components/Fields/InPlaceEditable/Wrappers/InlineWrapper.vue'
import PopoverWrapper from '@/components/Fields/InPlaceEditable/Wrappers/PopoverWrapper.vue'

const props = defineProps<{
  model: string
  modelId: number
  field: string
  value?: string | null
  token: string
  placeholder?: string
  mode?: 'inline' | 'popover'
}>()

const emit = defineEmits(['update:modelValue'])

const localValue = ref(props.value || '')
const loading = ref(false)

watch(() => props.value, (val) => {
  if (!loading.value) localValue.value = val || ''
})

const handleSave = async (newVal: string) => {
  if (newVal === props.value) return
  loading.value = true

  try {
    await patchField(props.model, props.modelId, props.field, newVal, props.token)
    emit('update:modelValue', newVal)
  } catch (e) {
    console.error('❌ Failed to patch field:', e)
  } finally {
    loading.value = false
  }
}

const placeholder = computed(() => props.placeholder || '—')
const isInline = computed(() => props.mode !== 'popover')
</script>

<template>
  <component
    :is="isInline ? InlineWrapper : PopoverWrapper"
    :model-value="localValue"
    :loading="loading"
    @update:modelValue="handleSave"
  >
    <template #display="{ value }">
      <span class="truncate max-w-[150px] text-sm text-muted-foreground">
        {{ value || placeholder }}
      </span>
    </template>

    <template #trigger="{ value }">
      <button class="text-sm text-left text-muted-foreground hover:underline">
        {{ value || placeholder }}
      </button>
    </template>

    <template #editor="{ value, update, stop }">
      <input
        type="text"
        class="text-sm px-2 py-1 border rounded focus:outline-none focus:ring w-full"
        :value="value"
        @input="e => update(e.target.value)"
        @keyup.enter="stop"
        @blur="stop"
        :disabled="loading"
        autofocus
      />
    </template>

    <template #content="{ value, update, stop }">
      <input
        type="text"
        class="text-sm px-2 py-1 border rounded focus:outline-none focus:ring w-full"
        :value="value"
        @input="e => update(e.target.value)"
        @keyup.enter="stop"
        @blur="stop"
        :disabled="loading"
        autofocus
      />
    </template>
  </component>
</template>
