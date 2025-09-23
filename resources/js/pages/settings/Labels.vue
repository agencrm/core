// resources/js/pages/settings/Labels.vue

<script setup lang="ts">
const apiKey = import.meta.env.VITE_APP_API_KEY

import { ref, onMounted, watch, h } from 'vue'
import { toast } from 'vue-sonner'
import { Head } from '@inertiajs/vue3'
import { Plus } from 'lucide-vue-next'
// import route from 'ziggy-js'  // not used here; remove or switch to { route } if you actually use it
// import { Ziggy } from '@/ziggy'

import {
  Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger,
} from '@/components/ui/dialog'
import { Button } from '@/components/ui/button'
import AppLayout from '@/layouts/AppLayout.vue'
import SettingsLayout from '@/layouts/settings/Layout.vue'
import DataTable from '@/components/DataTable/DataTable.vue'
import CreateElementForm from '@/components/Forms/CreateElement.vue'
import CellEditableCombobox from '@/components/DataTable/CellEditableCombobox.vue'
import InPlaceEditableText from '@/components/Fields/InPlaceEditable/Text.vue'
import InPlaceEditableColor from '@/components/Fields/InPlaceEditable/Color.vue'
import type { BreadcrumbItem } from '@/types'
import { makeCreateHandlers } from '@/lib/datatable'

// ── NEW: take a ref to the table so we can prepend rows optimistically
const tableRef = ref<{ prependRow: (row: any) => void } | null>(null)

const breadcrumbItems: BreadcrumbItem[] = [{ title: 'Labels', href: '/settings/labels' }]

const form = ref({
  name: '',
  description: '',
  label_group_ids: [] as number[],
})

const { handleSuccess, handleError } = makeCreateHandlers({
    tableRef,
    toast,
    successMessage: 'Label added',
    onReset: () => {
        form.value = { name: '', description: '', label_group_ids: [] }
    },
})

const fieldMap = [
  {
    key: 'name',
    type: 'text',
    label: 'Name',
    placeholder: 'e.g. Qualified, Needs Proposal',
  },
  {
    key: 'label_group_ids',
    type: 'combobox',
    label: 'Label Group',
    placeholder: 'Select a label group',
    endpoint: '/api/label-groups',
    optionLabel: 'name',
    optionValue: 'id',
    attributes: { multiple: true },
  },
]

// // ── SAME helper Contacts uses, so the table gets a complete row shape
// function extractRow(json: any) {
//   const r = json?.data ?? json
//   return {
//     created_at: r?.created_at ?? new Date().toISOString(),
//     updated_at: r?.updated_at ?? new Date().toISOString(),
//     ...r,
//   }
// }

// // ── CHANGED: use tableRef.prependRow instead of touching a local array the table doesn’t read
// const handleSuccess = (res: any) => {
//   const row = extractRow(res)
//   if (row?.id) {
//     tableRef.value?.prependRow(row)
//   }
//   // clear the form for the next create
//   form.value.name = ''
//   form.value.description = ''
//   form.value.label_group_ids = []
// }

// const handleError = (err: any) => {
//   console.error('Label creation failed:', err)
// }

// Optional: you can still fetch to let the table load initial data server-side;
// the DataTable will hit endpoint-route anyway, so this fetch isn’t required.
const labels = ref<any[]>([])
onMounted(async () => {
  try {
    const res = await fetch('/api/labels')
    const json = await res.json()
    labels.value = json.data
  } catch (e) {
    // non-fatal; DataTable still loads from endpoint-route
  }
})

// View state (unchanged)
const routeKey = `viewMode:/labels`
const view = ref<'table' | 'board'>('table')
onMounted(() => { try { const s = localStorage.getItem(routeKey); if (s === 'board' || s === 'table') view.value = s } catch {} })
watch(view, v => { try { localStorage.setItem(routeKey, v) } catch {} })

// Columns
const columns = [
  {
    accessorKey: 'name',
    header: 'Name',
    cell: ({ row }: any) =>
      h(InPlaceEditableText, {
        model: 'labels',
        modelId: row.original.id,
        field: 'name',
        token: apiKey,
        value: row.original.name,
        mode: 'inline',
        placeholder: 'Untitled',
        onUpdateModelValue: (val: string) => (row.original.name = val),
      }),
  },
  // Removed the redundant raw "color" column so you don’t have two columns with the same accessorKey
  {
    accessorKey: 'color',
    header: 'Color',
    cell: ({ row }: any) =>
      h(InPlaceEditableColor, {
        model: 'labels',
        modelId: row.original.id,
        field: 'color',
        token: apiKey,
        value: row.original.color,
        mode: 'inline',
        onUpdateModelValue: (val: string) => (row.original.color = val),
      }),
  },
  {
    accessorKey: 'groups',
    header: 'Label Groups',
    cell: ({ row }: any) =>
      h(CellEditableCombobox, {
        model: 'labels',
        modelId: row.original.id,
        field: 'label_group_ids',
        value: Array.isArray(row.original.groups) ? row.original.groups.map((g: any) => g.id) : [],
        endpoint: '/api/label-groups',
        token: apiKey,
        optionLabel: 'name',
        optionValue: 'id',
        'onUpdate:modelValue': (newVal: any) => {
          // Make sure your component emits the actual array of {id,name} or ids; adjust accordingly.
          row.original.groups = newVal
        },
      }),
  },
  { accessorKey: 'description', header: 'Description', cell: ({ row }: any) => row.getValue('description') },
  {
    accessorKey: 'created_at',
    header: 'Created At',
    cell: ({ row }: any) => new Date(row.getValue('created_at')).toLocaleString(),
  },
]
</script>

<template>
  <AppLayout :breadcrumbs="breadcrumbItems">
    <Head title="Appearance settings" />

    <template #action-controls>
      <Dialog>
        <DialogTrigger>
          <Plus />
        </DialogTrigger>
        <DialogContent>
          <DialogHeader>
            <DialogTitle class="flex items-center justify-between border-b pb-3">
              Create A Label
            </DialogTitle>
            <DialogDescription>
              <CreateElementForm
                :endpoint="'/api/labels'"
                :fields="form"
                :field-map="fieldMap"
                :token="apiKey"
                :onSuccess="handleSuccess"
                :onError="handleError"
              />
            </DialogDescription>
          </DialogHeader>
        </DialogContent>
      </Dialog>
    </template>

    <SettingsLayout section-class="w-full" section-wrapper-class="flex-1">
      <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
        <DataTable
          ref="tableRef"
          endpoint-route="api.labels.index"
          :columns="columns"
          search-placeholder="Search labels..."
          :auth-token="apiKey"
          v-slot:expand="{ row }"
        >
        </DataTable>
      </div>
    </SettingsLayout>
  </AppLayout>
</template>
