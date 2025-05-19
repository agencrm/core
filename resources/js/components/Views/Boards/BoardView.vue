<script setup lang="ts">
import { ref, watch, computed, onMounted } from 'vue'
import axios from 'axios'
import { route } from 'ziggy-js'

import BoardControls from '@/components/Views/Boards/BoardControls.vue'
import BoardColumn from '@/components/Views/Boards/BoardColumn.vue'

const props = defineProps<{
  routeName: string
  authToken: string
    showBoardControls?: boolean
}>()

// Load selectedItems from localStorage immediately
const savedItems = JSON.parse(localStorage.getItem('label-drag-selected') || '[]')
const selectedItems = ref<Array<{ id: number; name: string; group_id?: string }>>(
  Array.isArray(savedItems) ? savedItems : []
)

const columns = ref<Array<{ id: string; title: string; items: any[] }>>([])
const fetchedData = ref<any>(null)

// Compute columnMap from selectedItems
const columnMap = computed(() =>
  Object.fromEntries(selectedItems.value.map(item => [item.id, item]))
)

// Watch for selectedItems change and fetch new board data
watch(selectedItems, async (val) => {
  console.log('📌 selectedItems updated:', val)

  const labelIds = val.map(i => i.id)
  if (!labelIds.length) {
    columns.value = []
    fetchedData.value = null
    console.log('⚠️ No labels selected; clearing columns')
    return
  }

  try {
    const res = await axios.get(route(props.routeName), {
      headers: {
        Authorization: `Bearer ${props.authToken}`,
      },
      params: { label_ids: labelIds },
    })

    console.log('📦 API response:', res.data)
    fetchedData.value = res.data

    const items = res.data.data
    const labels = res.data?.sideloaded?.labels ?? []

    // columns.value = labels.map((l: any) => ({
    //   id: String(l.id),
    //   title: l.name,
    //   items: [],
    // }))


      columns.value = selectedItems.value.map((item) => ({
      id: String(item.id),
      title: item.name,
      items: [],
    }))

    items.forEach((item: any) => {
      const col = columns.value.find(c => c.id === String(item.label_id))
      if (col) col.items.push(item)
    })

    console.log('✅ Columns after bucketing items:', columns.value)
  } catch (error) {
    console.error('❌ API fetch failed:', error)
    fetchedData.value = { error: String(error) }
  }
}, { immediate: true })


const labelMap = ref<Record<number, { id: number; name: string }>>({})

onMounted(async () => {
  const res = await axios.get(route(props.routeName), {
    headers: {
      Authorization: `Bearer ${props.authToken}`,
    },
  })

  const items = res.data.data
  const labels = res.data?.sideloaded?.labels ?? []
  labelMap.value = Object.fromEntries(labels.map((l: any) => [Number(l.id), l]))
  emit('update:labelMap', labelMap.value)

  columns.value.forEach((col) => (col.items = []))
  items.forEach((item, idx) => {
    columns.value[idx % 3].items.push(item)
  })
})


</script>

<template>
  <div class="flex flex-col gap-4 px-2 md:px-0">
    <BoardControls
      v-if="props.showBoardControls"
      :token="authToken"
      :route-name="routeName"
      :auth-token="authToken"
      @update:selected-items="val => selectedItems.value = val"
    />

    <!-- Debug output -->
    <!-- <pre class="text-xs bg-yellow-100 text-red-600 p-2 rounded overflow-x-auto max-h-48">
    selectedItems: {{ JSON.stringify(selectedItems, null, 2) }}
    </pre> -->

    <!-- Debug output -->
    <!-- <pre class="text-xs bg-yellow-100 text-red-600 p-2 rounded overflow-x-auto max-h-48">
    columns: {{ JSON.stringify(columns, null, 2) }}
    </pre> -->

    <!-- Debug output -->
    <!-- <pre class="text-xs bg-yellow-100 text-red-600 p-2 rounded overflow-x-auto max-h-48">
    columnMap: {{ JSON.stringify(columnMap, null, 2) }}
    </pre> -->

    <!-- Raw fetched API data -->
    <!-- <pre class="text-xs bg-blue-100 text-blue-800 p-2 rounded overflow-x-auto max-h-48">
fetchedData: {{ JSON.stringify(fetchedData, null, 2) }}
    </pre> -->

    <div class="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-4 h-full">
      <BoardColumn
        v-for="col in columns"
        :key="col.id"
        :id="col.id"
        :title="col.title"
        :items="col.items"
        :column-map="columnMap"
        :label-map="labelMap"
        :labels="labels"
      />
    </div>
  </div>
</template>
