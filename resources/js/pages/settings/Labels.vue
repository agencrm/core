<script setup lang="ts">

// resources/js/pages/settings/Labels.vue

const apiKey = import.meta.env.VITE_APP_API_KEY

import { ref, onMounted, watch, h } from 'vue'
import { Head } from '@inertiajs/vue3';
import { Plus  } from 'lucide-vue-next';

import route from 'ziggy-js'
import { Ziggy } from '@/ziggy' // adjust path if needed

// Components
import {
Dialog,
DialogContent,
DialogDescription,
DialogHeader,
DialogTitle,
DialogTrigger,
} from "@/components/ui/dialog"

import { Button } from '@/components/ui/button'

import AppearanceTabs from '@/components/AppearanceTabs.vue';
import HeadingSmall from '@/components/HeadingSmall.vue';
import DataTable from '@/components/DataTable/DataTable.vue'
import CreateElementForm from '@/components/Forms/CreateElement.vue';
import CellEditableFieldColor from '@/components/DataTable/CellEditableFieldColor.vue';
import CellEditableCombobox from '@/components/DataTable/CellEditableCombobox.vue';
import FieldInlineEditableText from '@/components/Fields/InPlaceEditable/Text.vue';
import Combobox from '@/components/Fields/Standalone/Combobox.vue';
import InPlaceEditableText from '@/components/Fields/InPlaceEditable/Text.vue'
import InPlaceEditableColor from '@/components/Fields/InPlaceEditable/Color.vue'


import { type BreadcrumbItem } from '@/types';
import AppLayout from '@/layouts/AppLayout.vue';
import SettingsLayout from '@/layouts/settings/Layout.vue';

const breadcrumbItems: BreadcrumbItem[] = [
    {
        title: 'Labels',
        href: '/settings/labels',
    },
];


const form = ref({
    name: '',
    description: '',
    label_group_ids: [],
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
        attributes: {
            multiple:true
        }
    }
]

const handleSuccess = (res) => {
    labels.value.unshift(res.data)
    form.value.name = ''
}

const handleError = (err) => {
    console.error('Label creation failed:', err)
}


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
    {
    accessorKey: 'name_2',
    header: 'Name (New)',
    cell: ({ row }) =>
        h(InPlaceEditableText, {
        model: 'labels',
        modelId: row.original.id,
        field: 'name',
        token: apiKey,
        value: row.original.name,
        mode: 'inline',
        placeholder: 'Untitled',
        onUpdateModelValue: (val) => row.original.name = val,
        }),
    },
    { accessorKey: 'color', header: 'Color', cell: ({ row }) => row.getValue('color') },
    {
    accessorKey: 'color',
    header: 'Color',
    cell: ({ row }) =>
        h(InPlaceEditableColor, {
        model: 'labels',
        modelId: row.original.id,
        field: 'color',
            token: apiKey,
        value: row.original.color,
        mode: 'inline', // or 'popover'
        onUpdateModelValue: (val) => row.original.color = val,
        }),
    },
    {
        accessorKey: 'groups',
        header: 'Label Groups',
    cell: ({ row }) => h(CellEditableCombobox, {
        model: 'labels',
        modelId: row.original.id,
        field: 'label_group_ids',
        value: row.original.groups.map(g => g.id), // correct prop name if your component expects `value`
        endpoint: '/api/label-groups',
        token: apiKey,
        optionLabel: 'name',
        optionValue: 'id',
        'onUpdate:modelValue': (newVal) => {
            row.original.groups = newVal
        }
    })
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
    <AppLayout :breadcrumbs="breadcrumbItems">
        <Head title="Appearance settings" />

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
                    :field-map="fieldMap"
                    :auth-token="apiKey"
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
                endpoint-route="api.labels.index"
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
