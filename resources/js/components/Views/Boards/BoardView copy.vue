<script setup lang="ts">

// resources/js/components/Views/Boards/BoardView copy.vue

import { ref, watch } from 'vue'
import axios from 'axios'
import { route } from 'ziggy-js'

const apiKey = import.meta.env.APP_API_KEY

import BoardControls from '@/components/Views/Boards/BoardControls.vue'
import BoardColumn from '@/components/Views/Boards/BoardColumn.vue'

const props = defineProps<{
  routeName: string
  authToken: string
}>()

const emit = defineEmits(['update:labelMap'])

const columns = ref<
  { id: string; title: string; items: any[] }[]
>([])

const labelMap = ref<Record<number, { id: number; name: string }>>({})
const labelIds = ref<number[]>([])

watch(labelIds, async (val) => {
  console.log('📌 labelIds updated:', val)

  if (!val.length) {
    columns.value = []
    console.log('⚠️ No labels selected; clearing columns')
    return
  }

  try {
    const res = await axios.get(route(props.routeName), {
      headers: {
        Authorization: `Bearer ${props.authToken}`,
      },
      params: { label_ids: val },
    })

    console.log('📦 API response:', res.data)

    const items = res.data.data
    const labels = res.data?.sideloaded?.labels ?? []

    labelMap.value = Object.fromEntries(
      labels.map((l: any) => [Number(l.id), l])
    )
    emit('update:labelMap', labelMap.value)

    columns.value = labels.map((l: any) => ({
      id: String(l.id),
      title: l.name,
      items: [],
    }))

    console.log('📊 Columns initialized (empty items):', columns.value)

    items.forEach((item: any) => {
      const col = columns.value.find(c => c.id === String(item.label_id))
      if (col) col.items.push(item)
    })

    console.log('✅ Columns after bucketing items:', columns.value)

  } catch (error) {
    console.error('❌ API fetch failed:', error)
  }
}, { immediate: true })
</script>

<template>
  <div class="flex flex-col gap-4 px-2 md:px-0">
    <BoardControls
      :token="apiKey"
      :route-name="routeName"
      :auth-token="authToken"
      @update:label-ids="val => labelIds.value = val"
    />

    <!-- Debug output -->
    <pre class="text-xs bg-yellow-100 text-red-600 p-2 rounded overflow-x-auto max-h-48">
labelIds: {{ JSON.stringify(labelIds, null, 2) }}
columns: {{ JSON.stringify(columns, null, 2) }}
labelMap: {{ JSON.stringify(labelMap, null, 2) }}
    </pre>

    <div class="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-4 h-full">
      <BoardColumn
        v-for="col in columns"
        :key="col.id"
        :id="col.id"
        :title="col.title"
        :items="col.items"
        :label-map="labelMap"
      />
    </div>
  </div>
</template>
