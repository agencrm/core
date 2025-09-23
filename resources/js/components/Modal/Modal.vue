<script setup lang="ts">
// resources/js/components/Modal/Modal.vue
import { ref, computed, watch } from 'vue'
import { Dialog as ShadDialog, DialogTrigger } from '@/components/ui/dialog'
import { Sheet, SheetTrigger } from '@/components/ui/sheet'
import ModalDialog from '@/components/Modal/Dialog.vue'
import ModalTray from '@/components/Modal/Tray.vue'
import ModalControls from '@/components/Modal/Controls.vue'
import { SquareArrowOutUpRight } from 'lucide-vue-next'

type BlockKey = 'fields' | 'notes' | 'comments'
type BlockSpec = BlockKey | { key: BlockKey; title?: string; props?: Record<string, any> }

const props = defineProps<{
    id: number | string
    href?: string
    title?: string
    subtitle?: string
    contentClass?: string
    storageKey?: string
    blocks?: BlockSpec[]
    showFooter?: boolean      
}>()

const prefKey = computed(() => props.storageKey ?? 'ui.modal.preferredStyle')
const preferred = ref<'dialog' | 'tray'>(readPref(prefKey.value))
const open = ref(false)

function readPref(key: string): 'dialog' | 'tray' {
  try { const v = localStorage.getItem(key); return v === 'tray' || v === 'dialog' ? v : 'dialog' } catch { return 'dialog' }
}
function writePref(key: string, v: 'dialog' | 'tray') { try { localStorage.setItem(key, v) } catch {} }
watch(preferred, (v) => writePref(prefKey.value, v))
</script>

<template>
  <component :is="preferred === 'dialog' ? ShadDialog : Sheet" v-model:open="open">
    <component :is="preferred === 'dialog' ? DialogTrigger : SheetTrigger" as-child>
      <button type="button" class="inline-flex items-center gap-1 hover:underline focus:outline-none">
        <slot name="trigger">{{ String(id) }}</slot>
        <SquareArrowOutUpRight class="h-3 w-3 opacity-70" />
      </button>
    </component>

    <ModalDialog
      v-if="preferred === 'dialog'"
      :id="id"
      :href="href"
      :title="title"
      :subtitle="subtitle"
      :blocks="blocks"
      :show-footer="showFooter"  
      @close="open = false"
    >
      <template #headerExtra>
        <ModalControls v-model="preferred" />
      </template>
      <slot />
    <template v-if="$slots.footer || showFooter === true" #footer>
        <slot name="footer" />
    </template>
    </ModalDialog>

    <ModalTray
      v-else
      :id="id"
      :href="href"
      :title="title"
      :subtitle="subtitle"
      :content-class="contentClass"
      :blocks="blocks"
      @close="open = false"
    >
      <template #headerExtra>
        <ModalControls v-model="preferred" />
      </template>
      <slot />
        <template v-if="$slots.footer || showFooter === true" #footer>
            <slot name="footer" />
        </template>
    </ModalTray>
  </component>
</template>
