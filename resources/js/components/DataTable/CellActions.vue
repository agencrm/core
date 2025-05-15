<script setup lang="ts">
import { MoreVertical } from 'lucide-vue-next'
import {
  DropdownMenu,
  DropdownMenuTrigger,
  DropdownMenuContent,
  DropdownMenuItem,
  DropdownMenuSeparator,
} from '@/components/ui/dropdown-menu'

const props = defineProps<{
  row: Record<string, any>
}>()

const handleView = () => {
  console.log('View', props.row)
}

const handleEdit = () => {
  console.log('Edit', props.row)
}

const handleDelete = () => {
  console.log('Delete', props.row)
}
</script>

<template>
  <DropdownMenu>
    <DropdownMenuTrigger as-child>
      <button
        type="button"
        class="p-2 rounded-md hover:bg-muted focus:outline-none focus:ring-2 focus:ring-ring"
      >
        <MoreVertical class="w-4 h-4" />
      </button>
    </DropdownMenuTrigger>
    <DropdownMenuContent align="end" class="w-40">
      <DropdownMenuItem @click="handleView">View</DropdownMenuItem>
      <DropdownMenuItem @click="handleEdit">Edit</DropdownMenuItem>
      <DropdownMenuSeparator />
      <DropdownMenuItem @click="handleDelete" class="text-destructive">
        Delete
      </DropdownMenuItem>
    </DropdownMenuContent>
  </DropdownMenu>
</template>
