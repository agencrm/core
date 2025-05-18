<script setup lang="ts">
import { ref, watch } from 'vue'
import { Pencil } from 'lucide-vue-next'

const props = defineProps<{
  modelValue?: string | null
  placeholder?: string
  loading?: boolean
}>()

const emit = defineEmits(['update:modelValue'])

const editing = ref(false)
const inputValue = ref(props.modelValue || '')

watch(() => props.modelValue, (val) => {
  inputValue.value = val || ''
})

const startEditing = () => {
  editing.value = true
  inputValue.value = props.modelValue || ''
}

const stopEditing = () => {
  editing.value = false
  emit('update:modelValue', inputValue.value)
}
</script>

<template>
  <div class="inline-flex items-center gap-1">
    <template v-if="editing">
      <input
        v-model="inputValue"
        class="text-sm px-2 py-1 border rounded focus:outline-none focus:ring"
        @blur="stopEditing"
        @keyup.enter="stopEditing"
        :disabled="props.loading"
        autofocus
      />
    </template>
    <template v-else>
      <button
        @click="startEditing"
        class="text-sm text-left px-2 py-1 hover:bg-gray-100 rounded flex items-center gap-1 group"
      >
        <span class="truncate max-w-[150px]">
          {{ props.modelValue || props.placeholder || '—' }}
        </span>
        <Pencil class="w-3 h-3 opacity-30 group-hover:opacity-80 transition" />
      </button>
    </template>
  </div>
</template>
