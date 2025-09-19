<script setup lang="ts">
// resources/js/components/Views/Boards/BoardCard.vue

import { GripVertical, SquareArrowOutUpRight } from 'lucide-vue-next'
import { route } from 'ziggy-js'
import Modal from '@/components/Modal/Modal.vue'
import CellEditableFieldLabel from '@/components/Fields/InPlaceEditable/Label.vue'

const props = defineProps<{
  item: any
  isOverlay?: boolean
  isGhost?: boolean
  labelMap?: Record<number, { id: number; name: string }>
}>()

const href = typeof route === 'function'
  ? route('flows.show', props.item?.id)
  : `/flows/${props.item?.id}`
</script>

<template>
  <div
    class="rounded-lg border bg-card text-card-foreground shadow-sm transition-all"
    :class="{
      'ring-2 ring-indigo-500': isGhost,
      'opacity-30': isOverlay,
      'opacity-70 pointer-events-none': item.__optimisticUpdating === true
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
      <Modal
        :id="item.id"
        :href="href"
        :title="`Contact #${item.id}`"
        subtitle="Open the full page or close."
        content-class="w-[32rem] max-w-[95vw]"
        storage-key="ui.modal.boardCard"
      >
        <template #trigger>
          <button class="inline-flex items-center gap-1 font-medium underline-offset-2 hover:underline focus:outline-none">
            <span>{{ item.first_name }} {{ item.last_name }}</span>
            <SquareArrowOutUpRight class="w-3.5 h-3.5 opacity-70" aria-hidden="true" />
          </button>
        </template>

        <div class="text-sm">
          <div class="text-muted-foreground">{{ item.email }}</div>
        </div>
      </Modal>

      <br />
      <span class="text-xs text-muted-foreground">
        {{ item.email }}
      </span>
    </div>
  </div>
</template>
