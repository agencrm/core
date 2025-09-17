<script setup lang="ts">
// resources/js/components/Views/Boards/BoardControls.vue

import { ref, nextTick, computed } from 'vue'
import { Tabs, TabsContent, TabsList, TabsTrigger } from '@/components/ui/tabs'
import { Button } from '@/components/ui/button'
import BoardViewAttributes from '@/components/Views/Boards/BoardViewAttributes.vue'
import SavedViewsTable from '@/components/Views/SavedViewsTable.vue'
import CreateElementForm from '@/components/Forms/CreateElement.vue'

type ViewState = {
  columnType: string | null
  rowType: string | null
  labelSelection: Array<string | number>
  selectedItems: Array<{ id: number; name: string }>
}

const props = defineProps<{
  columnOptions?: { value: string; label: string }[]
  rowOptions?: { value: string; label: string }[]
  token: string
  endpoint?: string
  optionLabel?: string
  optionValue?: string
  storageKey?: string
}>()

const emit = defineEmits([
  'update:columnType',
  'update:rowType',
  'update:labelSelection',
  'update:selectedItems',
  'update:viewState',
  'apply:view',
])

const attrsRef = ref<any>(null)
const latestViewState = ref<ViewState | null>(null)
const savedViewsRef = ref<{ prependRow: (row:any)=>void } | null>(null)

function toAttributes(s: ViewState | null) {
  return {
    group_by_type: s?.columnType ?? null,
    row_type: s?.rowType ?? null,
    label_groups: s?.labelSelection ?? [],
    selected_items: s?.selectedItems ?? [],
  }
}

const createViewEndpoint = '/api/views'
const newViewName = ref('')

const createViewFields = computed(() => ({
  name: newViewName.value,
  view_type: 'board',
  attributes: toAttributes(latestViewState.value),
}))

const createViewFieldMap = [
  { key: 'name', type: 'text', label: 'View name', placeholder: 'View name', attributes: { required: true } },
  { key: 'view_type', label: 'view_type', attributes: { type: 'hidden' } },
  { key: 'attributes', label: 'attributes', attributes: { type: 'hidden' } },
]

// Strong client-side validation used by CreateElementForm.beforeSubmit
function validateCreate(values: Record<string, any>): string | null {
  const name = String(values?.name ?? '').trim()
  const type = String(values?.view_type ?? '').trim()
  const attrs = values?.attributes

  if (!name) return 'Please enter a view name.'
  if (!type) return 'View type is required.'
  // attributes must be a non-empty object
  if (!attrs || typeof attrs !== 'object' || Array.isArray(attrs) || Object.keys(attrs).length === 0) {
    return 'Attributes are required.'
  }

  // Optional: ensure at least one meaningful attribute (customize as you like)
  const hasMeaningful =
    attrs.group_by_type != null ||
    (Array.isArray(attrs.label_groups) && attrs.label_groups.length > 0) ||
    (Array.isArray(attrs.selected_items) && attrs.selected_items.length > 0) ||
    attrs.row_type != null

  if (!hasMeaningful) return 'Configure at least one view attribute.'

  return null
}

// Disable submit button if invalid to prevent accidental submit
const isCreateInvalid = computed(() => !!validateCreate(createViewFields.value))

function extractRow(json: any) {
  const r = json?.data ?? json
  return {
    id: r.id,
    name: r.name ?? 'Untitled',
    view_type: r.view_type ?? 'board',
    created_at: r.created_at ?? new Date().toISOString(),
    updated_at: r.updated_at ?? new Date().toISOString(),
    ...r,
  }
}

function handleCreateSuccess(json: any) {
  const row = extractRow(json)
  if (row?.id) {
    savedViewsRef.value?.prependRow(row) // optimistic add
    newViewName.value = ''
  }
}

function handleCreateError(_err: any) {}

function handleApplyFromTable(viewRow: any) {
  emit('apply:view', viewRow)
}

nextTick(() => {})
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
          <CreateElementForm
            :endpoint="createViewEndpoint"
            :fields="createViewFields"
            :field-map="createViewFieldMap"
            :token="props.token"
            :beforeSubmit="validateCreate"
            :onSuccess="handleCreateSuccess"
            :onError="handleCreateError"
          >
            <template #footer>
              <div class="flex items-center gap-2 justify-end">
                <Button type="submit" :disabled="isCreateInvalid">Save current</Button>
              </div>
            </template>
          </CreateElementForm>

          <SavedViewsTable ref="savedViewsRef" @apply="handleApplyFromTable" />
        </div>
      </TabsContent>
    </Tabs>
  </div>
</template>
