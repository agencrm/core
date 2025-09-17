<script setup lang="ts">
// resources/js/components/Views/SavedViewsTable.vue

import { ref, h } from 'vue'
import DataTable from '@/components/DataTable/DataTable.vue'
import axios from 'axios'
import { toast } from 'vue-sonner'

// Icons + Tooltip UI
import { Play, Trash2 } from 'lucide-vue-next'
import {
  Tooltip,
  TooltipContent,
  TooltipProvider,
  TooltipTrigger,
} from '@/components/ui/tooltip'
import { Button } from '@/components/ui/button'

const apiKey = import.meta.env.VITE_APP_API_KEY

const emit = defineEmits<{
  (e: 'apply', row: any): void
}>()

// Access DataTable's exposed optimistic APIs
const tableRef = ref<{ reload?: () => void; removeRow?: (id:any)=>void; prependRow?: (row:any)=>void } | null>(null)

const deletingIds = ref<Set<number | string>>(new Set())

function forceReload() {
  if (tableRef.value?.reload) tableRef.value.reload()
}

async function deleteView(row: any) {
  const id = row.id
  if (!id) return

  // optimistic: stash the row, remove immediately
  const snapshot = { ...row }
  deletingIds.value.add(id)
  tableRef.value?.removeRow?.(id)

  try {
    await axios.delete(`/api/views/${id}`, {
      headers: { Authorization: `Bearer ${apiKey}` },
    })
    toast.success('View deleted')
  } catch (err: any) {
    // rollback
    tableRef.value?.prependRow?.(snapshot)
    toast.error(err?.response?.data?.message ?? 'Failed to delete view')
  } finally {
    deletingIds.value.delete(id)
  }
}

// Columns
const columns = [
  {
    accessorKey: 'id',
    header: 'ID',
    cell: ({ row }) => row.getValue('id'),
    meta: { focusable: false },
  },
  {
    accessorKey: 'name',
    header: 'Name',
    cell: ({ row }) => row.getValue('name'),
    meta: { focusable: false },
  },
  // Apply
  {
    accessorKey: 'apply',
    header: '',
    cell: ({ row }) =>
      h(
        TooltipProvider,
        {},
        () =>
          h(
            Tooltip,
            {},
            {
              default: () => [
                h(
                  TooltipTrigger,
                  { asChild: true },
                  () =>
                    h(
                      Button,
                      {
                        variant: 'ghost',
                        size: 'icon',
                        class: 'h-8 w-8',
                        'aria-label': 'Apply view',
                        onClick: () => emit('apply', row.original),
                      },
                      () => h(Play, { class: 'h-4 w-4' })
                    )
                ),
                h(TooltipContent, {}, () => 'Apply'),
              ],
            }
          )
      ),
    meta: { focusable: false },
  },
  // delete
  {
    accessorKey: 'delete',
    header: '',
    cell: ({ row }) =>
      h(
        TooltipProvider,
        {},
        () =>
          h(
            Tooltip,
            {},
            {
              default: () => [
                h(
                  TooltipTrigger,
                  { asChild: true },
                  () =>
                    h(
                      Button,
                      {
                        variant: 'ghost',
                        size: 'icon',
                        class: 'h-8 w-8 text-destructive hover:text-destructive',
                        'aria-label': 'Delete view',
                        disabled: deletingIds.value.has(row.original.id),
                        onClick: () => deleteView(row.original),
                      },
                      () => h(Trash2, { class: 'h-4 w-4' })
                    )
                ),
                h(TooltipContent, {}, () => (deletingIds.value.has(row.original.id) ? 'Deleting…' : 'Delete')),
              ],
            }
          )
      ),
    meta: { focusable: false },
  },
]

// Expose a light wrapper so parents can optimistically add a row
defineExpose({
  prependRow(row: any) {
    tableRef.value?.prependRow?.(row)
  },
})

</script>

<template>
  <DataTable
    ref="tableRef"
    endpoint-route="api.views.index"
    :columns="columns"
    search-placeholder="Search views..."
    :auth-token="apiKey"
  />
</template>
