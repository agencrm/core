<script setup lang="ts">
// resources/js/components/Modal/Controls.vue
import { ref } from 'vue'
import { SlidersVertical } from 'lucide-vue-next'
import { Button } from '@/components/ui/button'
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuLabel,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu'

const props = defineProps<{ modelValue: 'dialog' | 'tray' }>()
const emit  = defineEmits<{ (e: 'update:modelValue', v: 'dialog' | 'tray'): void }>()

// OPTIONAL: close the dropdown after clicking a pill
const open = ref(false)
function set(v: 'dialog' | 'tray') {
  emit('update:modelValue', v)
  open.value = false // remove this line if you prefer it to stay open
}
</script>

<template>
  <DropdownMenu v-model:open="open">
    <DropdownMenuTrigger as-child>
      <Button type="button" variant="ghost" size="icon" title="Controls" class="h-8 w-8">
        <SlidersVertical class="h-4 w-4" />
      </Button>
    </DropdownMenuTrigger>

    <DropdownMenuContent align="end" class="w-48">
      <DropdownMenuLabel>Layout</DropdownMenuLabel>
      <DropdownMenuSeparator />

      <!-- pill toggle inside dropdown (NOT wrapped in DropdownMenuItem) -->
      <div class="p-2">
        <div class="inline-flex rounded-md border overflow-hidden text-xs">
          <button
            type="button"
            class="px-2 py-1"
            :class="props.modelValue === 'dialog'
              ? 'bg-accent text-accent-foreground'
              : 'bg-background text-foreground/80 hover:bg-muted'"
            @click="set('dialog')"
          >
            Dialog
          </button>
          <button
            type="button"
            class="px-2 py-1 border-l"
            :class="props.modelValue === 'tray'
              ? 'bg-accent text-accent-foreground'
              : 'bg-background text-foreground/80 hover:bg-muted'"
            @click="set('tray')"
          >
            Tray
          </button>
        </div>
      </div>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
