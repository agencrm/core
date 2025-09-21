<script setup lang="ts">

// resources/js/components/Elements/Contacts/ModalFooter.vue

const props = defineProps<{
  id: number | string
  href: string
}>()

const emit = defineEmits<{ (e: 'close'): void }>()
</script>

<template>
  <div class="flex items-center gap-2">
    <button
      class="inline-flex items-center rounded-md border px-3 py-1.5 text-sm
             hover:bg-accent hover:text-accent-foreground"
      @click="$emit('close')"
    >
      Save {{ id }}
    </button>

    <a
      :href="href"
      class="inline-flex items-center rounded-md border px-3 py-1.5 text-sm
             hover:bg-accent hover:text-accent-foreground"
    >
      Open full view
    </a>
  </div>
</template>
