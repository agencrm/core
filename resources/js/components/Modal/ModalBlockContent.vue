<script setup lang="ts">
// resources/js/components/Modal/ModalBlockContent.vue

import { defineAsyncComponent, computed } from 'vue'
import {
  Accordion,
  AccordionContent,
  AccordionItem,
  AccordionTrigger,
} from '@/components/ui/accordion'

// Lazy-load block components so unused ones don't affect bundle
const BlockRegistry = {
  fields: defineAsyncComponent(() => import('./Blocks/Fields.vue')),
  notes: defineAsyncComponent(() => import('./Blocks/Notes/NotesBlock.vue')),
  comments: defineAsyncComponent(() => import('./Blocks/Comments/CommentsBlock.vue')),
} as const

type BlockKey = keyof typeof BlockRegistry
type BlockSpec =
  | BlockKey
  | {
      key: BlockKey
      title?: string
      props?: Record<string, any>
    }

const props = defineProps<{
  id: number | string
  blocks: BlockSpec[]
}>()

const normalized = computed(() =>
  (props.blocks || []).map((b) => {
    if (typeof b === 'string') {
      return { key: b as BlockKey, title: defaultTitle(b as BlockKey), props: {} }
    }
    return {
      key: b.key,
      title: b.title ?? defaultTitle(b.key),
      props: b.props ?? {},
    }
  })
)

function defaultTitle(k: BlockKey) {
  switch (k) {
    case 'fields':
      return 'Fields'
    case 'notes':
      return 'Notes'
    case 'comments':
      return 'Comments'
    default:
      return 'Section'
  }
}
</script>

<template>
  <Accordion type="multiple" class="w-full">
    <AccordionItem
      v-for="(b, i) in normalized"
      :key="`${String(id)}-${b.key}-${i}`"
      :value="`${b.key}-${i}`"
      class="border-b"
    >
      <AccordionTrigger class="text-sm font-medium">
        {{ b.title }}
      </AccordionTrigger>
      <AccordionContent>
        <component
          :is="BlockRegistry[b.key]"
          v-bind="{ id, ...(b.props || {}) }"
        />
      </AccordionContent>
    </AccordionItem>
  </Accordion>
</template>
