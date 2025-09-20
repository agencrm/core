<script setup lang="ts">
// resources/js/components/Views/Boards/BoardColumn.vue

import { ref, computed } from 'vue'
import draggable, { type StartEvent } from 'vuedraggable'
import { GripVertical } from 'lucide-vue-next'
import BoardCard from './BoardCard.vue'
import { ScrollArea } from '@/components/ui/scroll-area'

const props = defineProps<{
    id: string
    title: string
    items: { id: string | number; [key: string]: any }[]
    labelMap: Record<number, { id: number; name: string; color?: string }>
    columnMap?: Record<number, { id: number; name: string; group_id?: string }>
}>()

const emit = defineEmits<{
    (e: 'dropped', payload: {
        item: any
        fromColumnId: string
        toColumnId: string
        toIndex: number
    }): void
}>()

const draggingItemId = ref<string | number | null>(null)
const isOver = ref(false)

// Tracks where an item came from so the target column can emit a single, clean "dropped" payload.
const lastSourceByItem = new Map<string | number, string>()

function isDraggingItem(id: string | number) {
    return draggingItemId.value === id
}

function handleStart(e: StartEvent) {
    const domId = e?.item?.id
    draggingItemId.value = domId ?? null
}

function handleEnd() {
    draggingItemId.value = null
}

/**
 * vuedraggable change event:
 * - When an item leaves this column, we get { removed: { element, oldIndex } } here and cache the source.
 * - When an item enters this column, we get { added: { element, newIndex } } and emit a single "dropped".
 */
function handleChange(evt: any) {
    if (evt?.removed) {
        const { element } = evt.removed
        if (element && (element.id !== undefined && element.id !== null)) {
            lastSourceByItem.set(element.id, props.id)
        }
    }

    if (evt?.added) {
        const { element, newIndex } = evt.added
        if (!element) return

        const fromColumnId =
            lastSourceByItem.get(element.id) ??
            (element.label_id != null ? String(element.label_id) : props.id)

        lastSourceByItem.delete(element.id)

        emit('dropped', {
            item: element,
            fromColumnId: String(fromColumnId),
            toColumnId: props.id,
            toIndex: Number(newIndex ?? 0),
        })
    }
}

/**
 * Header border style resolves the label color (hex like #e01b24) if available.
 * Fallback uses the theme border color so it still looks intentional.
 */
const headerStyle = computed(() => {
    const color = props.labelMap?.[Number(props.id)]?.color
    return {
        // thick bottom border; adjust width if you want it thinner/thicker
        borderBottom: `4px solid ${color && /^#|rgb|hsl/i.test(color) ? color : 'var(--border)'}`,
    } as Record<string, string>
})
</script>

<template>
    <div
        class="h-full w-[350px] flex flex-col flex-shrink-0 snap-center rounded-lg border bg-primary-foreground text-card-foreground shadow-sm"
    >
        <!-- Thick bottom border in label color -->
        <div class="p-4 font-semibold flex items-center border-b-0" :style="headerStyle">
            <button
                class="inline-flex items-center justify-center rounded-md p-1 text-primary/50 -ml-2 cursor-grab hover:bg-accent hover:text-accent-foreground"
                aria-label="Move column"
            >
                <GripVertical class="w-4 h-4" />
            </button>
            <span class="ml-auto">{{ title }}</span>
        </div>

        <ScrollArea class="flex-1 overflow-hidden min-h-[7rem] board-column-scrollarea">
            <div class="p-2 h-full">
                <draggable
                    :list="items"
                    group="tasks"
                    item-key="id"
                    class="flex flex-col gap-2 border-2 min-h-full w-full rounded-md transition-colors duration-200"
                    :class="items.length === 0
                        ? (isOver ? 'border-dashed border-muted-foreground/50' : 'border-dashed border-muted-foreground/10')
                        : 'border-transparent'"
                    :empty-insert-threshold="60"
                    @dragover.prevent="isOver = true"
                    @dragleave="isOver = false"
                    @drop="isOver = false"
                    @start="handleStart"
                    @end="handleEnd"
                    @change="handleChange"
                    ghost-class="drag-ghost"
                    animation="200"
                >
                    <template #item="{ element }">
                        <BoardCard
                            :item="element"
                            :label-map="labelMap"
                            :is-ghost="isDraggingItem(element.id)"
                        />
                    </template>

                    <template #clone="{ element }">
                        <BoardCard :item="element" is-overlay />
                    </template>
                </draggable>
            </div>
        </ScrollArea>
    </div>
</template>

<style scoped lang="scss">
.drag-ghost {
    @apply opacity-30;
}
</style>

<style lang="scss">
.board-column-scrollarea [data-reka-scroll-area-viewport] {
    min-height: 7rem;

    > div {
        height: 100%;
        min-height: 7rem;
    }

    .h-full {
        &, .min-h-full {
            min-height: 7rem;
        }
    }
}
</style>
