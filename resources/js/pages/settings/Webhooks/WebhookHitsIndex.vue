<script setup lang="ts">

// resources/js/pages/settings/Webhooks/WebhookHitsIndex.vue

import { ref, onMounted, watch, h } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import SettingsLayout from '@/layouts/settings/Layout.vue';
import { Head, Link } from '@inertiajs/vue3'
import DataTable from '@/components/DataTable/DataTable.vue'
import { Plus, Settings2 } from 'lucide-vue-next'

const apiKey = import.meta.env.VITE_APP_API_KEY

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from '@/components/ui/dialog'

import CreateElementForm from '@/components/Forms/CreateElement.vue'

// page state
const breadcrumbs = [
    { title: 'Settings', href: '/settings' },
    { title: 'Webhooks', href: '/settings/webhooks' },
    { title: 'Hits', href: '/settings/webhooks/hits' },
]
const showBoardControls = ref(false)

const form = ref({
  name: '',
  description: '',
})

// fields for the create form
const fieldMap = [
  { key: 'name', type: 'text', label: 'Name', placeholder: 'Flow name' },
  { key: 'description', type: 'text', label: 'Description', placeholder: 'Describe the flow' },
]

// view state
const routeKey = `viewMode:/flows`
const view = ref<'table' | 'board'>('table')

onMounted(() => {
  try {
    const stored = localStorage.getItem(routeKey)
    if (stored === 'board' || stored === 'table') view.value = stored
  } catch {}
})
watch(view, val => {
  try { localStorage.setItem(routeKey, val) } catch {}
})

// flows table columns
// const columns = [
//   { accessorKey: 'name', header: 'Name', cell: ({ row }) => row.getValue('name') },
//   { accessorKey: 'slug', header: 'Slug', cell: ({ row }) => row.getValue('slug') },
//   { accessorKey: 'status', header: 'Status', cell: ({ row }) => row.getValue('status') ?? 'draft' },
//   {
//     accessorKey: 'updated_at',
//     header: 'Updated',
//     cell: ({ row }) => new Date(row.getValue('updated_at')).toLocaleString(),
//   },
// ]

function pretty(v: unknown) {
  try { return JSON.stringify(v, null, 2) } catch { return String(v ?? '') }
}

// resources/js/pages/settings/Webhooks/WebhookHitsIndex.vue (script setup)


// resources/js/pages/settings/Webhooks/WebhookHitsIndex.vue
// columns snippet

const columns = [
    { accessorKey: 'id', header: 'ID' },
    { accessorKey: 'provider', header: 'Provider' },
    { accessorKey: 'event', header: 'Event' },
    { accessorKey: 'ip', header: 'IP' },

    // DATE-ONLY column (received)
    {
        accessorKey: 'received_at',
        header: 'Received',
        cell: ({ row }) => {
            const v = row.getValue('received_at') || row.getValue('created_at');
            return v ? new Date(v).toLocaleDateString() : '—';
        },
    },

    // JOB STATUS (lifecycle: queued|processing|done)
    {
        accessorKey: 'job_status',
        header: 'Status',
        cell: ({ row }) => {
            const v = (row.getValue('job_status') || 'queued').toString();
            const cls =
                v === 'done'
                    ? 'bg-green-100 text-green-800 border-green-200'
                    : v === 'processing'
                    ? 'bg-amber-100 text-amber-800 border-amber-200'
                    : 'bg-slate-100 text-slate-700 border-slate-200';
            return h('span', { class: `text-xs px-2 py-0.5 border rounded ${cls}` }, v);
        },
    },

    // JOB RESULT (outcome: success|failed|duplicate|noop|null)
    {
        accessorKey: 'job_result',
        header: 'Result',
        cell: ({ row }) => {
            const v = (row.getValue('job_result') || '').toString() || '—';
            const cls =
                v === 'success'
                    ? 'bg-green-100 text-green-800 border-green-200'
                    : v === 'failed'
                    ? 'bg-red-100 text-red-800 border-red-200'
                    : v === 'duplicate' || v === 'noop'
                    ? 'bg-zinc-100 text-zinc-800 border-zinc-200'
                    : 'bg-slate-50 text-slate-600 border-slate-200';
            return h('span', { class: `text-xs px-2 py-0.5 border rounded ${cls}` }, v);
        },
    },

    // ATTEMPTS
    {
        accessorKey: 'job_attempts',
        header: 'Attempts',
        cell: ({ row }) => {
            const v = row.getValue('job_attempts');
            return typeof v === 'number' ? v : (v ?? 0);
        },
    },

    // FINISHED AT
    {
        accessorKey: 'processed_at',
        header: 'Finished',
        cell: ({ row }) => {
            const v = row.getValue('processed_at');
            return v ? new Date(v).toLocaleString() : '—';
        },
    },

    // DETAILS column: collapse toggle + content
    {
        id: 'details',
        header: 'Details',
        cell: ({ row }) =>
            h(
                'button',
                {
                    type: 'button',
                    class: 'text-xs px-2 py-0.5 border rounded',
                    onClick: () => row.toggleExpanded(),
                },
                row.getIsExpanded() ? 'Hide' : 'Show'
            ),
        meta: {
            collapsible: true,
            renderCollapse: (row: any) =>
                h(
                    'div',
                    { class: 'p-3 grid grid-cols-1 md:grid-cols-2 gap-4' },
                    [
                        // Job summary
                        h('div', { class: 'border rounded p-3' }, [
                            h('strong', { class: 'block mb-2' }, 'Job'),
                            h('dl', { class: 'text-xs grid grid-cols-3 gap-x-2 gap-y-1' }, [
                                h('dt', { class: 'font-medium col-span-1' }, 'Status'),
                                h('dd', { class: 'col-span-2' }, row.original.job_status ?? '—'),

                                h('dt', { class: 'font-medium col-span-1' }, 'Result'),
                                h('dd', { class: 'col-span-2' }, row.original.job_result ?? '—'),

                                h('dt', { class: 'font-medium col-span-1' }, 'Attempts'),
                                h('dd', { class: 'col-span-2' }, (row.original.job_attempts ?? 0).toString()),

                                h('dt', { class: 'font-medium col-span-1' }, 'Handler'),
                                h('dd', { class: 'col-span-2 break-all' }, row.original.handler ?? '—'),

                                h('dt', { class: 'font-medium col-span-1' }, 'Correlation ID'),
                                h('dd', { class: 'col-span-2 break-all' }, row.original.job_id ?? '—'),

                                h('dt', { class: 'font-medium col-span-1' }, 'Driver ID'),
                                h('dd', { class: 'col-span-2 break-all' }, row.original.job_driver_id ?? '—'),

                                h('dt', { class: 'font-medium col-span-1' }, 'Finished'),
                                h('dd', { class: 'col-span-2' }, row.original.processed_at ? new Date(row.original.processed_at).toLocaleString() : '—'),
                            ]),
                        ]),

                        // Response context (JSON or text)
                        h('div', { class: 'border rounded p-3' }, [
                            h('strong', { class: 'block mb-2' }, 'Job Response'),
                            h(
                                'pre',
                                { class: 'text-xs whitespace-pre-wrap' },
                                pretty(row.original.job_response)
                            ),
                        ]),

                        // Headers
                        h('div', { class: 'border rounded p-3' }, [
                            h('strong', { class: 'block mb-2' }, 'Headers'),
                            h('pre', { class: 'text-xs whitespace-pre-wrap' }, pretty(row.original.headers)),
                        ]),

                        // Payload
                        h('div', { class: 'border rounded p-3' }, [
                            h('strong', { class: 'block mb-2' }, 'Payload'),
                            h('pre', { class: 'text-xs whitespace-pre-wrap' }, pretty(row.original.payload)),
                        ]),
                    ]
                ),
        },
    },
];


function handleSuccess() {
  // optionally close dialog and reload table here (depends on your DataTable API)
}

function handleError(err: any) {
  console.error(err)
}
</script>

<template>
  <Head title="Flows" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <template #view-controls>
      <div class="flex justify-between items-center px-2 md:px-0">
        <button
          @click="showBoardControls = !showBoardControls"
          class="p-2 rounded hover:bg-accent hover:text-accent-foreground transition"
          title="Toggle Board Controls"
        >
          <Settings2 class="text-muted-foreground" />
        </button>
      </div>
    </template>

    <template #action-controls>
      <!-- <Dialog>
        <DialogTrigger>
          <Plus />
        </DialogTrigger>
        <DialogContent>
          <DialogHeader>
            <DialogTitle class="flex items-center justify-between border-b pb-3">
              Create A Flow
            </DialogTitle>
            <DialogDescription>
              <CreateElementForm
                :endpoint="'/api/flows'"
                :fields="form"
                :field-map="fieldMap"
                :token="apiKey"
                :onSuccess="handleSuccess"
                :onError="handleError"
              />
            </DialogDescription>
          </DialogHeader>
        </DialogContent>
      </Dialog> -->
    </template>

    <div class="flex flex-col gap-4 p-4">

        <SettingsLayout
        section-class="w-full space-y-12"
        section-wrapper-class="flex-1 w-full"
        >
        <DataTable
          endpoint-route="api.webhooks.hits.index"
          :columns="columns"
          search-placeholder="Search webhook hits..."
          :auth-token="apiKey"
          v-slot:expand="{ row }"
            :show-expand-column="true"
        >
          <div class="p-2">
            <strong>Description:</strong> {{ row.original.description || '—' }}
          </div>
        </DataTable>
        </SettingsLayout>

    </div>
  </AppLayout>
</template>
