<script setup lang="ts">
// resources/js/components/Views/Boards/BoardCard.vue

import { GripVertical } from 'lucide-vue-next'
import CellEditableFieldLabel from '@/components/Fields/InPlaceEditable/Label.vue'

const props = defineProps<{
  item: any
  isOverlay?: boolean
  isGhost?: boolean
  labelMap?: Record<number, { id: number; name: string }>
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
    <!-- Header with Label -->
    <div class="p-3 border-b-2 border-secondary flex items-center gap-2">
      <button
        class="inline-flex items-center justify-center rounded-md p-1 text-secondary-foreground/50 -ml-2 cursor-grab hover:bg-accent hover:text-accent-foreground"
        aria-label="Move task"
      >
        <GripVertical class="w-4 h-4" />
      </button>

      <!-- Editable Label Field -->
      <CellEditableFieldLabel
        model="contact"
        :model-id="item.id"
        token="apiKey"
        :value="item.label_id"
        :label-map="labelMap"
      />
    </div>

    <!-- Main Content -->
    <div class="p-3 text-left text-sm whitespace-pre-wrap">
      {{ item.first_name }} {{ item.last_name }}
      <br />
      <span class="text-xs text-muted-foreground">
        {{ item.email }}
      </span>
    </div>
  </div>
</template>
