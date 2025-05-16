<script setup lang="ts">
import { ref, reactive } from 'vue'
import { Button } from '@/components/ui/button'

import StandaloneCombobox from '@/components/Fields/Standalone/Combobox.vue';
import StandaloneText from '@/components/Fields/Standalone/Text.vue';

const fieldComponentMap = {
  text: StandaloneText,
  combobox: StandaloneCombobox,
}


const props = defineProps<{
  endpoint: string
  fields: Record<string, any> // will be cloned for internal state
    fieldMap?: {
    key: string
    type?: 'text' | 'combobox'
    label?: string
    placeholder?: string
    endpoint?: string
    optionLabel?: string
    optionValue?: string
  }[]
  token?: string
  onSuccess?: (response: any) => void
  onError?: (error: any) => void
}>()

const isSubmitting = ref(false)
const values = reactive({ ...props.fields }) // internal clone

const handleSubmit = async () => {
  isSubmitting.value = true
  try {
    const res = await fetch(props.endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(props.token && { Authorization: `Bearer ${props.token}` }),
      },
      body: JSON.stringify(values),
    })

    if (!res.ok) throw await res.json()

    const json = await res.json()
    props.onSuccess?.(json)
    Object.keys(values).forEach((k) => values[k] = '') // reset after success
  } catch (error) {
    props.onError?.(error)
  } finally {
    isSubmitting.value = false
  }
}
</script>

<template>
  <form @submit.prevent="handleSubmit" class="space-y-4">

    <slot name="before-fields" />

    <div v-for="field in props.fieldMap || []" :key="field.key">
      <label :for="field.key" class="text-sm font-medium capitalize">
        {{ field.label || field.key.replace(/_/g, ' ') }}
      </label>

      <!-- TEXT FIELD -->
      <input
        v-if="fieldComponentMap[field.type || 'text'] === 'input'"
        :id="field.key"
        v-model="values[field.key]"
        type="text"
        class="mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm"
        :placeholder="field.placeholder || `Enter ${field.label || field.key}`"
      />

      <!-- COMBOBOX FIELD -->
      <component
        v-else
        :is="fieldComponentMap[field.type]"
        v-model="values[field.key]"
        :endpoint="field.endpoint"
        :token="token"
        :option-label="field.optionLabel"
        :option-value="field.optionValue"
        :placeholder="field.placeholder"
        v-bind="field.attributes"
      />
    </div>

    <slot name="after-fields" />

    <slot name="footer">
      <div class="flex justify-end gap-2">
        <Button type="submit" :disabled="isSubmitting">
          <span v-if="isSubmitting">Saving...</span>
          <span v-else>Save</span>
        </Button>
      </div>
    </slot>
  
  </form>
</template>
