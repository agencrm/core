<script setup lang="ts">
// resources/js/components/Modal/Tray.vue
import { useSlots, computed } from 'vue'
import SheetContent from '@/components/Modal/SheetContent.vue'
import { 
    //SheetContent, 
    SheetHeader, SheetTitle, SheetDescription, SheetFooter } from '@/components/ui/sheet'
import ModalBlockContent from '@/components/Modal/ModalBlockContent.vue'
import { DialogClose } from '@/components/ui/dialog'
import { X } from 'lucide-vue-next'

type BlockKey = 'fields' | 'notes' | 'comments'
type BlockSpec = BlockKey | { key: BlockKey; title?: string; props?: Record<string, any> }

const props = defineProps<{
  id: number | string
  href?: string
  title?: string
  subtitle?: string
  contentClass?: string
  blocks?: BlockSpec[]
}>()

const emit = defineEmits<{ (e: 'close'): void }>()
const slots = useSlots()

// Show footer ONLY if a footer slot is actually provided
const hasFooter = computed(() => Boolean(slots.footer))
</script>

<template>
  <SheetContent
    side="right"
    :hide-close="true"                                 
    :class="props.contentClass ?? 'w-[30rem] max-w-[90vw]'"
  >
    <SheetHeader
      class="flex flex-row items-start sm:items-center justify-between m-0 pb-4 mb-6 gap-2 border-b border-muted/70"
    >
      <div class="flex flex-col flex-1 min-w-0">
        <SheetTitle class="truncate">
          {{ props.title ?? `Contact #${props.id}` }}
        </SheetTitle>
        <SheetDescription class="text-sm text-muted-foreground truncate">
          {{ props.subtitle ?? 'Quick actions and details' }}
        </SheetDescription>
      </div>

      <!-- right side: controls dropdown + inline close -->
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
    </SheetHeader>

    <div class="mt-4 space-y-3 p-4">
      <slot>
        <template v-if="props.blocks && props.blocks.length">
          <ModalBlockContent :id="props.id" :blocks="props.blocks" />
        </template>
        <template v-else>
          <div class="text-sm text-muted-foreground">
            ID: <span class="font-medium text-foreground">{{ props.id }}</span>
          </div>
        </template>
      </slot>
    </div>

    <!-- Footer only when populated via slot -->
    <SheetFooter v-if="hasFooter" class="mt-6 gap-2 border-t border-muted/70 pt-4">
      <slot name="footer" />
    </SheetFooter>
  </SheetContent>
</template>
