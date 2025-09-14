
<script setup lang="ts">
// resources/js/components/Views/Boards/BoardControls.vue

import { ref, nextTick } from 'vue'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Input } from '@/components/ui/input'
import { Button } from '@/components/ui/button'
import BoardViewAttributes from '@/components/Views/Boards/BoardViewAttributes.vue'

import SavedViewsTable from '@/components/Views/SavedViewsTable.vue';

type ViewState = {
    columnType: string | null
    rowType: string | null
    labelSelection: Array<string | number>
    selectedItems: Array<{ id: number; name: string }>
}

type Preset = {
    id: string
    name: string
    createdAt: number
    updatedAt: number
    settings: ViewState
}

const props = defineProps<{
    columnOptions?: { value: string; label: string }[]
    rowOptions?: { value: string; label: string }[]
    token: string
    endpoint?: string
    optionLabel?: string
    optionValue?: string
    storageKey?: string   // NEW: namespace for localStorage (optional)
}>()

const emit = defineEmits([
    'update:columnType',
    'update:rowType',
    'update:labelSelection',
    'update:selectedItems',
    'update:viewState', // NEW: forward child’s composite state up to BoardView
])

// ---------- Saved Views (localStorage) ----------
const NS = props.storageKey ?? (typeof window !== 'undefined' ? window.location.pathname : 'default')
const PRESETS_KEY = `board-view-presets::${NS}`
const DEFAULT_ID_KEY = `board-view-default-id::${NS}`

const attrsRef = ref<any>(null)  // child expose handle
const newViewName = ref('')
const presets = ref<Preset[]>([])
const defaultId = ref<string | null>(null)
const latestViewState = ref<ViewState | null>(null) // keeps in sync with child via @update:viewState

function loadPresets(): Preset[] {
    try { return JSON.parse(localStorage.getItem(PRESETS_KEY) || '[]') } catch { return [] }
}
function savePresets(list: Preset[]) {
    localStorage.setItem(PRESETS_KEY, JSON.stringify(list))
}
function loadDefaultId(): string | null {
    return localStorage.getItem(DEFAULT_ID_KEY)
}
function saveDefaultId(id: string | null) {
    if (id) localStorage.setItem(DEFAULT_ID_KEY, id)
    else localStorage.removeItem(DEFAULT_ID_KEY)
}

function createPreset(name: string, settings: ViewState) {
    const id = (crypto as any)?.randomUUID?.() ?? `${Date.now().toString(36)}-${Math.random().toString(36).slice(2)}`
    const now = Date.now()
    const p: Preset = { id, name, createdAt: now, updatedAt: now, settings }
    presets.value = [...presets.value, p]
    savePresets(presets.value)
    return p
}
function deletePreset(id: string) {
    presets.value = presets.value.filter(p => p.id !== id)
    savePresets(presets.value)
    if (defaultId.value === id) {
        defaultId.value = null
        saveDefaultId(null)
    }
}
function applyPreset(p: Preset) {
    // Push settings down into the child via exposed method
    attrsRef.value?.applyPreset?.(p.settings)
}
function setDefaultPreset(id: string) {
    defaultId.value = id
    saveDefaultId(id)
}

function handleSaveCurrent() {
    if (!latestViewState.value) return
    const name = (newViewName.value || '').trim()
    if (!name) return
    createPreset(name, latestViewState.value)
    newViewName.value = ''
}

// Init
presets.value = loadPresets()
defaultId.value = loadDefaultId()

// If there is a default preset, apply it once child is mounted
nextTick(() => {
    if (!defaultId.value) return
    const p = presets.value.find(x => x.id === defaultId.value)
    if (p) applyPreset(p)
})
</script>

<template>
    <div class="relative flex-none shrink-0 self-start w-full md:w-[20rem] rounded-xl border border-sidebar-border/70 dark:border-sidebar-border p-4">
        <Tabs default-value="viewControls">
            <TabsList class="grid w-full grid-cols-2">
                <TabsTrigger value="viewControls">View Controls</TabsTrigger>
                <TabsTrigger value="savedViews">Saved Views</TabsTrigger>
            </TabsList>

            <TabsContent value="viewControls">
                <BoardViewAttributes
                    ref="attrsRef"
                    :column-options="props.columnOptions"
                    :row-options="props.rowOptions"
                    :token="props.token"
                    :endpoint="props.endpoint"
                    :option-label="props.optionLabel"
                    :option-value="props.optionValue"
                    :storage-key="props.storageKey"
                    @update:columnType="(v) => emit('update:columnType', v)"
                    @update:rowType="(v) => emit('update:rowType', v)"
                    @update:labelSelection="(v) => emit('update:labelSelection', v)"
                    @update:selectedItems="(v) => emit('update:selectedItems', v)"
                    @update:viewState="(v) => { latestViewState = v; emit('update:viewState', v) }"
                />
            </TabsContent>

            <TabsContent value="savedViews">
                <div class="flex flex-col gap-4 mt-4">
                    <div class="flex items-center gap-2">
                        <Input v-model="newViewName" placeholder="View name" class="flex-1" />
                        <Button variant="default" @click="handleSaveCurrent">Save current</Button>
                    </div>

                    <div v-if="presets.length === 0" class="text-sm text-muted-foreground">
                        No saved views yet.
                    </div>

                    <div v-else class="flex flex-col gap-2">
                        <div
                            v-for="p in presets"
                            :key="p.id"
                            class="flex items-center justify-between rounded border px-3 py-2"
                        >
                            <div class="flex flex-col">
                                <span class="text-sm font-medium">{{ p.name }}</span>
                                <span class="text-xs text-muted-foreground">Updated {{ new Date(p.updatedAt).toLocaleString() }}</span>
                            </div>
                            <div class="flex items-center gap-2">
                                <Button size="sm" variant="secondary" @click="applyPreset(p)">Apply</Button>
                                <Button
                                    size="sm"
                                    :variant="defaultId === p.id ? 'default' : 'outline'"
                                    @click="setDefaultPreset(p.id)"
                                    title="Set as default"
                                >
                                    Default
                                </Button>
                                <Button size="sm" variant="destructive" @click="deletePreset(p.id)">Delete</Button>
                            </div>
                        </div>
                    </div>
                </div>
                <SavedViewsTable />
            </TabsContent>
        </Tabs>
    </div>
</template>
