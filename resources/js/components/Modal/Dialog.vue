<script setup lang="ts">
// resources/js/components/Modal/Dialog.vue
import { useSlots, computed } from 'vue'
import {
  //DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogClose,   // 👈 add this
} from '@/components/ui/dialog'

import { X } from 'lucide-vue-next'

import DialogContent from '@/components/Modal/DialogContent.vue';

import ModalBlockContent from '@/components/Modal/ModalBlockContent.vue'
import ModalControls from '@/components/Modal/Controls.vue';

type BlockKey = 'fields' | 'notes' | 'comments'
type BlockSpec = BlockKey | { key: BlockKey; title?: string; props?: Record<string, any> }

const props = defineProps<{
  id: number | string
  href?: string
  title?: string
  subtitle?: string
  blocks?: BlockSpec[]
  showFooter?: boolean    
}>()

const emit = defineEmits<{ (e: 'close'): void }>()
const slots = useSlots()
const hasFooter = computed(() => {
  const fn = slots.footer
  if (!fn) return false
  // Render once and see if there are any non-empty nodes
  const nodes = fn()
  return nodes.some(n => {
    if (n.type === Comment) return false
    if (typeof n.children === 'string') return n.children.trim().length > 0
    return true // any element/fragment counts as content
  })
})
</script>

<template>
<DialogContent class="sm:max-w-lg" :hide-close="true">
<DialogHeader
  class="flex flex-row items-start sm:items-center justify-between m-0 pb-4 mb-6 gap-2 border-b border-muted/70"
>
    <div class="flex flex-col flex-1 min-w-0">
        <DialogTitle class="truncate">{{ props.title ?? `Contact #${props.id}` }}</DialogTitle>
        <DialogDescription class="text-sm text-muted-foreground truncate">
        {{ props.subtitle ?? 'Quick actions and details' }}
        </DialogDescription>
    </div>

  <!-- controls and inline close -->
    <div class="flex items-center gap-1">
        <slot name="headerExtra" />
        <DialogClose as-child>
        <button
            type="button"
            class="ml-1 inline-flex items-center justify-center rounded-md hover:bg-accent hover:text-accent-foreground h-8 w-8"
        >
            <span class="sr-only">Close</span>
            <X class="size-4" />
        </button>
        </DialogClose>
    </div>
</DialogHeader>


        <slot>
            <div class="space-y-3">
                <template v-if="props.blocks && props.blocks.length">
                    <ModalBlockContent :id="props.id" :blocks="props.blocks" />
                </template>
                <template v-else>
                    <div class="text-sm text-muted-foreground">
                        ID: <span class="font-medium text-foreground">{{ props.id }}</span>
                    </div>
                </template>
            </div>
        </slot>

        <!-- Footer only when populated via slot -->
        <div v-if="hasFooter" class="mt-6 flex items-center gap-2 border-t border-muted/70 pt-4">
            <slot name="footer" />
        </div>
    </DialogContent>
</template>
