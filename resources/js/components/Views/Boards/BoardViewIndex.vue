<script setup lang="ts">
import { ref, onMounted } from 'vue'
import axios from 'axios'
import { route } from 'ziggy-js'
import draggable from 'vuedraggable'
import { GripVertical } from 'lucide-vue-next'

const props = defineProps<{
  routeName: string
  authToken: string
}>()

const columns = ref([
  { id: 'todo', title: 'Todo', items: [] },
  { id: 'inprogress', title: 'In Progress', items: [] },
  { id: 'done', title: 'Done', items: [] },
])

const draggingIndex = ref<number | null>(null)

onMounted(async () => {
  const res = await axios.get(route(props.routeName), {
    headers: {
      Authorization: `Bearer ${props.authToken}`,
    },
  })

  const items = res.data.data
  columns.value.forEach((col) => (col.items = []))
  items.forEach((item, idx) => {
    columns.value[idx % 3].items.push(item)
  })
})
</script>

<template>
  <div class="mx-4 flex flex-col gap-6">
    <h1 class="text-4xl font-extrabold tracking-tight lg:text-5xl">
      Drag and Drop Kanban Board
    </h1>

    <div class="flex gap-4 overflow-x-auto snap-x snap-mandatory pb-4 px-2 md:px-0">
      <div
        v-for="col in columns"
        :key="col.id"
        class="rounded-lg border text-card-foreground shadow-sm h-[500px] max-h-[500px] w-[350px] max-w-full bg-primary-foreground flex flex-col flex-shrink-0 snap-center"
      >
        <div class="space-y-1.5 p-4 font-semibold border-b-2 flex items-center">
          <button
            class="inline-flex items-center justify-center rounded-md p-1 text-primary/50 -ml-2 cursor-grab hover:bg-accent hover:text-accent-foreground"
            aria-label="Move column"
          >
            <GripVertical class="w-4 h-4" />
          </button>
          <span class="ml-auto">{{ col.title }}</span>
        </div>

        <draggable
          :list="col.items"
          group="tasks"
          item-key="id"
          class="flex flex-col gap-2 p-2 overflow-y-auto"
          @start="e => draggingIndex.value = e.oldIndex"
          @end="() => draggingIndex.value = null"
        >
          <template #item="{ element, index }">
            <div
              class="rounded-lg border bg-card text-card-foreground shadow-sm transition-all"
              :class="{
                'outline outline-2 outline-primary ring-offset-2': draggingIndex === index
              }"
            >
              <div class="p-3 border-b-2 border-secondary flex items-center">
                <button
                  class="inline-flex items-center justify-center rounded-md p-1 text-secondary-foreground/50 -ml-2 cursor-grab hover:bg-accent hover:text-accent-foreground"
                  aria-label="Move task"
                >
                  <GripVertical class="w-4 h-4" />
                </button>
                <div class="ml-auto rounded-full border px-2.5 py-0.5 text-xs font-semibold">
                  Task
                </div>
              </div>
              <div class="p-3 text-left text-sm whitespace-pre-wrap">
                {{ element.first_name }} {{ element.last_name }}
                <br />
                <span class="text-xs text-muted-foreground">
                  {{ element.email }}
                </span>
              </div>
            </div>
          </template>
        </draggable>
      </div>
    </div>
  </div>
</template>
