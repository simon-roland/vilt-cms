<script setup lang="ts">
import { computed, watchEffect } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Head from '../components/Head.vue'
import CmsToolbar from '../components/CmsToolbar.vue'
import type { CmsToolbarData, CmsPage } from '../types'

// This layout persists across Inertia visits and Inertia replaces the props
// object on every visit — capturing `.props` here would freeze the toolbar on
// the first page's data. Always read through the page object.
const inertiaPage = usePage<{ cmsToolbar?: CmsToolbarData | null; page?: CmsPage }>()
const cmsToolbar = computed(() => inertiaPage.props.cmsToolbar ?? null)
const pageTitle = computed(() => inertiaPage.props.page?.name ?? '')

watchEffect(() => {
  document.documentElement.style.setProperty(
    '--cms-toolbar-height',
    cmsToolbar.value ? '44px' : '0px',
  )
})
</script>

<template>
  <Head />
  <CmsToolbar v-if="cmsToolbar" :toolbar="cmsToolbar" :page-title="pageTitle" />
  <div v-if="cmsToolbar" style="height: var(--cms-toolbar-height, 44px); flex-shrink: 0;" aria-hidden="true" />
  <slot />
</template>
