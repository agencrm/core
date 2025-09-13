<script setup lang="ts">
// resources/js/components/Views/Boards/BoardView.vue

/**
 * DO NOT REMOVE
 *
 * 1) fetch columns
 * 2) fetch rows (if exist)
 * 3) fetch items based on 1 & 2
 */

import { ref, watch, computed, onMounted } from 'vue'
import axios from 'axios'
import { route } from 'ziggy-js'

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

const fetchLabelsForGroup = async () => {
    if (!props.labelGroupId) return

    try {
        const res = await axios.get(route('api.labels.index'), {
            headers: {
                Authorization: `Bearer ${props.authToken}`,
            },
            params: {
                group_ids: [props.labelGroupId],
            },
        })

        console.group(`[fetchLabelsForGroup] labelGroupId = ${props.labelGroupId}`)
        console.log('📦 Full API response:', res.data)

        const labels = res.data?.data ?? []
        console.log('🧷 Extracted labels:', labels)

        columnMap.value = res.data // store full response
        labelMap.value = Object.fromEntries(labels.map((l: any) => [Number(l.id), l]))
        console.log('🗺️ labelMap constructed:', labelMap.value)

        columns.value = labels.map((label: any) => ({
            id: String(label.id),
            title: label.name,
            items: [],
        }))

        emit('update:labelMap', labelMap.value)
        console.groupEnd()
    } catch (error) {
        console.group(`[fetchLabelsForGroup] ERROR - labelGroupId = ${props.labelGroupId}`)
        console.error('❌ Failed to fetch labels for group:', error)
        console.groupEnd()

        columnMap.value = { error: String(error) }
    }
}

const fetchItemsForColumns = async () => {
    const labelIds = columns.value.map(col => col.id)
    if (!labelIds.length) return

    try {
        const res = await axios.get(route(props.routeName), {
            headers: {
                Authorization: `Bearer ${props.authToken}`,
            },
            params: {
                label_ids: labelIds,
            },
        })

        const items = res.data?.data ?? []

        // Clear previous items
        columns.value.forEach(col => col.items = [])

        // Bucket into correct column
        items.forEach((item: any) => {
            const col = columns.value.find(c => c.id === String(item.label_id))
            if (col) col.items.push(item)
        })
    } catch (error) {
        console.error('❌ Failed to fetch items for columns:', error)
    }
}


onMounted(async () => {
    await fetchLabelsForGroup()
    await fetchItemsForColumns()

    if (!props.labelGroupId) {
        const res = await axios.get(route(props.routeName), {
            headers: {
                Authorization: `Bearer ${props.authToken}`,
            },
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
            :route-name="routeName"
            :auth-token="authToken"
            @update:selected-items="val => selectedItems.value = val"
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
