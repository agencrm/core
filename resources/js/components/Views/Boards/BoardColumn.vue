<script setup lang="ts">

    // resources/js/components/Views/Boards/BoardColumn.vue 

    import { ref } from 'vue'
    import draggable, { type StartEvent } from 'vuedraggable'
    import { GripVertical } from 'lucide-vue-next'
    import BoardCard from './BoardCard.vue'
    import { ScrollArea } from '@/components/ui/scroll-area'

    const props = defineProps<{
    id: string
    title: string
    items: { id: string | number; [key: string]: any }[]
    labelMap: Record<number, { id: number; name: string }>
    columnMap: Record<number, { id: number; name: string; group_id?: string }>
    }>()

    const draggingItemId = ref<string | number | null>(null)

    function isDraggingItem(id: string | number) {
    return draggingItemId.value === id
    }

    function handleStart(e: StartEvent) {
    draggingItemId.value = e.item?.id ?? null
    }

    function handleEnd() {
    draggingItemId.value = null
    }
    </script>

    <template>
    <div
        class="h-full w-[350px] flex flex-col flex-shrink-0 snap-center rounded-lg border bg-primary-foreground text-card-foreground shadow-sm"
    >
        <div class="p-4 font-semibold border-b-2 flex items-center">
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


<style  lang="scss">

.board-column-scrollarea  [data-reka-scroll-area-viewport] {
    // background:red;
    min-height:7rem;

    > div {
        // background:orange;
        height:100%;
        min-height:7rem;




    }

        .h-full {
            &, .min-h-full {
            min-height:7rem;    
            }

        }

}

</style>
