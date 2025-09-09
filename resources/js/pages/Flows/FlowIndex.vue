<script setup lang="ts">
// resources/js/pages/Flows/FlowIndex.vue

import { ref, onMounted, watch } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import DataTable from '@/components/DataTable/DataTable.vue'
import { Plus, Settings2 } from 'lucide-vue-next'

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
const breadcrumbs = [{ title: 'Flows', href: '/flows' }]
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
const columns = [
  { accessorKey: 'name', header: 'Name', cell: ({ row }) => row.getValue('name') },
  { accessorKey: 'slug', header: 'Slug', cell: ({ row }) => row.getValue('slug') },
  { accessorKey: 'status', header: 'Status', cell: ({ row }) => row.getValue('status') ?? 'draft' },
  {
    accessorKey: 'updated_at',
    header: 'Updated',
    cell: ({ row }) => new Date(row.getValue('updated_at')).toLocaleString(),
  },
]

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
      <Dialog>
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
                :token="'1|LgRGb6npouVszXCZDJcpGIVe6CVKS2CjhOBt1figbf15decf'"
                :onSuccess="handleSuccess"
                :onError="handleError"
              />
            </DialogDescription>
          </DialogHeader>
        </DialogContent>
      </Dialog>
    </template>

    <div class="flex flex-col gap-4 p-4">
      <template v-if="view === 'table'">
        <DataTable
          endpoint-route="api.flows.index"
          :columns="columns"
          search-placeholder="Search flows..."
          auth-token="1|LgRGb6npouVszXCZDJcpGIVe6CVKS2CjhOBt1figbf15decf"
          v-slot:expand="{ row }"
        >
          <div class="p-2">
            <strong>Description:</strong> {{ row.original.description || '—' }}
          </div>
        </DataTable>
      </template>

      <template v-else>
        <div class="p-6 text-sm text-muted-foreground border rounded">
          Board view for flows not implemented yet.
        </div>
      </template>
    </div>
  </AppLayout>
</template>
