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
}>()

const emit = defineEmits(['update:labelMap'])

const columns = ref<Array<{ id: string; title: string; items: any[] }>>([])
const labelMap = ref<Record<number, { id: number; name: string }>>({})
const columnMap = ref<any>(null)
const rowMap = ref<any>(null)

const columnType = ref<string | null>(null)
const rowType = ref<string | null>(null)
const selectedColumnLabels = ref<Array<{ id: number; name: string }>>([])

// NEW: capture full view state from controls (fixes undefined availableItems usage)
const latestViewState = ref<{
    columnType: string | null
    rowType: string | null
    labelSelection: Array<string | number>
    selectedItems: Array<{ id: number; name: string }>
} | null>(null)

async function fetchLabelsForGroup() {
    if (!props.labelGroupId) return
    try {
        const res = await axios.get(route('api.labels.index'), {
            headers: { Authorization: `Bearer ${props.authToken}` },
            params: { group_ids: [props.labelGroupId] },
        })
        const labels = res.data?.data ?? []
        columnMap.value = res.data
        labelMap.value = Object.fromEntries(labels.map((l: any) => [Number(l.id), l]))

        if (!selectedColumnLabels.value.length) {
            columns.value = labels.map((label: any) => ({
                id: String(label.id),
                title: label.name,
                items: [],
            }))
        }
        emit('update:labelMap', labelMap.value)
    } catch (error) {
        columnMap.value = { error: String(error) }
    }
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

onMounted(async () => {
    await fetchLabelsForGroup()
    await fetchItemsForColumns()

    if (!props.labelGroupId) {
        const res = await axios.get(route(props.routeName), {
            headers: { Authorization: `Bearer ${props.authToken}` },
        })
        const items = res.data.data
        const labels = res.data?.sideloaded?.labels ?? []
        labelMap.value = Object.fromEntries(labels.map((l: any) => [Number(l.id), l]))
        emit('update:labelMap', labelMap.value)

        if (columns.value.length === 0) return
        columns.value.forEach((col) => (col.items = []))
        items.forEach((item, idx) => {
            const targetCol = columns.value[idx % columns.value.length]
            if (targetCol) targetCol.items.push(item)
        })
    }
})

const columnMapDisplay = computed(() => columnMap.value)

// ---------- Optional: persist view to API (debounced) ----------
const activeViewId = ref<number | null>(null)

const persistView = debounce(async () => {
    const headers = { Authorization: `Bearer ${props.authToken}` }
    const v = latestViewState.value
    const payload = {
        name: 'Board View',
        view_type: 'board',
        attributes: {
            group_by_type: columnType.value,
            row_type: rowType.value,
            label_groups: v?.labelSelection ?? [],
            selected_items: v?.selectedItems ?? [],
        },
    }
    if (activeViewId.value) {
        await axios.put(`/api/views/${activeViewId.value}`, payload, { headers })
    } else {
        const { data } = await axios.post('/api/views', payload, { headers })
        activeViewId.value = data?.data?.id ?? null
    }
}, 500)

watch([selectedColumnLabels, columnType, rowType, latestViewState], () => {
    persistView()
})
</script>

<template>


    <!-- <pre class="bg-black text-white text-sm mt-4 p-2 rounded overflow-auto max-h-64">
        {{ JSON.stringify(columnMapDisplay, null, 2) }}
    </pre>

    <pre
        v-if="Object.keys(labelMap).length"
        class="bg-black text-white text-sm mt-4 p-2 rounded overflow-auto max-h-64"
    >
        {{ JSON.stringify(labelMap, null, 2) }}
    </pre> -->

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
            @update:labelSelection="/* noop for now */(_)=>{}"
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
        />
    </div>
</template>
