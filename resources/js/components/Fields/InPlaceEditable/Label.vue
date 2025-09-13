<script setup lang="ts">
// resources/js/components/Fields/InPlaceEditable/Label.vue

import { ref, computed, watch, onMounted, inject } from 'vue'
import axios from 'axios'

// ShadCN primitives
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu'

import { ChevronDown, ChevronUp, ArrowDown } from 'lucide-vue-next'

const props = defineProps<{
  model: string
  modelId: number
  token: string
  value?: number | string | null
  labelMap?: Record<number, { id: number; name: string; color?: string }>
  // New props for fill-down functionality
  rowIndex?: number
  isSelected?: boolean
  onFillDown?: (labelId: string, startIndex: number) => void
}>()

const selected = ref(props.value ? String(props.value) : null)
const labels = ref<{ id: number; name: string; color?: string }[]>([])
const loading = ref(false)
const open = ref(false)

// Try to inject table context for getting selected rows
const tableContext = inject('tableContext', null)

// Reactive label from labelMap
const label = computed(() => {
  const id = selected.value ? Number(selected.value) : null
  return id && props.labelMap ? props.labelMap[id] : null
})

const fetchLabels = async () => {
  try {
    const res = await axios.get('/api/labels', {
      headers: {
        Authorization: `Bearer ${props.token}`,
      },
    })

    labels.value = res.data.data ?? []

    // Merge any new/updated labels into labelMap (optimistically, in place)
    if (props.labelMap) {
      for (const lbl of labels.value) {
        props.labelMap[lbl.id] = lbl
      }
    }
  } catch (err) {
    console.error('❌ Error fetching labels:', err)
  }
}

const update = async (newVal: string) => {
  if (newVal === selected.value) return
  if (!props.token) {
    console.error('Missing API token in <InPlaceEditable/Label> props. Pass :token="import.meta.env.VITE_API_TOKEN".')
    return
  }
  
  selected.value = newVal
  loading.value = true
  
  try {
    const res = await axios.patch(
      `/api/fields/${props.model}/${props.modelId}`,
      { key: 'label_id', value: newVal },
      { headers: { Authorization: `Bearer ${props.token}` } }
    )
    console.log('Label updated', res.data)
  } catch (err) {
    console.error('Update failed:', err)
  } finally {
    loading.value = false
  }
}

// New fill-down functionality
const fillDown = async (labelId: string) => {
  if (props.onFillDown && props.rowIndex !== undefined) {
    props.onFillDown(labelId, props.rowIndex)
  }
}

// Check if fill-down should be available
const canFillDown = computed(() => {
  return props.onFillDown && props.rowIndex !== undefined && (props.isSelected || hasSelectedRowsBelow.value)
})

// Check if there are selected rows below this one
const hasSelectedRowsBelow = computed(() => {
  // This would need to be passed from the parent component
  // For now, we'll assume it's available if onFillDown is provided
  return !!props.onFillDown
})

watch(() => props.value, val => {
  selected.value = val ? String(val) : null
})

onMounted(fetchLabels)
</script>

<template>
  <DropdownMenu v-model:open="open">
    <DropdownMenuTrigger as-child>
      <button
        class="inline-flex items-center gap-2 px-2 py-1 text-xs rounded text-white relative group"
        :style="{ backgroundColor: label?.color || '#999' }"
        :disabled="loading"
        :class="{ 'ring-2 ring-blue-400': isSelected }"
      >
        {{ label?.name || 'Select label' }}
        <component :is="open ? ChevronUp : ChevronDown" class="w-3 h-3 text-white opacity-80" />
        
        <!-- Fill-down indicator -->
        <div
          v-if="canFillDown"
          class="absolute -bottom-1 -right-1 w-3 h-3 bg-blue-500 rounded-full opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center"
        >
          <ArrowDown class="w-2 h-2 text-white" />
        </div>
      </button>
    </DropdownMenuTrigger>

    <DropdownMenuContent class="w-48">
      <!-- Regular label options -->
      <DropdownMenuItem
        v-for="lbl in labels"
        :key="lbl.id"
        @click="() => update(String(lbl.id))"
        class="flex items-center gap-2 cursor-pointer"
      >
        <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: lbl.color || '#999' }" />
        <span>{{ lbl.name }}</span>
      </DropdownMenuItem>

      <!-- Fill-down options -->
      <template v-if="canFillDown">
        <DropdownMenuSeparator />
        <div class="px-2 py-1 text-xs text-muted-foreground font-medium">
          Fill Down
        </div>
        <DropdownMenuItem
          v-for="lbl in labels"
          :key="`fill-${lbl.id}`"
          @click="() => fillDown(String(lbl.id))"
          class="flex items-center gap-2 cursor-pointer bg-blue-50 hover:bg-blue-100"
        >
          <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: lbl.color || '#999' }" />
          <span>{{ lbl.name }}</span>
          <ArrowDown class="w-3 h-3 ml-auto text-blue-500" />
        </DropdownMenuItem>
      </template>
    </DropdownMenuContent>
  </DropdownMenu>
</template>