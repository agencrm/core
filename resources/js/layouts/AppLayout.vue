<script setup lang="ts">

// resources/js/layouts/AppLayout.vue

import AppLayout from '@/layouts/app/AppSidebarLayout.vue'
import type { BreadcrumbItemType } from '@/types'

const props = withDefaults(defineProps<{
  breadcrumbs?: BreadcrumbItemType[]
}>(), {
  breadcrumbs: () => [],
})
</script>

<template>
  <AppLayout :breadcrumbs="props.breadcrumbs">
    <template #view-controls>
      <slot name="view-controls" />
    </template>
    <template #action-controls>
      <slot name="action-controls" />
    </template>
    <slot />
  </AppLayout>
</template>
