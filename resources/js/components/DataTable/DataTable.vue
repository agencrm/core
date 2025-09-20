<script setup lang="ts">
// resources/js/components/DataTable/DataTable.vue

import {
  ref,
  shallowRef,
  watch,
  watchEffect,
  defineProps,
  defineExpose,
  onMounted,
  onBeforeUnmount,
  computed,
} from 'vue'

import axios from 'axios'
import { route } from 'ziggy-js'

import type { ColumnDef } from '@tanstack/vue-table'
import {
  getCoreRowModel,
  getExpandedRowModel,
  getFilteredRowModel,
  getPaginationRowModel,
  getSortedRowModel,
  useVueTable,
  FlexRender,
} from '@tanstack/vue-table'

import { Table, TableBody, TableCell, TableHead, TableHeader, TableRow } from '@/components/ui/table'
import DataTablePagination from '@/components/DataTable/Pagination.vue'
import DataTableToolbar from '@/components/DataTable/Toolbar.vue'

// Props
const props = defineProps<{
    endpointRoute: string
    authToken?: string
    columns: ColumnDef<any>[]
    searchPlaceholder?: string
    onFill?: (args: {
      colId: string
      sourceRowIndex: number
      targetRowIndices: number[]
      value: any
      rows: any[]
    }) => Promise<void> | void
    showExpandColumn?: boolean 
    params?: Record<string, any>
}>()

const showExpandColumn = computed(() => props.showExpandColumn !== false)

const collapsibleColumn = computed(() => {
  return (props.columns as any[]).find(c => c?.meta?.collapsible)
})

// Data + state
const data = ref<any[]>([])
const total = ref(0)
const pagination = ref({ pageIndex: 0, pageSize: 20 })
const searchTerm = ref('')
const perPage = ref(pagination.value.pageSize)
const table = shallowRef()

// Focused cell state
const focusedCell = ref<{ rowIndex: number | null; colId: string | null }>({
  rowIndex: null,
  colId: null,
})

// Anchor for shift-range selection (usually last focused cell)
const anchorCell = ref<{ rowIndex: number | null; colId: string | null }>({
  rowIndex: null,
  colId: null,
})

// Selected cells (row-range within a single column). Key: "r{row}::c{colId}"
const selectedCells = ref<Set<string>>(new Set())
function cellKey(rowIndex: number, colId: string) {
  return `r${rowIndex}::c${colId}`
}

// Column meta helpers
function isCellFocusable(cell: any): boolean {
  return !!((cell?.column?.columnDef?.meta as any)?.focusable)
}
function isCellFillable(cell: any): boolean {
  const meta = (cell?.column?.columnDef?.meta as any) || {}
  return !!(meta.isFillable ?? meta.fillable)
}

// Build Ziggy-powered request URL
const buildRequestUrl = (pageIndex: number, pageSize: number): string => {
    const baseUrl = route(props.endpointRoute)
    const params = new URLSearchParams()
    params.set('page', String(pageIndex + 1))
    params.set('limit', String(pageSize))
    params.set('search', searchTerm.value)
    params.set('sort', '')
    params.set('direction', 'asc')
    if (props.params && typeof props.params === 'object') {
        Object.entries(props.params).forEach(([k, v]) => {
        if (v === undefined || v === null) return
        params.set(k, String(v))
        })
    }
    return `${baseUrl}?${params.toString()}`
}

function parseRowFromKey(key: string): number | null {
  // key format: r{row}::c{colId}; e.g., r12::cemail
  const m = /^r(\d+)::c/.exec(key)
  return m ? Number(m[1]) : null
}

function performFill(colId: string, sourceRowIndex: number, targetRowIndices: number[]) {
  if (!Array.isArray(data.value) || data.value.length === 0) return

  // read source value
  const source = data.value[sourceRowIndex]
  if (!source) return
  const value = (source as any)?.[colId]

  // clone-mutate rows to keep Vue happy
  for (const r of targetRowIndices) {
    if (r === sourceRowIndex) continue // no-op, already has value
    const row = data.value[r]
    if (!row) continue
    // shallow clone row so Vue tracks the change
    data.value[r] = { ...row, [colId]: value }
  }

  // make sure tanstack sees the change
  syncTableData()

  // optional: let the parent persist
  if (props.onFill) {
    try {
      const rows = targetRowIndices.map(i => data.value[i]).filter(Boolean)
      props.onFill({ colId, sourceRowIndex, targetRowIndices, value, rows })
    } catch {}
  }
}


// Fetch + table setup
async function getPageData(pageIndex: number, pageSize: number) {
  const url = buildRequestUrl(pageIndex, pageSize)
  const headers = props.authToken ? { Authorization: `Bearer ${props.authToken}` } : {}

  const res = await axios.get(url, { headers })
  data.value = res.data.data
  total.value = res.data.meta.total

  table.value = useVueTable({
    get data() {
      return data.value
    },
    columns: props.columns,
    manualPagination: true,
    getCoreRowModel: getCoreRowModel(),
    getPaginationRowModel: getPaginationRowModel(),
    getFilteredRowModel: getFilteredRowModel(),
    getSortedRowModel: getSortedRowModel(),
    getExpandedRowModel: getExpandedRowModel(),
    get pageCount() {
      return Math.ceil(total.value / pagination.value.pageSize)
    },
    state: {
      get pagination() {
        return pagination.value
      }
    },
    onPaginationChange: (updater) => {
      pagination.value =
        typeof updater === 'function' ? updater(pagination.value) : updater
    },
  })
}

// Watchers
watchEffect(() => {
    getPageData(pagination.value.pageIndex, pagination.value.pageSize)
})

watch(
    () => props.params ? JSON.stringify(props.params) : '',
    () => {
        pagination.value.pageIndex = 0
        getPageData(0, pagination.value.pageSize)
    }
)


watch([searchTerm, perPage], () => {
    pagination.value.pageIndex = 0
    pagination.value.pageSize = perPage.value
    getPageData(0, perPage.value)
})

// Resync TanStack without fetch
function syncTableData() {
  if (!table.value) return
  table.value.setOptions((prev: any) => ({
    ...prev,
    data: data.value,
    pageCount: Math.ceil(total.value / pagination.value.pageSize),
  }))
}

// Exposed optimistic helpers
function prependRow(row: any) {
  data.value = [row, ...data.value]
  total.value = (total.value || 0) + 1
  syncTableData()
}
function appendRow(row: any) {
  data.value = [...data.value, row]
  total.value = (total.value || 0) + 1
  syncTableData()
}
function updateRow(id: any, patch: Record<string, any>) {
  const idx = data.value.findIndex(r => r.id === id)
  if (idx !== -1) {
    data.value[idx] = { ...data.value[idx], ...patch }
    syncTableData()
  }
}
function removeRow(id: any) {
  const before = data.value.length
  data.value = data.value.filter(r => r.id !== id)
  if (data.value.length !== before) total.value = Math.max(0, total.value - 1)
  syncTableData()
}

defineExpose({ prependRow, appendRow, updateRow, removeRow })

// Focus APIs
function focusCell(rowIndex: number, colId: string) {
  focusedCell.value = { rowIndex, colId }
  if (anchorCell.value.rowIndex === null || anchorCell.value.colId === null) {
    anchorCell.value = { rowIndex, colId }
  }
}
function clearFocus() {
  focusedCell.value = { rowIndex: null, colId: null }
}
function isCellFocused(rowIndex: number, colId: string) {
  return focusedCell.value.rowIndex === rowIndex && focusedCell.value.colId === colId
}

// Selection helpers (replace the Set so Vue reacts)
function clearSelection() {
  selectedCells.value = new Set()
}


function isCellSelected(rowIndex: number, colId: string) {
  return selectedCells.value.has(cellKey(rowIndex, colId))
}
function isSelectionStart(rowIndex: number, colId: string) {
  return isCellSelected(rowIndex, colId) && !isCellSelected(rowIndex - 1, colId)
}
function isSelectionEnd(rowIndex: number, colId: string) {
  return isCellSelected(rowIndex, colId) && !isCellSelected(rowIndex + 1, colId)
}


function selectRangeSameColumn(anchor: { rowIndex: number; colId: string }, target: { rowIndex: number; colId: string }) {
  if (anchor.colId !== target.colId) return
  const next = new Set<string>() // replace entirely with the new contiguous range
  const start = Math.min(anchor.rowIndex, target.rowIndex)
  const end = Math.max(anchor.rowIndex, target.rowIndex)
  for (let r = start; r <= end; r++) next.add(cellKey(r, target.colId))
  selectedCells.value = next
}

// Mouse handling (click/shift-click)
function handleCellMouseDown(e: MouseEvent, rowIndex: number, colId: string, focusable: boolean) {
  if (!focusable) {
    clearFocus()
    clearSelection()
    return
  }
  const shift = e.shiftKey === true
  if (!shift) {
    focusCell(rowIndex, colId)
    anchorCell.value = { rowIndex, colId }
    clearSelection()
    return
  }
  // Shift-click range (same column as anchor)
  if (anchorCell.value.rowIndex != null && anchorCell.value.colId === colId) {
    selectRangeSameColumn(
      { rowIndex: anchorCell.value.rowIndex, colId },
      { rowIndex, colId }
    )
    focusCell(rowIndex, colId)
    e.preventDefault()
  } else {
    // Different column or no anchor: start fresh
    focusCell(rowIndex, colId)
    anchorCell.value = { rowIndex, colId }
    clearSelection()
  }
}

// === Drag-to-select from the corner handle ===
const isDragging = ref(false)
const dragStart = ref<{ rowIndex: number; colId: string } | null>(null)

function onHandleMouseDown(e: MouseEvent, rowIndex: number, colId: string) {
  // Start drag only from a focused, fillable cell
  if (focusedCell.value.rowIndex !== rowIndex || focusedCell.value.colId !== colId) return
  isDragging.value = true
  dragStart.value = { rowIndex, colId }
  // Use the same anchor as the focused cell
  anchorCell.value = { rowIndex, colId }
  // Initialize selection with just the start
  selectedCells.value = new Set([cellKey(rowIndex, colId)])
  // Prevent text selection
  e.preventDefault()
}

function hitTestRowCol(clientX: number, clientY: number): { rowIndex: number | null; colId: string | null } {
  const el = document.elementFromPoint(clientX, clientY) as HTMLElement | null
  if (!el) return { rowIndex: null, colId: null }
  const rowEl = el.closest('[data-row-index]') as HTMLElement | null
  const cellEl = el.closest('[data-col-id]') as HTMLElement | null
  const rowIndex = rowEl ? Number(rowEl.getAttribute('data-row-index')) : null
  const colId = cellEl ? cellEl.getAttribute('data-col-id') : null
  return { rowIndex: Number.isFinite(rowIndex) ? (rowIndex as number) : null, colId: colId ?? null }
}

function onDocumentMouseMove(e: MouseEvent) {
  if (!isDragging.value || !dragStart.value) return
  const { rowIndex: startRow, colId } = dragStart.value
  const { rowIndex: overRow, colId: overCol } = hitTestRowCol(e.clientX, e.clientY)
  if (overRow == null || overCol !== colId) return
  // Update selection as a contiguous range between start and current over row
  selectRangeSameColumn({ rowIndex: startRow, colId }, { rowIndex: overRow, colId })
}

function onDocumentMouseUp() {
  if (!isDragging.value) return

  const start = dragStart.value
  isDragging.value = false
  dragStart.value = null

  if (!start) return

  const { colId, rowIndex: sourceRowIndex } = start

  // get all selected rows in THIS column (contiguous by construction)
  const selectedRowsInCol = Array.from(selectedCells.value)
    .filter(k => k.endsWith(`::c${colId}`))
    .map(parseRowFromKey)
    .filter((n): n is number => Number.isFinite(n))

  if (selectedRowsInCol.length <= 1) {
    // nothing to fill
    return
  }

  // Fill the entire range using the source (the cell where drag started)
  performFill(colId, sourceRowIndex, selectedRowsInCol)
}

// Click outside / Escape clears everything (outside of the TABLE GRID only)
const gridEl = ref<HTMLElement | null>(null)

function clearAll() {
  clearFocus()
  clearSelection()
}

function onDocumentMouseDown(e: MouseEvent) {
  const grid = gridEl.value
  const target = e.target as Node | null
  if (!grid || !target) return
  if (!grid.contains(target)) {
    clearAll()
  }
}

function onDocumentKeyDown(e: KeyboardEvent) {
  if (e.key === 'Escape') {
    if (isDragging.value) {
      isDragging.value = false
      dragStart.value = null
    }
    clearAll()
  }
}

onMounted(() => {
  document.addEventListener('mousedown', onDocumentMouseDown, true) // outside clicks
  document.addEventListener('mousemove', onDocumentMouseMove)       // drag tracking
  document.addEventListener('mouseup', onDocumentMouseUp)           // drag end
  document.addEventListener('keydown', onDocumentKeyDown)           // esc
})
onBeforeUnmount(() => {
  document.removeEventListener('mousedown', onDocumentMouseDown, true)
  document.removeEventListener('mousemove', onDocumentMouseMove)
  document.removeEventListener('mouseup', onDocumentMouseUp)
  document.removeEventListener('keydown', onDocumentKeyDown)
})
</script>

<template>
  <div class="p-4 select-none" v-if="table">
    <DataTableToolbar
      :pagination="pagination"
      :totalPages="table.getPageCount()"
      :rowCount="table.getRowModel().rows.length"
      :totalRowCount="total"
      :canPreviousPage="table.getCanPreviousPage()"
      :canNextPage="table.getCanNextPage()"
      :setPageIndex="table.setPageIndex"
      :setPageSize="table.setPageSize"
      :search="searchTerm"
      :table="table"
      @update:search="searchTerm = $event"
      @update:perPage="(val) => { perPage.value = val }"
      @refresh="getPageData(pagination.pageIndex, pagination.pageSize)"
    />

    <!-- Wrap ONLY the grid in a ref so “outside” means outside the grid -->
    <div ref="gridEl">
      <Table>
        <TableHeader>
          <TableRow v-for="headerGroup in table.getHeaderGroups()" :key="headerGroup.id">
            <TableHead v-for="header in headerGroup.headers" :key="header.id">
              <template v-if="!header.isPlaceholder">
                <FlexRender :render="header.column.columnDef.header" :props="header.getContext()" />
              </template>
            </TableHead>
          </TableRow>
        </TableHeader>

     <TableBody>
          <template v-for="(row, rowIndex) in table.getRowModel().rows" :key="row.id">
            <!-- Main data row -->
            <TableRow :data-row-index="rowIndex">
              <TableCell
                v-for="cell in row.getVisibleCells()"
                :key="cell.id"
                class="relative cursor-default p-2 border border-border"
                :data-col-id="cell.column.id"
                :class="[ isCellSelected(rowIndex, cell.column.id) ? 'bg-emerald-500/10' : '' ]"
                @mousedown.stop="handleCellMouseDown($event, rowIndex, cell.column.id, isCellFocusable(cell))"
              >
                <FlexRender :render="cell.column.columnDef.cell" :props="cell.getContext()" />

                <!-- selection/focus/handle overlays you already have -->
                <span
                  v-if="isCellSelected(rowIndex, cell.column.id)"
                  class="pointer-events-none absolute inset-0 z-10 border-emerald-600"
                  :class="[
                    'border-l border-r',
                    isSelectionStart(rowIndex, cell.column.id) ? 'border-t' : '',
                    isSelectionEnd(rowIndex, cell.column.id)   ? 'border-b' : '',
                    isDragging ? 'border-dashed' : 'border-solid'
                  ]"
                />
                <span
                  v-if="(cell?.column?.columnDef?.meta as any)?.focusable
                        && focusedCell.rowIndex === rowIndex
                        && focusedCell.colId === cell.column.id
                        && selectedCells.size <= 1"
                  class="pointer-events-none absolute inset-0 z-20 ring-2 ring-emerald-600 ring-offset-0"
                />
                <span
                  v-if="isCellFocusable(cell)
                        && isCellFillable(cell)
                        && (
                             (isCellSelected(rowIndex, cell.column.id) && isSelectionEnd(rowIndex, cell.column.id))
                             || (selectedCells.size === 0 && isCellFocused(rowIndex, cell.column.id))
                           )"
                  class="fill-handle absolute bottom-0 right-0 w-2 h-2 translate-x-1/2 translate-y-1/2
                         rounded-sm border-2 border-emerald-600 bg-background cursor-crosshair z-30"
                  @mousedown.stop.prevent="onHandleMouseDown($event, rowIndex, cell.column.id)"
                />
              </TableCell>
            </TableRow>

            <!-- Expanded detail ROW (owned by whichever column has meta.collapsible) -->
            <TableRow v-if="row.getIsExpanded() && collapsibleColumn">
              <TableCell
                :colspan="row.getVisibleCells().length"
                class="bg-muted/30 p-0"
              >
                <!-- Call the column's renderCollapse(row) -->
                <component
                  :is="collapsibleColumn.meta.renderCollapse ? { render: () => collapsibleColumn.meta.renderCollapse(row) } : 'div'"
                />
              </TableCell>
            </TableRow>
          </template>
        </TableBody>

      </Table>
    </div>

    <DataTablePagination
      :pagination="pagination"
      :totalPages="table.getPageCount()"
      :rowCount="table.getRowModel().rows.length"
      :totalRowCount="total"
      :canPreviousPage="table.getCanPreviousPage()"
      :canNextPage="table.getCanNextPage()"
      :setPageIndex="table.setPageIndex"
      :setPageSize="table.setPageSize"
    />
  </div>
</template>

<style scoped>
/* keep handle clickable (not purely visual anymore) */
</style>
