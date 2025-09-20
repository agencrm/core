<script setup lang="ts">
// resources/js/components/Views/Boards/BoardView.vue

import { ref, watch, computed, onMounted } from 'vue'
import axios from 'axios'
import { route } from 'ziggy-js'
import { debounce } from 'lodash'

import BoardControls from '@/components/Views/Boards/BoardControls.vue'
import BoardColumn from '@/components/Views/Boards/BoardColumn.vue'

const props = defineProps<{
    routeName: string
    authToken: string
    showBoardControls?: boolean
    labelGroupId?: number
    /** used by PATCH endpoint: /api/fields/:model/:id */
    modelName?: 'contact' | 'task' | 'project' | string
}>()

const emit = defineEmits(['update:labelMap'])

type Column = { id: string; title: string; items: any[] }

const columns = ref<Column[]>([])
const labelMap = ref<Record<number, { id: number; name: string }>>({})
const columnMap = ref<any>(null)
const rowMap = ref<any>(null)

const columnType = ref<string | null>(null)
const rowType = ref<string | null>(null)
const selectedColumnLabels = ref<Array<{ id: number; name: string }>>([])

const latestViewState = ref<{
    columnType: string | null
    rowType: string | null
    labelSelection: Array<string | number>
    selectedItems: Array<{ id: number; name: string }>
} | null>(null)

// async function fetchLabelsForGroup() {
//     if (!props.labelGroupId) return
//     try {
//         const res = await axios.get(route('api.labels.index'), {
//             headers: { Authorization: `Bearer ${props.authToken}` },
//             params: { group_ids: [props.labelGroupId] },
//         })
//         const labels = res.data?.data ?? []
//         columnMap.value = res.data
//         labelMap.value = Object.fromEntries(labels.map((l: any) => [Number(l.id), l]))

//         if (!selectedColumnLabels.value.length) {
//             columns.value = labels.map((label: any) => ({
//                 id: String(label.id),
//                 title: label.name,
//                 items: [],
//             }))
//         }
//         emit('update:labelMap', labelMap.value)
//     } catch (error) {
//         columnMap.value = { error: String(error) }
//     }
// }

// resources/js/components/Views/Boards/BoardView.vue
// Replace ONLY the fetchLabelsForGroup() function with this version.
// Keep your existing imports, refs, watchers, etc.

async function fetchLabelsForGroup() {
  if (!props.labelGroupId) return
  try {
    const res = await axios.get(route('api.labels.index'), {
      headers: { Authorization: `Bearer ${props.authToken}` },
      params: { group_ids: [props.labelGroupId] },
    })
    const labels = Array.isArray(res?.data?.data) ? res.data.data : []
    labelMap.value = Object.fromEntries(labels.map((l: any) => [Number(l.id), l]))
    emit('update:labelMap', labelMap.value)

    // Only construct columns here if nothing was saved
    if (!columns.value.length && !selectedColumnLabels.value.length) {
      columns.value = labels.map((l: any) => ({ id: String(l.id), title: l.name ?? String(l.id), items: [] }))
    }

    if (columns.value.length) await fetchItemsForColumns()
  } catch (error) {
    columnMap.value = { error: String(error) }
    console.error('[BoardView] fetchLabelsForGroup failed:', error)
  }
}

// Namespace used by <BoardControls storage-key="...">
const controlsStorageKey = computed(() =>
  `board:${props.routeName}${props.labelGroupId ? ':lg=' + props.labelGroupId : ''}`
)

// BoardViewAttributes uses these localStorage keys internally
const LS_KEYS = {
  STATE: () => controlsStorageKey.value, // { columnType,rowType,labelSelection }
  SELECTED_ITEMS: 'label-drag-selected', // [{id,name}]
}

// Load saved controls and shape columns from them
function loadControlsStateFromStorage() {
  // 1) core state (columnType,rowType,labelSelection)
  try {
    const raw = localStorage.getItem(LS_KEYS.STATE())
    if (raw) {
      const s = JSON.parse(raw)
      columnType.value = s?.columnType ?? null
      rowType.value = s?.rowType ?? null
      // labelSelection is available if you ever need it (numbers)
      // const labelSelection: number[] = (s?.labelSelection ?? []).map(Number).filter(Number.isFinite)
      latestViewState.value = {
        columnType: columnType.value,
        rowType: rowType.value,
        labelSelection: s?.labelSelection ?? [],
        selectedItems: [], // fill next
      }
    }
  } catch {}

  // 2) selectedItems (the actual column set user curated)
  try {
    const rawSel = localStorage.getItem(LS_KEYS.SELECTED_ITEMS)
    const items = Array.isArray(JSON.parse(rawSel ?? '[]')) ? JSON.parse(rawSel!) : []
    selectedColumnLabels.value = items
    // Build columns directly from saved “selectedItems” (id, name)
    if (Array.isArray(items) && items.length) {
      columns.value = items.map((it: any) => ({
        id: String(it.id),
        title: it.name ?? String(it.id),
        items: [],
      }))
    }
    // keep latestViewState in sync for any future persistence
    if (latestViewState.value) latestViewState.value.selectedItems = items
  } catch {}
}



async function fetchItemsForColumns() {
    const labelIds = columns.value.map(col => col.id)
    if (!labelIds.length) return
    try {
        const res = await axios.get(route(props.routeName), {
            headers: { Authorization: `Bearer ${props.authToken}` },
            params: {
                label_ids: labelIds,
                row_type: rowType.value ?? null,
            },
        })
        const items = res.data?.data ?? []
        columns.value.forEach(col => { col.items = [] })
        items.forEach((item: any) => {
            const col = columns.value.find(c => c.id === String(item.label_id))
            if (col) col.items.push(item)
        })
    } catch (error) {
        console.error('Failed to fetch items for columns:', error)
    }
}

function rebuildColumnsFromSelection() {
    if (!selectedColumnLabels.value.length) return
    columns.value = selectedColumnLabels.value.map(l => ({
        id: String(l.id),
        title: l.name,
        items: [],
    }))
}

watch([selectedColumnLabels, columnType, rowType], async () => {
    rebuildColumnsFromSelection()
    await fetchItemsForColumns()
})

// onMounted(async () => {
//     await fetchLabelsForGroup()
//     await fetchItemsForColumns()

//     // fallback: distribute evenly when not using a label group
//     if (!props.labelGroupId) {
//         const res = await axios.get(route(props.routeName), {
//             headers: { Authorization: `Bearer ${props.authToken}` },
//         })
//         const items = res.data.data
//         const labels = res.data?.sideloaded?.labels ?? []
//         labelMap.value = Object.fromEntries(labels.map((l: any) => [Number(l.id), l]))
//         emit('update:labelMap', labelMap.value)

//         if (columns.value.length === 0) return
//         columns.value.forEach((col) => (col.items = []))
//         items.forEach((item, idx) => {
//             const targetCol = columns.value[idx % columns.value.length]
//             if (targetCol) targetCol.items.push(item)
//         })
//     }
// })

// resources/js/components/Views/Boards/BoardView.vue
// UPDATE: onMounted to use saved controls instead of hard-loading labels

onMounted(async () => {
  // 1) Build columns from saved controls (no UI toggle required)
  loadControlsStateFromStorage()

  // 2) If we got a column set, fetch the items for those columns
  if (columns.value.length) {
    await fetchItemsForColumns()
  } else if (props.labelGroupId) {
    // Optional fallback: if nothing saved yet but a label group is provided,
    // you can still hydrate from the API. Otherwise, keep it empty until user configures.
    await fetchLabelsForGroup()
  }

  // 3) Fallback for “no label group mode” stays the same
  if (!props.labelGroupId) {
    const res = await axios.get(route(props.routeName), {
      headers: { Authorization: `Bearer ${props.authToken}` },
    })
    const items = res.data?.data ?? []
    const labels = res.data?.sideloaded?.labels ?? []
    labelMap.value = Object.fromEntries(labels.map((l: any) => [Number(l.id), l]))
    emit('update:labelMap', labelMap.value)

    if (!columns.value.length) return
    columns.value.forEach(c => (c.items = []))
    items.forEach((item: any, idx: number) => {
      const target = columns.value[idx % columns.value.length]
      if (target) target.items.push(item)
    })
  }
})


const columnMapDisplay = computed(() => columnMap.value)

// ---------- Persist view (debounced) ----------
const activeViewId = ref<number | null>(null)

const persistView = debounce(async () => {
    // const headers = { Authorization: `Bearer ${props.authToken}` }
    // const v = latestViewState.value

    // // Normalize everything to primitive IDs the API expects
    // const labelGroups = (v?.labelSelection ?? [])
    //     .map((x: any) => Number(x))
    //     .filter((n: any) => Number.isFinite(n))

    // const selectedItemIds = (v?.selectedItems ?? [])
    //     .map((x: any) => Number(x?.id ?? x))
    //     .filter((n: any) => Number.isFinite(n))

    // const payload = {
    //     name: 'Board View',
    //     view_type: 'board',
    //     attributes: {
    //         group_by_type: columnType.value ?? null,
    //         row_type: rowType.value ?? null,
    //         label_groups: labelGroups,
    //         selected_items: selectedItemIds,
    //     },
    // }

    // try {
    //     if (activeViewId.value) {
    //         await axios.put(`/api/views/${activeViewId.value}`, payload, { headers })
    //     } else {
    //         const { data } = await axios.post('/api/views', payload, { headers })
    //         activeViewId.value = data?.data?.id ?? null
    //     }
    // } catch (err: any) {
    //     // Helpful while tuning validation:
    //     console.error('Persist view failed:', err?.response?.data ?? err)
    // }
}, 500)

watch([selectedColumnLabels, columnType, rowType, latestViewState], () => {
    persistView()
})

async function patchField(model: string, id: number | string, key: string, value: any, headers: any) {
    return axios.patch(`/api/fields/${model}/${id}`, { key, value }, { headers })
}

/**
 * Optimistic drop handler:
 * - UI already reflects the move (vuedraggable).
 * - Mark the card as updating.
 * - PATCH server with { key, value } payload.
 * - On failure: move it back to the source column.
 */
async function handleCardDropped(payload: {
    item: any
    fromColumnId: string
    toColumnId: string
    toIndex: number
}) {
    const headers = { Authorization: `Bearer ${props.authToken}` }
    const model = props.modelName ?? 'contact'

    let keyToUpdate: string | null = null
    let valueToSet: string | number | null = null

    switch (columnType.value) {
        case 'labels':
            keyToUpdate = 'label_id'
            valueToSet = Number(payload.toColumnId)
            break
        default:
            keyToUpdate = null
    }
    if (!keyToUpdate) return

    const toCol = columns.value.find(c => c.id === payload.toColumnId)
    const fromCol = columns.value.find(c => c.id === payload.fromColumnId)
    if (!toCol || !fromCol) return

    payload.item.__optimisticUpdating = true

    try {
        await patchField(model, payload.item.id, keyToUpdate, valueToSet, headers)
        if (keyToUpdate === 'label_id') payload.item.label_id = valueToSet
    } catch (err) {
        // rollback
        const idx = toCol.items.findIndex(i => i.id === payload.item.id)
        if (idx >= 0) {
            const [moved] = toCol.items.splice(idx, 1)
            fromCol.items.push(moved)
        }
        if (keyToUpdate === 'label_id') payload.item.label_id = Number(payload.fromColumnId) || null
        console.error('Drop update failed:', err)
    } finally {
        payload.item.__optimisticUpdating = false
    }
}
</script>

<template>
    <div class="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-4 px-2 md:px-0 h-full">
        <BoardControls
            v-if="props.showBoardControls"
            :token="authToken"
            :endpoint="'/api/labels'"
            :option-label="'name'"
            :option-value="'id'"
            :storage-key="`board:${props.routeName}${props.labelGroupId ? ':lg=' + props.labelGroupId : ''}`"
            @update:columnType="val => columnType = val"
            @update:rowType="val => rowType = val"
            @update:labelSelection="(_)=>{}"
            @update:selectedItems="val => { selectedColumnLabels = val }"
            @update:viewState="v => { latestViewState = v }"
        />

        <BoardColumn
            v-for="col in columns"
            :key="col.id"
            :id="col.id"
            :title="col.title"
            :items="col.items"
            :label-map="labelMap"
            @dropped="handleCardDropped"
        />
    </div>
</template>
