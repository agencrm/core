<script setup lang="ts">
import { GripVertical } from 'lucide-vue-next'

const props = defineProps<{
  item: any
  isOverlay?: boolean
  isGhost?: boolean
}>()
</script>

<template>
  <div
    class="rounded-lg border bg-card text-card-foreground shadow-sm transition-all"
    :class="{
      'ring-2 ring-indigo-500': isGhost,
      'opacity-30': isOverlay
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
      {{ item.first_name }} {{ item.last_name }}
      <br />
      <span class="text-xs text-muted-foreground">
        {{ item.email }}
      </span>
    </div>
  </div>
</template>
