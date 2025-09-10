<script setup lang="ts">
// resources/js/pages/Flows/FlowIndex.vue

const apiKey = import.meta.env.APP_API_KEY

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


// ---------------------------------------------------------


import { ref } from 'vue'
import { VueFlow, useVueFlow } from '@vue-flow/core'
import { Background } from '@vue-flow/background'
import { ControlButton, Controls } from '@vue-flow/controls'
import { MiniMap } from '@vue-flow/minimap'
import { initialEdges, initialNodes } from '@/components/Flow/initial-elements.js'
import Icon from '@/components/Flow/Icon.vue'

/**
 * `useVueFlow` provides:
 * 1. a set of methods to interact with the VueFlow instance (like `fitView`, `setViewport`, `addEdges`, etc)
 * 2. a set of event-hooks to listen to VueFlow events (like `onInit`, `onNodeDragStop`, `onConnect`, etc)
 * 3. the internal state of the VueFlow instance (like `nodes`, `edges`, `viewport`, etc)
 */
const { onInit, onNodeDragStop, onConnect, addEdges, setViewport, toObject } = useVueFlow()

const nodes = ref(initialNodes)

const edges = ref(initialEdges)

// our dark mode toggle flag
const dark = ref(false)

/**
 * This is a Vue Flow event-hook which can be listened to from anywhere you call the composable, instead of only on the main component
 * Any event that is available as `@event-name` on the VueFlow component is also available as `onEventName` on the composable and vice versa
 *
 * onInit is called when the VueFlow viewport is initialized
 */
onInit((vueFlowInstance) => {
  // instance is the same as the return of `useVueFlow`
  vueFlowInstance.fitView()
})

/**
 * onNodeDragStop is called when a node is done being dragged
 *
 * Node drag events provide you with:
 * 1. the event object
 * 2. the nodes array (if multiple nodes are dragged)
 * 3. the node that initiated the drag
 * 4. any intersections with other nodes
 */
onNodeDragStop(({ event, nodes, node }) => {
  console.log('Node Drag Stop', { event, nodes, node })
})

/**
 * onConnect is called when a new connection is created.
 *
 * You can add additional properties to your new edge (like a type or label) or block the creation altogether by not calling `addEdges`
 */
onConnect((connection) => {
  addEdges(connection)
})

/**
 * To update a node or multiple nodes, you can
 * 1. Mutate the node objects *if* you're using `v-model`
 * 2. Use the `updateNode` method (from `useVueFlow`) to update the node(s)
 * 3. Create a new array of nodes and pass it to the `nodes` ref
 */
function updatePos() {
  nodes.value = nodes.value.map((node) => {
    return {
      ...node,
      position: {
        x: Math.random() * 400,
        y: Math.random() * 400,
      },
    }
  })
}

/**
 * toObject transforms your current graph data to an easily persist-able object
 */
function logToObject() {
  console.log(toObject())
}

/**
 * Resets the current viewport transformation (zoom & pan)
 */
function resetTransform() {
  setViewport({ x: 0, y: 0, zoom: 1 })
}

function toggleDarkMode() {
  dark.value = !dark.value
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
                :token="apiKey"
                :onSuccess="handleSuccess"
                :onError="handleError"
              />
            </DialogDescription>
          </DialogHeader>
        </DialogContent>
      </Dialog>
    </template>

    <div 
        class="flex flex-col gap-4 p-4 h-svh" 
    >

        <VueFlow
            :nodes="nodes"
            :edges="edges"
            :class="{ dark }"
            class="basic-flow h-100"
            :default-viewport="{ zoom: 1.5 }"
            :min-zoom="0.2"
            :max-zoom="4"
        >
            <Background pattern-color="#aaa" :gap="16" />

            <MiniMap />

            <Controls position="top-left">
            <ControlButton title="Reset Transform" @click="resetTransform">
                <Icon name="reset" />
            </ControlButton>

            <ControlButton title="Shuffle Node Positions" @click="updatePos">
                <Icon name="update" />
            </ControlButton>

            <ControlButton title="Toggle Dark Mode" @click="toggleDarkMode">
                <Icon v-if="dark" name="sun" />
                <Icon v-else name="moon" />
            </ControlButton>

            <ControlButton title="Log `toObject`" @click="logToObject">
                <Icon name="log" />
            </ControlButton>
            </Controls>
        </VueFlow>


    </div>
  </AppLayout>
</template>


<style>
@import '@vue-flow/core/dist/style.css';
@import '@vue-flow/core/dist/theme-default.css';
@import '@vue-flow/controls/dist/style.css';
@import '@vue-flow/minimap/dist/style.css';
@import '@vue-flow/node-resizer/dist/style.css';


</style>