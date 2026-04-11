<script setup lang="ts">
import { computed } from 'vue'
import { usePage } from '@inertiajs/vue3'
import Head from '../components/Head.vue'
import CmsToolbar from '../components/CmsToolbar.vue'
import type { CmsToolbarData, CmsPage } from '../types'

const props = usePage<{ cmsToolbar?: CmsToolbarData | null; page?: CmsPage }>().props
const cmsToolbar = computed(() => props.cmsToolbar ?? null)
const pageTitle = computed(() => props.page?.title ?? '')
</script>

<template>
  <Head />
  <CmsToolbar v-if="cmsToolbar" :toolbar="cmsToolbar" :page-title="pageTitle" />
  <div v-if="cmsToolbar" style="height: var(--cms-toolbar-height, 44px); flex-shrink: 0;" aria-hidden="true" />
  <slot />
</template>
