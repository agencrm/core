<script setup lang="ts">
import { ref, reactive } from 'vue'
import { Button } from '@/components/ui/button'

const props = defineProps<{
  endpoint: string
  fields: Record<string, any> // will be cloned for internal state
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
    <div v-for="(value, key) in values" :key="key">
      <label :for="key" class="text-sm font-medium capitalize">
        {{ key.replace(/_/g, ' ') }}
      </label>
      <input
        :id="key"
        v-model="values[key]"
        type="text"
        class="mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm"
        :placeholder="`Enter ${key.replace(/_/g, ' ')}`"
      />
    </div>

    <div class="flex justify-end gap-2">
      <Button type="submit" :disabled="isSubmitting">
        <span v-if="isSubmitting">Saving...</span>
        <span v-else>Save</span>
      </Button>
    </div>
  </form>
</template>
