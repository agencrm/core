<script setup lang="ts">
// resources/js/components/Views/SavedViewsTable.vue

import { ref, onMounted, watch, h } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head, Link } from '@inertiajs/vue3'
import DataTable from '@/components/DataTable/DataTable.vue'
import { Plus, Settings2 } from 'lucide-vue-next'
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


const createOpen = ref(false)


const tableRef = ref<{ prependRow: (row:any)=>void } | null>(null)


// View state
const routeKey = `viewMode:/views`
const view = ref<'table'|'board'>('table')
onMounted(() => { try { const s = localStorage.getItem(routeKey); if (s === 'board' || s === 'table') view.value = s } catch {} })
watch(view, v => { try { localStorage.setItem(routeKey, v) } catch {} })

// Columns
const columns = [
    { accessorKey: 'id', header: 'ID', cell: ({ row }) => row.getValue('id'),
        meta: { 
            focusable: false,
        },
    },
    { accessorKey: 'name', header: 'Name', cell: ({ row }) => row.getValue('name'),
        meta: { 
            focusable: false,
        },
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




</script>

<template>
    <DataTable
        ref="tableRef"
        endpoint-route="api.views.index"
        :columns="columns"
        search-placeholder="Search views..."
        :auth-token="apiKey"
        v-slot:expand="{ row }"
    >
    </DataTable>
</template>
