<!-- resources/js/components/DataTable/DataTableDemoColumn.vue -->
<script setup lang="ts">
defineProps({
  payment: Object
})

defineEmits(['expand'])
</script>

<template>
  <div class="text-sm text-muted-foreground">
    <button @click="$emit('expand')" class="underline">
      Expand Row
    </button>
  </div>
</template>
