<script setup lang="ts">
import { ref, computed, watch, onMounted } from 'vue'
import axios from 'axios'

// ShadCN primitives
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuItem,
} from '@/components/ui/dropdown-menu'

import { ChevronDown, ChevronUp } from 'lucide-vue-next'

const props = defineProps<{
  model: string
  modelId: number
  token: string
  value?: number | string | null
  labelMap?: Record<number, { id: number; name: string; color?: string }>
}>()

const selected = ref(props.value ? String(props.value) : null)
const labels = ref<{ id: number; name: string; color?: string }[]>([])
const loading = ref(false)
const open = ref(false)

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
  selected.value = newVal // optimistic update
  loading.value = true
  try {
    const res = await axios.patch(`/api/fields/${props.model}/${props.modelId}`, {
      key: 'label_id',
      value: newVal,
    }, {
      headers: {
        Authorization: `Bearer ${props.token}`,
      },
    })
    console.log('✔️ Label updated', res.data)
  } catch (err) {
    console.error('❌ Update failed:', err)
  } finally {
    loading.value = false
  }
}

watch(() => props.value, val => {
  selected.value = val ? String(val) : null
})

onMounted(fetchLabels)
</script>

<template>
  <DropdownMenu v-model:open="open">
    <DropdownMenuTrigger as-child>
      <button
        class="inline-flex items-center gap-2 px-2 py-1 text-xs rounded text-white"
        :style="{ backgroundColor: label?.color || '#999' }"
        :disabled="loading"
      >
        {{ label?.name || 'Select label' }}
        <component :is="open ? ChevronUp : ChevronDown" class="w-3 h-3 text-white opacity-80" />
      </button>
    </DropdownMenuTrigger>

    <DropdownMenuContent class="w-48">
      <DropdownMenuItem
        v-for="lbl in labels"
        :key="lbl.id"
        @click="() => update(String(lbl.id))"
        class="flex items-center gap-2 cursor-pointer"
      >
        <span class="w-3 h-3 rounded-full" :style="{ backgroundColor: lbl.color || '#999' }" />
        <span>{{ lbl.name }}</span>
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
