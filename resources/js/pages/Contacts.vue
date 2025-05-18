<script setup lang="ts">
import { ref, onMounted, watch, h } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'
import DataTable from '@/components/DataTable/DataTable.vue'
import ViewController from '@/components/Views/ViewController.vue'
import BoardView from '@/components/Views/Boards/BoardView.vue'
import PlaceholderPattern from '@/components/PlaceholderPattern.vue'
import CellEditableFieldLabel from '@/components/Fields/InPlaceEditable/Label.vue'

const breadcrumbs = [{ title: 'Contacts', href: '/contacts' }]
const contacts = ref([])
const labelMap = ref({})

// view state
const routeKey = `viewMode:/contacts`
const view = ref<'table' | 'board'>('table')
onMounted(() => {
  try {
    const stored = localStorage.getItem(routeKey)
    if (stored === 'board' || stored === 'table') {
      view.value = stored
    }
  } catch (e) {}
})
watch(view, val => {
  try {
    localStorage.setItem(routeKey, val)
  } catch (e) {}
})

// columns
const columns = [
  { accessorKey: 'first_name', header: 'First Name', cell: ({ row }) => row.getValue('first_name') },
  { accessorKey: 'last_name', header: 'Last Name', cell: ({ row }) => row.getValue('last_name') },
  { accessorKey: 'email', header: 'Email', cell: ({ row }) => row.getValue('email') },
  {
    accessorKey: 'label_id',
    header: 'Label',
    cell: ({ row }) =>
      h(CellEditableFieldLabel, {
        model: 'contact',
        modelId: row.original.id,
        token: '1|LgRGb6npouVszXCZDJcpGIVe6CVKS2CjhOBt1figbf15decf',
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
</script>

<template>
    <Head title="Contacts" />
    <AppLayout :breadcrumbs="breadcrumbs">
        <template #view-controls>
        <ViewController :view="view" @update:view="val => view = val" />
        </template>

        <div class="flex flex-col gap-4 p-4">
                <!-- <pre class="text-xs bg-muted p-2 rounded whitespace-pre overflow-auto max-h-40">
            <label-map-debug>{{ JSON.stringify(labelMap, null, 2) }}</label-map-debug>
            </pre> -->

            <template v-if="view === 'table'">


                <DataTable
                    endpoint-route="api.contacts.index"
                    :columns="columns"
                    search-placeholder="Search contacts..."
                    auth-token="1|LgRGb6npouVszXCZDJcpGIVe6CVKS2CjhOBt1figbf15decf"
                    v-slot:expand="{ row }"
                >
                    <div class="p-2">
                        <strong>Path:</strong> {{ row.original.path }}<br />
                        <strong>Size:</strong> {{ (row.original.size / 1024).toFixed(2) }} KB
                    </div>
                </DataTable>

            </template>

            <template v-else>
            <BoardView
                route-name="api.contacts.index"
                auth-token="1|LgRGb6npouVszXCZDJcpGIVe6CVKS2CjhOBt1figbf15decf"
                class="min-h-[100vh] md:min-h-min"
            />
            </template>

        </div>
    </AppLayout>
</template>
