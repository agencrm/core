<script setup lang="ts">

// resources/js/pages/settings/Labels.vue

import { ref, onMounted, watch, h } from 'vue'
import { Head } from '@inertiajs/vue3';
import { Plus  } from 'lucide-vue-next';

// Components
import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog"
import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import DataTable from '@/components/DataTable/DataTable.vue'
import CreateElementForm from '@/components/Forms/CreateElement.vue';
import CellEditableFieldColor from '@/components/DataTable/CellEditableFieldColor.vue';
import { Button } from '@/components/ui/button'

import { type BreadcrumbItem } from '@/types';

import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Labels Groups',
        href: '/settings/label-groups',
    },
];

const form = ref({
  name: '',
  description: '',
  // label_group_id: null,
})

const fieldMap = [
  {
    key: 'name',
    type: 'text',
    label: 'Name',
    placeholder: 'e.g. Qualified, Needs Proposal',
  },
]

const handleSuccess = (res) => {
  labels.value.unshift(res.data)
  form.value.name = ''
}

const handleError = (err) => {
  console.error('Label creation failed:', err)
}

const labels = ref([])

// LocalStorage-backed view state
const routeKey = `viewMode:/labels`
const view = ref<'table' | 'board'>('table')

onMounted(() => {
  try {
    const stored = localStorage.getItem(routeKey)
    if (stored === 'board' || stored === 'table') {
      view.value = stored
    }
  } catch (e) {
    console.warn('localStorage read failed', e)
  }
})

watch(view, (val) => {
  try {
    localStorage.setItem(routeKey, val)
  } catch (e) {
    console.warn('localStorage write failed', e)
  }
})

const columns = [
  { accessorKey: 'name', header: 'Name', cell: ({ row }) => row.getValue('name') },
  // { accessorKey: 'color', header: 'Color', cell: ({ row }) => row.getValue('color') },
  // {
  //   accessorKey: 'color',
  //   header: 'Color',
  //   cell: ({ row }) => h(CellEditableFieldColor, {
  //     modelValue: row.original.color,
  //     'onUpdate:modelValue': (newColor) => row.original.color = newColor,
  //     labelId: row.original.id,
  //     token: '1|LgRGb6npouVszXCZDJcpGIVe6CVKS2CjhOBt1figbf15decf',
  //   }),
  // },
  { accessorKey: 'description', header: 'Description', cell: ({ row }) => row.getValue('description') },
  { accessorKey: 'created_at', header: 'Created At', cell: ({ row }) => new Date(row.getValue('created_at')).toLocaleString() },
]

onMounted(async () => {
  const res = await fetch('/api/labels')
  const json = await res.json()
  labels.value = json.data
})


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
                <DialogTitle class="flex items-center justify-between border-b pb-3">Create A Label Group</DialogTitle>
                <DialogDescription>
                  <CreateElementForm
                    :endpoint="'/api/label-groups'"
                    :fields="form"
                    :field-map="fieldMap"
                    :token="'1|LgRGb6npouVszXCZDJcpGIVe6CVKS2CjhOBt1figbf15decf'"
                    :onSuccess="handleSuccess"
                    :onError="handleError"
                  >
                  </CreateElementForm>
                </DialogDescription>
              </DialogHeader>
            </DialogContent>
          </Dialog>
        </template>


        <SettingsLayout
          section-class="w-full"
          section-wrapper-class="flex-1"
        >
         <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
              <DataTable
                route-name="api.label-groups.index"
                :columns="columns"
                search-placeholder="Search labels..."
                auth-token="1|LgRGb6npouVszXCZDJcpGIVe6CVKS2CjhOBt1figbf15decf"
                v-slot:expand="{ row }"
              >
                <div class="p-2">
                  <strong>Path:</strong> {{ row.original.path }}<br />
                  <strong>Size:</strong> {{ (row.original.size / 1024).toFixed(2) }} KB
                </div>
              </DataTable>
                      </div>

        </SettingsLayout>
    </AppLayout>
</template>
