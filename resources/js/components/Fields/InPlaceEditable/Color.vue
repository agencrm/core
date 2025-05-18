<script setup lang="ts">
import { ref, watch, onMounted } from 'vue'
import { ColorPicker } from 'vue3-colorpicker'
import 'vue3-colorpicker/style.css'

import { X } from 'lucide-vue-next'

import { patchField } from '@/utils/patchField'
import PopoverWrapper from '@/components/Fields/InPlaceEditable/Wrappers/PopoverWrapper.vue'

const props = defineProps<{
  model: string
  modelId: number
  field: string
  value?: string | null
  token: string
  placeholder?: string
}>()

const emit = defineEmits(['update:modelValue'])

const localValue = ref(props.value || '#cccccc')
const loading = ref(false)

// Detect dark mode via Tailwind's .dark class
const isDark = ref(false)
onMounted(() => {
  const classList = document.documentElement.classList
  isDark.value = classList.contains('dark')
  const observer = new MutationObserver(() => {
    isDark.value = classList.contains('dark')
  })
  observer.observe(document.documentElement, { attributes: true, attributeFilter: ['class'] })
})

watch(() => props.value, (val) => {
  if (!loading.value) localValue.value = val || '#cccccc'
})

const handleSave = async (newVal: string) => {
  if (newVal === props.value) return
  loading.value = true
  try {
    await patchField(props.model, props.modelId, props.field, newVal, props.token)
    emit('update:modelValue', newVal)
  } catch (e) {
    console.error('❌ Failed to patch color field:', e)
  } finally {
    loading.value = false
  }
}
</script>

<template>
  <PopoverWrapper
    :model-value="localValue"
    :loading="loading"
    @update:modelValue="handleSave"
  >
    <!-- Trigger: Swatch + hex -->
    <template #trigger="{ value }">
      <div class="flex items-center gap-2 cursor-pointer hover:opacity-80">
        <div class="w-4 h-4 rounded-full border shadow-sm" :style="{ backgroundColor: value || '#ccc' }" />
        <span class="text-xs font-mono text-gray-800 dark:text-gray-200">
          {{ value?.toUpperCase() || '#CCCCCC' }}
        </span>
      </div>
    </template>

    <!-- Content: Picker styled like ShadCN popover -->
    <template #content="{ update, stop }">
      <div class="relative p-0">
        <!-- Close button -->
        <button
          class="absolute -top-3 -right-3 text-muted-foreground hover:text-foreground transition"
          @click="stop"
        >
          <X class="w-4 h-4" />
        </button>

        <!-- Color Picker -->
        <ColorPicker
            lang="En"
          v-model:pureColor="localValue"
          format="hex"
          shape="circle"
          :is-widget="true"
          :theme="isDark ? 'black' : 'white'"
          @pure-color-change="color => {
            update(color)
            stop()
          }"
        />
      </div>
    </template>
  </PopoverWrapper>
</template>
