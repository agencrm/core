<script setup lang="ts">

// resources/js/components/Views/Boards/BoardViewAttributes.vue

import { ref, computed, onMounted, watch } from 'vue'
import axios from 'axios'
import draggable from 'vuedraggable'
import { GripVertical, Eraser } from 'lucide-vue-next'


import {
  Select,
  SelectContent,
  SelectItem,
  SelectTrigger,
  SelectValue,
} from '@/components/ui/select'

import BoardViewAttributes from '@/components/Views/Boards/BoardViewAttributes.vue';

import { Button } from "@/components/ui/button"
import {
  Card,
  CardContent,
  CardDescription,
  CardFooter,
  CardHeader,
  CardTitle,
} from "@/components/ui/card"
import { Input } from "@/components/ui/input"
import { Label } from "@/components/ui/label"
import {
  Tabs,
  TabsContent,
  TabsList,
  TabsTrigger,
} from "@/components/ui/tabs"

import StandaloneCombobox from '@/components/Fields/Standalone/Combobox.vue'

const props = defineProps<{
  columnOptions?: { value: string; label: string }[]
  rowOptions?: { value: string; label: string }[]
  token: string
  endpoint?: string
  optionLabel?: string
  optionValue?: string
}>()

const emit = defineEmits(['update:columnType', 'update:rowType', 'update:labelSelection', 'update:selectedItems'])

const STORAGE_KEY = 'board-controls'
const DRAG_STATE_KEY = 'saved-selected-labels'
const STORAGE_KEY_2 = 'label-drag-selected'

const defaultOptions = {
  columns: [
    { value: 'labels', label: 'Labels' },
    { value: 'field', label: 'Field' },
  ],
  rows: [
    { value: 'assignee', label: 'Assignee' },
    { value: 'tag', label: 'Tag' },
    { value: 'priority', label: 'Priority' },
    { value: '__none__', label: 'None' },
  ],
}

const columnSelectOptions = computed(() => props.columnOptions ?? defaultOptions.columns)
const rowSelectOptions = computed(() => props.rowOptions ?? defaultOptions.rows)

const columnType = ref<string | null>(null)
const rowType = ref<string | null>(null)
const labelSelection = ref<Array<string | number>>([])

const labelGroupMap = ref<Record<number, { id: number; name: string }>>({})
const allLabels = ref<Array<{ id: number; name: string; group_id: string }>>([])

const selectedLabelsByGroup = ref<Record<string, any[]>>({})

const allItems = ref<Array<{ id: number; name: string; group_id?: string }>>([])
const availableItems = ref<Array<{ id: number; name: string }>>([])
const selectedItems = ref<Array<{ id: number; name: string }>>([])

const draggingItemId = ref<number | null>(null)
function handleStart(e: any) {
  draggingItemId.value = e.item?.__draggable_context?.element?.id ?? null
}
function handleEnd() {
  draggingItemId.value = null
}

const availableLabelsByGroup = computed(() => {
  const selectedIds = new Set(
    Object.values(selectedLabelsByGroup.value).flat().map(label => label.id)
  )
  const result: Record<string, any[]> = {}
  for (const label of allLabels.value) {
    const gid = String(label.group_id)
    if (selectedIds.has(label.id)) continue
    if (!result[gid]) result[gid] = []
    result[gid].push(label)
  }
  return result
})

watch(selectedLabelsByGroup, (val) => {
  localStorage.setItem(DRAG_STATE_KEY, JSON.stringify(val))
}, { deep: true })

onMounted(() => {
  try {
    const saved = JSON.parse(localStorage.getItem(STORAGE_KEY) || '{}')
    columnType.value = saved.columnType ?? null
    rowType.value = saved.rowType ?? null
    labelSelection.value = saved.labelSelection ?? []

    const stored = localStorage.getItem(DRAG_STATE_KEY)
    if (stored) {
      selectedLabelsByGroup.value = JSON.parse(stored)
    }
  } catch {
    // ignore
  }

  try {
    const savedCols = JSON.parse(localStorage.getItem(STORAGE_KEY_2) || '[]')
    selectedItems.value = savedCols
  } catch {
    selectedItems.value = []
  }
  syncAvailable()

  emit('update:columnType', columnType.value)
  emit('update:rowType', rowType.value === '__none__' ? null : rowType.value)
  emit('update:labelSelection', labelSelection.value)
  emit('update:selectedItems', selectedItems.value)
})

watch([columnType, rowType, labelSelection], ([col, row, labels]) => {
  localStorage.setItem(
    STORAGE_KEY,
    JSON.stringify({ columnType: col, rowType: row, labelSelection: labels })
  )
  emit('update:columnType', col)
  emit('update:rowType', row === '__none__' ? null : row)
  emit('update:labelSelection', labels)
})

watch(labelSelection, () => {
  if (columnType.value === 'labels' && labelSelection.value.length) {
    fetchLabelsForGroups()
  }
})

function handleLabelUpdate(val: string[] | number[]) {
  labelSelection.value = val
}

async function fetchLabelsForGroups() {
  const groupIds = labelSelection.value
  const url = props.endpoint ?? '/api/labels'

  try {
    const res = await axios.get(url, {
      headers: { Authorization: `Bearer ${props.token}` },
      params: { group_ids: groupIds },
    })

    const labels = (res.data.data ?? []).flatMap((label: any) =>
      (label.groups ?? []).map((group: any) => ({
        id: Number(label.id),
        name: label.name,
        group_id: String(group.id),
      }))
    )

    allLabels.value = labels
    allItems.value = labels.map(label => ({ id: label.id, name: label.name, group_id: label.group_id }))
    syncAvailable()

    labelGroupMap.value = Object.fromEntries(
      (res.data.data ?? [])
        .flatMap((label: any) => label.groups ?? [])
        .map((group: any) => [group.id, group])
    )

    const selectedRaw = JSON.parse(localStorage.getItem(DRAG_STATE_KEY) || '{}')
    selectedLabelsByGroup.value = {}
    for (const [gid, list] of Object.entries(selectedRaw)) {
      selectedLabelsByGroup.value[gid] = list as any[]
    }
  } catch (err) {
    console.error('[fetchLabelsForGroups] ERROR:', err)
  }
}

function syncAvailable() {
  const selectedIds = new Set(selectedItems.value.map(i => i.id))
  availableItems.value = allItems.value.filter(i => !selectedIds.has(i.id))
}

watch(selectedItems, (val) => {
  localStorage.setItem(STORAGE_KEY_2, JSON.stringify(val))
  syncAvailable()
  emit('update:selectedItems', val)
}, { deep: true })

function clearSelectedItems() {
  localStorage.removeItem(STORAGE_KEY_2)
  selectedItems.value = []
  syncAvailable()
  emit('update:selectedItems', [])
}
</script>


<template>

        <div class="flex flex-col gap-6">
      <!-- Row: Group By Type -->
      <div class="flex flex-col gap-2">
        <label class="text-sm font-medium">Group By Type</label>
        <Select v-model="columnType">
          <SelectTrigger class="w-full max-w-md">
            <SelectValue placeholder="Select column type" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="opt in columnSelectOptions"
              :key="opt.value"
              :value="opt.value"
            >
              {{ opt.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Row: Label Groups -->
      <div class="flex flex-col gap-2">
        <label class="text-sm font-medium">Label Group(s)</label>
        <StandaloneCombobox
          :model-value="labelSelection"
          @update:modelValue="handleLabelUpdate"
          :token="props.token"
          :endpoint="'/api/label-groups'"
          :option-label="props.optionLabel ?? 'name'"
          :option-value="props.optionValue ?? 'id'"
          :multiple="true"
          placeholder="Select group(s)"
          class="max-w-3xl"
        />
      </div>

      <!-- Row: Rows/Swimlanes -->
      <div class="flex flex-col gap-2">
        <label class="text-sm font-medium">Rows (swimlanes)</label>
        <Select v-model="rowType">
          <SelectTrigger class="w-full max-w-md">
            <SelectValue placeholder="None" />
          </SelectTrigger>
          <SelectContent>
            <SelectItem
              v-for="opt in rowSelectOptions"
              :key="opt.value"
              :value="opt.value"
            >
              {{ opt.label }}
            </SelectItem>
          </SelectContent>
        </Select>
      </div>

      <!-- Row: Drag UI - Available -->
      <div
        v-if="columnType === 'labels' && labelSelection.length"
        class="flex flex-col gap-2 rounded-lg border bg-primary-foreground text-card-foreground shadow-sm"
      >
        <div class="p-4 font-semibold border-b">Available</div>
        <div class="p-2">
          <draggable
            v-model="availableItems"
            :group="'labels'"
            item-key="id"
            handle=".drag-handle"
            class="flex flex-col gap-2"
            @start="handleStart"
            @end="handleEnd"
            ghost-class="drag-ghost"
            animation="200"
          >
            <template #item="{ element }">
              <div
                class="flex items-center gap-2 p-2 border rounded bg-muted text-sm"
                :class="{ 'ring-2 ring-indigo-500': draggingItemId === element.id }"
              >
                <GripVertical class="w-4 h-4 drag-handle text-muted-foreground" />
                {{ element.name }}
              </div>
            </template>
          </draggable>
        </div>
      </div>

      <!-- Row: Drag UI - Selected -->
      <div
        v-if="columnType === 'labels' && labelSelection.length"
        class="flex flex-col gap-2 rounded-lg border bg-primary-foreground text-card-foreground shadow-sm"
      >
        <div class="p-4 font-semibold border-b flex items-center">
          <span class="mr-auto">Selected</span>
          <button
            @click="clearSelectedItems"
            class="text-muted-foreground hover:text-foreground p-1 ml-2 transition-colors"
            title="Clear selected items"
          >
            <Eraser class="w-5 h-5" />
          </button>
        </div>
        <div class="p-2">
          <draggable
            v-model="selectedItems"
            :group="'labels'"
            item-key="id"
            handle=".drag-handle"
            class="flex flex-col gap-2"
            @start="handleStart"
            @end="handleEnd"
            ghost-class="drag-ghost"
            animation="200"
          >
            <template #item="{ element }">
              <div
                class="flex items-center gap-2 p-2 border rounded bg-muted text-sm"
                :class="{ 'ring-2 ring-indigo-500': draggingItemId === element.id }"
              >
                <GripVertical class="w-4 h-4 drag-handle text-muted-foreground" />
                {{ element.name }}
              </div>
            </template>
          </draggable>
        </div>
      </div>

      <!-- Row: Empty helper -->
      <div v-else class="text-sm text-muted-foreground">
        Choose “Labels” and pick one or more groups to build your column set.
      </div>
    </div>

</template>