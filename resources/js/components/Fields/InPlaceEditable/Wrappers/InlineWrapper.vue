<script setup lang="ts">

// resources/js/components/Fields/InPlaceEditable/Wrappers/InlineWrapper.vue

import { ref, watch } from 'vue'

const props = defineProps<{
  modelValue: string | number | null
  loading?: boolean
}>()

const emit = defineEmits(['update:modelValue'])

const editing = ref(false)
const internalValue = ref(props.modelValue)

watch(() => props.modelValue, (val) => {
  if (!editing.value) internalValue.value = val
})

const startEditing = () => {
  if (props.loading) return
  editing.value = true
}

const stopEditing = () => {
  editing.value = false
  emit('update:modelValue', internalValue.value)
}
</script>

<template>
  <div class="inline-flex items-center gap-1">
    <template v-if="editing">
      <slot name="editor" :value="internalValue" :update="v => (internalValue = v)" :stop="stopEditing" />
    </template>
    <template v-else>
      <div @click="startEditing" class="cursor-pointer w-full">
        <slot name="display" :value="internalValue" />
      </div>
    </template>
  </div>
</template>
