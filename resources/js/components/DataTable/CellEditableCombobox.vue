<script setup lang="ts">
import { ref, watch } from 'vue'
import StandaloneCombobox from '@/components/Fields/Standalone/Combobox.vue'
import { patchField } from '@/utils/patchField'

const props = defineProps<{
  model: string
  modelId: number
  field: string
  value: number[] | null
  token: string
  endpoint: string
  optionLabel?: string
  optionValue?: string
}>()

const emit = defineEmits(['update:modelValue'])

const selected = ref<Array<number>>(props.value || [])

watch(() => props.value, (val) => {
  selected.value = val || []
})

const handleUpdate = async (val: number[] | string[]) => {
  selected.value = val.map(Number)
  emit('update:modelValue', selected.value)

  try {
    await patchField(props.model, props.modelId, props.field, selected.value, props.token)
  } catch (err) {
    console.error('❌ Patch failed:', err)
  }
}
</script>

<template>
  <StandaloneCombobox
    :model-value="selected"
    @update:modelValue="handleUpdate"
    :token="props.token"
    :endpoint="props.endpoint"
    :option-label="props.optionLabel"
    :option-value="props.optionValue"
    :multiple="true"
    placeholder="Select group(s)"
  />
</template>
