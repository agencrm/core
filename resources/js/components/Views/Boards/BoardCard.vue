// resources/js/components/Views/Boards/BoardCard.vue
<script setup lang="ts">
/**
 * Accept modal configuration and forward to <Modal>.
 * You can pass either:
 *  - blocks: an array, OR
 *  - resolveBlocks: (ctx) => blocks
 * Also accepts modal title/subtitle/contentClass/storageKey overrides.
 */

import { GripVertical, SquareArrowOutUpRight } from 'lucide-vue-next'
import { route } from 'ziggy-js'
import { computed } from 'vue'
import Modal from '@/components/Modal/Modal.vue'
import CellEditableFieldLabel from '@/components/Fields/InPlaceEditable/Label.vue'

type BlockDef = {
  key: string
  props?: Record<string, any>
}

const props = defineProps<{
  item: any
  isOverlay?: boolean
  isGhost?: boolean
  labelMap?: Record<number, { id: number; name: string }>

  // NEW: modal config inputs
  blocks?: BlockDef[]
  resolveBlocks?: (ctx: { item: any }) => BlockDef[]
  modalTitle?: string | ((item: any) => string)
  modalSubtitle?: string | ((item: any) => string)
  modalContentClass?: string
  modalStorageKey?: string

  // optional token for inline fields inside the card header
  token?: string
}>()

const href = typeof route === 'function'
  ? route('contacts.show', props.item?.id) // adjust if your route differs
  : `/contacts/${props.item?.id}`

// Resolve blocks (prefer resolver for per-item props)
const resolvedBlocks = computed<BlockDef[]>(() => {
  if (typeof props.resolveBlocks === 'function') {
    return props.resolveBlocks({ item: props.item }) ?? []
  }
  return Array.isArray(props.blocks) ? props.blocks : []
})

function resolveStr(v?: string | ((item:any)=>string), fallback?: string) {
  if (typeof v === 'function') return v(props.item)
  return v ?? fallback ?? ''
}

const computedTitle = computed(() =>
  resolveStr(props.modalTitle, `Contact #${props.item?.id}`)
)
const computedSubtitle = computed(() =>
  resolveStr(props.modalSubtitle, 'Open the full page or close.')
)
const computedContentClass = computed(() =>
  props.modalContentClass ?? 'w-[32rem] max-w-[95vw]'
)
const computedStorageKey = computed(() =>
  props.modalStorageKey ?? 'ui.modal.boardCard'
)
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
        aria-label="Move card"
      >
        <GripVertical class="w-4 h-4" />
      </button>

      <CellEditableFieldLabel
        model="contact"
        :model-id="item.id"
        :token="token"
        :value="item.label_id"
        :label-map="labelMap"
      />
    </div>

    <!-- Main Content -->
    <div class="p-3 text-left text-sm whitespace-pre-wrap">
      <Modal
        :id="item.id"
        :href="href"
        :title="computedTitle"
        :subtitle="computedSubtitle"
        :content-class="computedContentClass"
        :storage-key="computedStorageKey"
        :blocks="resolvedBlocks"
      >
        <template #trigger>
          <button class="inline-flex items-center gap-1 font-medium underline-offset-2 hover:underline focus:outline-none">
            <span>{{ item.first_name }} {{ item.last_name }}</span>
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
