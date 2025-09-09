<script setup lang="ts">
// resources/js/components/Forms/CreateElement.vue

import { ref, reactive } from 'vue'
import { Button } from '@/components/ui/button'

import StandaloneCombobox from '@/components/Fields/Standalone/Combobox.vue'
import StandaloneText from '@/components/Fields/Standalone/Text.vue'

type FieldConfig = {
  key: string
  type?: 'text' | 'combobox'
  label?: string
  placeholder?: string
  endpoint?: string
  optionLabel?: string
  optionValue?: string
  attributes?: Record<string, any>
}

const fieldComponentMap: Record<string, any> = {
  text: StandaloneText,
  combobox: StandaloneCombobox,
}

const props = defineProps<{
  endpoint: string
  fields: Record<string, any>
  fieldMap?: FieldConfig[]
  token?: string
  onSuccess?: (response: any) => void
  onError?: (error: any) => void
}>()

const isSubmitting = ref(false)
const values = reactive({ ...props.fields })

async function handleSubmit() {
  isSubmitting.value = true
  try {
    const res = await fetch(props.endpoint, {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        Accept: 'application/json',
        ...(props.token ? { Authorization: `Bearer ${props.token}` } : {}),
      },
      body: JSON.stringify(values),
    })

    const json = await res.json()
    if (!res.ok) throw json

    props.onSuccess?.(json)

    // reset fields
    Object.keys(values).forEach((k) => {
      const v = values[k]
      values[k] = Array.isArray(v) ? [] : ''
    })
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

    <div v-for="field in (fieldMap || [])" :key="field.key">
      <label :for="field.key" class="text-sm font-medium capitalize">
        {{ field.label || field.key.replace(/_/g, ' ') }}
      </label>

      <component
        v-if="fieldComponentMap[field.type || 'text']"
        :is="fieldComponentMap[field.type || 'text']"
        v-model="values[field.key]"
        :id="field.key"
        :endpoint="field.endpoint"
        :option-label="field.optionLabel"
        :option-value="field.optionValue"
        :placeholder="field.placeholder"
        v-bind="field.attributes"
        class="mt-1 block w-full"
      />

      <input
        v-else
        :id="field.key"
        v-model="values[field.key]"
        type="text"
        class="mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm"
        :placeholder="field.placeholder || `Enter ${field.label || field.key}`"
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
