<script setup lang="ts">

// resources/js/components/DataTable/CellEditableFieldColor.vue

import { ref, watch } from 'vue'

const props = defineProps<{
  modelValue: string
  labelId: number
  token: string
}>()

const emit = defineEmits(['update:modelValue'])

const color = ref(props.modelValue)
const loading = ref(false)

watch(() => props.modelValue, (val) => color.value = val)

const saveColor = async () => {
  loading.value = true
  try {
    const res = await fetch(`/api/labels/${props.labelId}`, {
      method: 'PATCH',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        Authorization: `Bearer ${props.token}`,
      },
      body: JSON.stringify({ color: color.value }),
    })

    if (!res.ok) throw await res.json()

    emit('update:modelValue', color.value)
  } catch (err) {
    console.error('Failed to update color:', err)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <input
    type="color"
    :value="color"
    :disabled="loading"
    class="h-6 w-12 border rounded"
    @input="color = $event.target.value"
    @change="saveColor"
  />
</template>
