<script setup lang="ts">
import { ref, watch, onMounted, nextTick } from 'vue'
import AppLayout from '@/layouts/AppLayout.vue'
import { Head } from '@inertiajs/vue3'

import { VueFlow, useVueFlow } from '@vue-flow/core'
import { Background } from '@vue-flow/background'

import DropzoneBackground from '@/components/Flow/DropzoneBackground.vue'
import Sidebar from '@/components/Flow/Tray.vue'
import useDnD from '@/components/Flow/usdDnD.js'

// ---- props from Inertia (controller passes the Flow model)
const props = defineProps<{
  flow: {
    id: number
    name: string
    description?: string | null
    graph?: {
      nodes?: any[]
      edges?: any[]
      viewport?: { x: number; y: number; zoom: number }
    } | null
  }
}>()

const breadcrumbs = [
  { title: 'Flows', href: '/flows' },
  { title: props.flow.name || `Flow #${props.flow.id}`, href: `/flows/${props.flow.id}` }
]

// ---- Vue Flow state
const nodes = ref<any[]>([])
const edges = ref<any[]>([])

// useVueFlow APIs
const {
  addEdges,
  addNodes,
  setNodes,
  setEdges,
  onConnect,
  screenToFlowCoordinate,
  setViewport,
  toObject,
  onNodesInitialized,
} = useVueFlow()

onConnect(addEdges)

// ---- DnD composable (drag handlers & overlay state)
const { onDragStart, onDragLeave, onDragOver, onDrop, isDragOver } = useDnD({
  addNodes,
  screenToFlowCoordinate,
  onNodesInitialized
})

// ---- Persistence (debounced PATCH to /api/flows/{id})
let saveTimer: number | undefined
function scheduleSave() {
  window.clearTimeout(saveTimer)
  saveTimer = window.setTimeout(saveGraph, 500)
}

async function saveGraph() {
  const graph = toObject() // { nodes, edges, viewport }
  await fetch(`/api/flows/${props.flow.id}`, {
    method: 'PATCH',
    headers: { 'Content-Type': 'application/json', Accept: 'application/json' },
    body: JSON.stringify({ graph })
  }).catch(() => {})
}

// auto-save on nodes/edges change
watch(nodes, scheduleSave, { deep: true })
watch(edges, scheduleSave, { deep: true })

// Load graph when mounted
onMounted(async () => {
  await nextTick()

  const g = props.flow.graph || {}
  setNodes(Array.isArray(g.nodes) ? g.nodes : [])
  setEdges(Array.isArray(g.edges) ? g.edges : [])

  if (g.viewport && typeof g.viewport === 'object') {
    setViewport(g.viewport)
  }
})
</script>

<template>
  <Head :title="`Flow Builder • ${props.flow.name || props.flow.id}`" />
  <AppLayout :breadcrumbs="breadcrumbs">
    <div class="p-4">
      <div class="text-sm text-muted-foreground mb-3">
        {{ props.flow.description || '—' }}
      </div>

      <div class="dnd-flow" @drop="onDrop">
        <!-- v-model allows Vue Flow to update nodes/edges reactively -->
        <VueFlow
          v-model:nodes="nodes"
          v-model:edges="edges"
          @dragover="onDragOver"
          @dragleave="onDragLeave"
          class="border rounded-md"
          :fit-view-on-init="false"
        >
          <!-- background grid + dropzone overlay -->
          <DropzoneBackground
            :style="{
              backgroundColor: isDragOver ? '#e7f3ff' : 'transparent',
              transition: 'background-color 0.2s ease',
            }"
          >
            <Background :size="2" :gap="20" pattern-color="#BDBDBD" />
            <p v-if="isDragOver" class="text-xs text-muted-foreground absolute top-2 left-2">
              Drop here
            </p>
          </DropzoneBackground>
        </VueFlow>

        <!-- draggable palette -->
        <Sidebar @dragstart="onDragStart" />
      </div>
    </div>
  </AppLayout>
</template>

<style scoped>
.dnd-flow {
  position: relative;
  height: calc(100vh - 220px);
  min-height: 480px;
  display: grid;
  grid-template-columns: 1fr 260px; /* canvas + sidebar */
  gap: 12px;
}
</style>
