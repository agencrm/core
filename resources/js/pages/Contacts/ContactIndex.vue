<script setup lang="ts">
// resources/js/pages/Contacts/ContactIndex.vue

import { ref, onMounted, watch, h } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import DataTable from '@/components/DataTable/DataTable.vue'
import { Plus, Settings2, SquareArrowOutUpRight } from 'lucide-vue-next' 
import {
  Dialog, DialogContent, DialogDescription, DialogHeader, DialogTitle, DialogTrigger
} from '@/components/ui/dialog'
import CreateElementForm from '@/components/Forms/CreateElement.vue'
import ViewController from '@/components/Views/ViewController.vue'
import BoardView from '@/components/Views/Boards/BoardView.vue'
import CellEditableFieldLabel from '@/components/Fields/InPlaceEditable/Label.vue'
import { toast } from 'vue-sonner'

const apiKey = import.meta.env.VITE_APP_API_KEY

// console.log(apiKey);

import ModalDialog from '@/components/Modal/Dialog.vue';
import ModalTray from '@/components/Modal/Tray.vue';
import Modal from '@/components/Modal/Modal.vue';

const createOpen = ref(false)

const breadcrumbs = [{ title: 'Contacts', href: '/contacts' }]
const labelMap = ref({})
const showBoardControls = ref(false)

// NEW: get a handle on the table instance
const tableRef = ref<{ prependRow: (row:any)=>void } | null>(null)

// Form
const form = ref({ first_name: '', last_name: '', email: '' })
const fieldMap = [
  { key: 'first_name', type: 'text',  label: 'First Name', placeholder: 'First Name' },
  { key: 'last_name',  type: 'text',  label: 'Last Name',  placeholder: 'Last Name'  },
  { key: 'email',      type: 'text',  label: 'Email',      placeholder: 'email@example.com' },
]

// View state
const routeKey = `viewMode:/contacts`
const view = ref<'table'|'board'>('table')
onMounted(() => { try { const s = localStorage.getItem(routeKey); if (s === 'board' || s === 'table') view.value = s } catch {} })
watch(view, v => { try { localStorage.setItem(routeKey, v) } catch {} })

const CONTACT_FQCN = 'App\\\\Models\\\\Contact'


// Columns
const columns = [
    // ID column
    {
    accessorKey: 'id',
    header: 'ID',
    cell: ({ row }) => {
        const id = row.original.id
        const href = typeof route === 'function' ? route('contacts.show', id) : `/contacts/${id}`

        return h(
        Modal,
        {
            id,
            href,
            title: `Contact #${id}`,
            subtitle: 'Open the full page or close.',
            contentClass: 'w-[32rem] max-w-[95vw]',
            storageKey: 'ui.modal.contacts.idCell',
        },
        {
            // Pass only the label; Modal will render the icon inside its own trigger button
            trigger: () => String(id),
        }
        )
    },
    meta: { focusable: true },
    },

    // First name column
    {
        accessorKey: 'first_name',
        header: 'First Name',
        cell: ({ row }) => {
            const id = row.original.id
            const firstName = row.original.first_name ?? '—'
            const href = typeof route === 'function' ? route('contacts.show', id) : `/contacts/${id}`

            return h(
            Modal,
            {
                id,
                href,
                title: `${firstName} (#${id})`,
                subtitle: 'Open the full page or close.',
                contentClass: 'w-[32rem] max-w-[95vw]',
                storageKey: 'ui.modal.contacts.firstNameCell',
                //blocks: ['fields', 'notes', 'comments'],
                blocks: [
                {
                    key: 'fields',
                    // // Optional: if your show route name differs
                    // props: {
                    //   endpointRoute: 'api.contacts.show', // or endpointUrl: '/api/contacts/{id}'
                    //   token: apiKey,
                    //   // Control which fields render and their labels/format
                    //   fieldMap: [
                    //     { key: 'first_name', label: 'First Name' },
                    //     { key: 'last_name',  label: 'Last Name'  },
                    //     { key: 'email',      label: 'Email'      },
                    //     {
                    //       key: 'created_at',
                    //       label: 'Created',
                    //       formatter: (v) => (v ? new Date(v).toLocaleString() : '—'),
                    //     },
                    //   ],
                    //   // Or omit fieldMap to auto-list all properties except excluded:
                    //   // exclude: ['some_large_json']
                    // },
                },
                'notes',
                {
                    key: 'comments',
                    props: {
                    commentableType: CONTACT_FQCN,
                    commentableId: id,
                    token: apiKey,
                    // parentId: 123, // optional, only if this form is a reply to another comment
                    // meta: { source: 'contacts.index' }, // optional
                    },
                },
                ]



            },
            { trigger: () => firstName }
            )
        },
        meta: { focusable: true },
    },

    // { accessorKey: 'first_name', header: 'First Name', cell: ({ row }) => row.getValue('first_name'),
    //         meta: { 
    //     focusable: true,
    //     },
    // },
    { accessorKey: 'last_name',  header: 'Last Name',  cell: ({ row }) => row.getValue('last_name'),  
            meta: { 
        focusable: true,
        },
        },
    { accessorKey: 'email',      header: 'Email',      cell: ({ row }) => row.getValue('email'),     
        meta: { 
        focusable: true,
        },
    },
    {
        accessorKey: 'label_id',
        header: 'Label',
        meta: { 
        focusable: true,
        isFillable: true,
        },
        cell: ({ row }) =>
        h(CellEditableFieldLabel, {
            model: 'contact',
            modelId: row.original.id,
            token: apiKey,
            value: row.original.label_id,
            labelMap: labelMap.value,
        }),
    },
    {
        accessorKey: 'created_at',
        header: 'Created At',
        cell: ({ row }) => new Date(row.getValue('created_at')).toLocaleString(),
    },
]

// Helper to normalize resource vs raw
function extractRow(json: any) {
  const r = json?.data ?? json
  return {
    created_at: r.created_at ?? new Date().toISOString(),
    updated_at: r.updated_at ?? new Date().toISOString(),
    ...r,
  }
}

function handleSuccess(json: any) {
  const row = extractRow(json)
  if (row?.id) {
    tableRef.value?.prependRow(row)
    toast.success('Contact added')
    // reset local form so the dialog is clean next open
    form.value = { first_name: '', last_name: '', email: '' }
    createOpen.value = false
  } else {
    toast('Saved, but response missing id; listing will refresh on next load')
  }
}

function handleError(err: any) {
  console.error('Create contact failed', err)
}
</script>

<template>
  <Head title="Contacts" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <template #view-controls>
      <div class="flex justify-between items-center px-2 md:px-0">
        <ViewController :view="view" @update:view="val => view = val" />
        <button
          @click="showBoardControls = !showBoardControls"
          class="p-2 rounded hover:bg-accent hover:text-accent-foreground transition"
          title="Toggle Board Controls"
        >
          <Settings2 class="text-muted-foreground"/>
        </button>
      </div>
    </template>

    <template #action-controls>
      <Dialog>
        <DialogTrigger>
          <Plus class="text-emerald-500"/>
        </DialogTrigger>
        <DialogContent>
          <DialogHeader>
            <DialogTitle class="flex items-center justify-between border-b pb-3">
              Create A Contact
            </DialogTitle>
            <DialogDescription>
              <CreateElementForm
                :endpoint="'/api/contacts'"
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

    <div class="flex flex-col gap-4 p-4">
      <template v-if="view === 'table'">
        <DataTable
          ref="tableRef"
          endpoint-route="api.contacts.index"
          :columns="columns"
          search-placeholder="Search contacts..."
          :auth-token="apiKey"
          v-slot:expand="{ row }"
        >
          <div class="p-2">
            <strong>Email:</strong> {{ row.original.email || '—' }}
          </div>
        </DataTable>
      </template>

      <template v-else>
        <BoardView
          route-name="api.contacts.index"
          :auth-token="apiKey"
          class="min-h-[100vh] md:min-h-min"
          :show-board-controls="showBoardControls"
          :label-group-id="1"
        />
      </template>
    </div>
  </AppLayout>
</template>
