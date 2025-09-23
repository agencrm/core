<script setup lang="ts">

// resources/js/components/Views/Boards/BoardCard.vue

import { GripVertical } from 'lucide-vue-next'
import { route } from 'ziggy-js'
import { computed } from 'vue'
import Modal from '@/components/Modal/Modal.vue'

type BlockDef = { key: string; props?: Record<string, any> }

const props = defineProps<{
  item: any
  isOverlay?: boolean
  isGhost?: boolean
  labelMap?: Record<number, { id: number; name: string }>
  blocks?: BlockDef[]
  resolveBlocks?: (ctx: { item: any }) => BlockDef[]
  modalTitle?: string | ((item: any) => string)
  modalSubtitle?: string | ((item: any) => string)
  modalContentClass?: string
  modalStorageKey?: string
  token?: string
  borderColor?: string
    modalShowFooter?: boolean
}>()

const href = typeof route === 'function'
  ? route('contacts.show', props.item?.id)
  : `/contacts/${props.item?.id}`

const headerBorderColor = computed(() => props.borderColor ?? 'var(--border)')
const gripColor = computed(() => props.borderColor ?? 'var(--muted-foreground)')

const resolvedBlocks = computed<BlockDef[]>(() =>
  typeof props.resolveBlocks === 'function'
    ? props.resolveBlocks({ item: props.item }) ?? []
    : (Array.isArray(props.blocks) ? props.blocks : [])
)

function resolveStr(v?: string | ((item:any)=>string), fallback?: string) {
  return typeof v === 'function' ? v(props.item) : (v ?? fallback ?? '')
}
const computedTitle = computed(() => resolveStr(props.modalTitle, `Contact #${props.item?.id}`))
const computedSubtitle = computed(() => resolveStr(props.modalSubtitle, 'Open the full page or close.'))
const computedContentClass = computed(() => props.modalContentClass ?? 'w-[32rem] max-w-[95vw]')
const computedStorageKey = computed(() => props.modalStorageKey ?? 'ui.modal.boardCard')
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
    <!-- Header -->
    <div
      class="p-3 border-b-2 flex items-center gap-2"
    >
      <button
        class="inline-flex items-center justify-center rounded-md p-1 -ml-2 cursor-grab hover:bg-accent"
        aria-label="Move card"
        :color="gripColor" 
      >
        <!-- drive icon color explicitly -->
        <GripVertical class="w-4 h-4" :color="gripColor" />
      </button>

      <Modal
  :id="item.id"
  :href="href"
  :title="computedTitle"
  :subtitle="computedSubtitle"
  :content-class="computedContentClass"
  :storage-key="computedStorageKey"
  :blocks="resolvedBlocks"
  :show-footer="modalShowFooter === undefined ? false : modalShowFooter"
      >
        <template #trigger>
          <button class="inline-flex items-center gap-1 font-medium underline-offset-2 hover:underline focus:outline-none">
            <span>{{ item.first_name }} {{ item.last_name }}</span>
          </button>
        </template>





      </Modal>
    </div>

    <!-- Main Content -->
    <div class="p-3 text-left text-sm whitespace-pre-wrap">
      <span class="text-xs text-muted-foreground">{{ item.email }}</span>

    </div>
  </div>
</template>
