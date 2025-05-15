<script setup lang="ts">

// resources/js/components/Views/Boards/BoardView.vue

import { ref, onMounted } from 'vue'
import axios from 'axios'
import { route } from 'ziggy-js'
import BoardColumn from '@/components/Views/Boards/BoardColumn.vue'


const props = defineProps<{
  routeName: string
  authToken: string
}>()

const emit = defineEmits(['update:labelMap'])
const columns = ref([
  { id: 'todo', title: 'Todo', items: [] },
  { id: 'inprogress', title: 'In Progress', items: [] },
  { id: 'done', title: 'Done', items: [] },
])

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
  <div class="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-4 px-2 md:px-0 h-full">
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
