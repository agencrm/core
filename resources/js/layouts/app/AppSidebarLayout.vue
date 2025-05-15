<script setup lang="ts">

// resources/js/layouts/app/AppSidebarLayout.vue

import AppContent from '@/components/AppContent.vue'
import AppShell from '@/components/AppShell.vue'
import AppSidebar from '@/components/AppSidebar.vue'
import AppSidebarHeader from '@/components/AppSidebarHeader.vue'
import type { BreadcrumbItemType } from '@/types'

const props = withDefaults(defineProps<{
  breadcrumbs?: BreadcrumbItemType[]
}>(), {
  breadcrumbs: () => [],
})
</script>

<template>
  <AppShell variant="sidebar">
    <AppSidebar />
    <AppContent variant="sidebar">
      <AppSidebarHeader :breadcrumbs="props.breadcrumbs">
        <template #view-controls>
          <slot name="view-controls" />
        </template>
        <template #action-controls>
          <slot name="action-controls" />
        </template>
      </AppSidebarHeader>

      <slot />
    </AppContent>
  </AppShell>
</template>
