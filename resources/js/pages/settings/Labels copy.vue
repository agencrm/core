<script setup lang="ts">
// resources/js/pages/Labels.vue

import { ref, onMounted, watch, h } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import DataTable from '@/components/DataTable/DataTable.vue'
import BoardView from '@/components/Views/Boards/BoardView.vue'
import ViewController from '@/components/Views/ViewController.vue'
import PlaceholderPattern from '@/components/PlaceholderPattern.vue';
import CreateElementForm from '@/components/Forms/CreateElement.vue';
import CellEditableFieldColor from '@/components/DataTable/CellEditableFieldColor.vue';


const form = ref({
  name: '',
  description: '',
  label_group_id: null,
})

const handleSuccess = (res) => {
  labels.value.unshift(res.data)
  form.value.name = ''
}

const handleError = (err) => {
  console.error('Label creation failed:', err)
}

import { Button } from '@/components/ui/button'

import { Plus  } from 'lucide-vue-next';

import {
  Dialog,
  DialogContent,
  DialogDescription,
  DialogHeader,
  DialogTitle,
  DialogTrigger,
} from "@/components/ui/dialog"

const breadcrumbs = [{ title: 'Labels', href: '/labels' }]
const labels = ref([])

// ✅ LocalStorage-backed view state
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
  { accessorKey: 'color', header: 'Color', cell: ({ row }) => row.getValue('color') },
  {
    accessorKey: 'color',
    header: 'Color',
    cell: ({ row }) => h(CellEditableFieldColor, {
      modelValue: row.original.color,
      'onUpdate:modelValue': (newColor) => row.original.color = newColor,
      labelId: row.original.id,
      token: '1|LgRGb6npouVszXCZDJcpGIVe6CVKS2CjhOBt1figbf15decf',
    }),
  },
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


  <Head title="Labels" />

  <AppLayout :breadcrumbs="breadcrumbs">
    <template #action-controls>


<Dialog>
  <DialogTrigger>
    <Plus />
  </DialogTrigger>
  <DialogContent>
    <DialogHeader>
      <DialogTitle class="flex items-center justify-between border-b pb-3">Create A Label</DialogTitle>
      <DialogDescription>
        <CreateElementForm
          :endpoint="'/api/labels'"
          :fields="form"
          :token="'1|LgRGb6npouVszXCZDJcpGIVe6CVKS2CjhOBt1figbf15decf'"
          :onSuccess="handleSuccess"
          :onError="handleError"
        >
      <div>
        <label for="name" class="text-sm font-medium">Name</label>
        <input
          id="name"
          v-model="form.name"
          type="text"
          class="mt-1 block w-full rounded-md border px-3 py-2 text-sm shadow-sm"
          placeholder="e.g. Qualified, Needs Proposal"
          required
        />
      </div>
    </CreateElementForm>
      </DialogDescription>
    </DialogHeader>
  </DialogContent>
</Dialog>


    </template>


    <div class="flex h-full flex-1 flex-col gap-4 rounded-xl p-4">
            <!-- <div class="grid auto-rows-min gap-4 md:grid-cols-3">
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
                <div class="relative aspect-video overflow-hidden rounded-xl border border-sidebar-border/70 dark:border-sidebar-border">
                    <PlaceholderPattern />
                </div>
            </div> -->
            <div class="relative min-h-[100vh] flex-1 md:min-h-min">
              <DataTable
                route-name="api.labels.index"
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
        </div>
  </AppLayout>
</template>
