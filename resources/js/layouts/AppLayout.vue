<script setup lang="ts">

// resources/js/layouts/AppLayout.vue

import AppLayout from '@/layouts/app/AppSidebarLayout.vue'
import type { BreadcrumbItemType } from '@/types'

import { Toaster } from '@/components/ui/sonner'
import 'vue-sonner/style.css' // vue-sonner v2 requires this import

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
  <Toaster />

  <!-- Nuxt, vue-sonner v1 because inserting inline CSS with JS to the head tag -->
  <ClientOnly>
    <Toaster rich-colors/>
  </ClientOnly>

  <!-- Nuxt, vue-sonner v2 no need to ClientOnly -->
  <Toaster />
</template>
