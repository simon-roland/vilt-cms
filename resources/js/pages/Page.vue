<script setup lang="ts">
import type { Component } from 'vue'
import { inject } from 'vue'

import Wrapper from '../layouts/Wrapper.vue'
import CmsToolbar from '../components/CmsToolbar.vue'
import type { CmsPage, CmsToolbarData } from '../types'

defineOptions({
  layout: [Wrapper],
})

const props = defineProps<{
  page: CmsPage
  cmsToolbar?: CmsToolbarData | null
}>()

const layouts = inject<Record<string, Component>>('cmsLayouts', {})
</script>

<template>
  <CmsToolbar
    v-if="cmsToolbar"
    :toolbar="cmsToolbar"
    :page-title="page.title"
  />
  <component :is="layouts[page.layout.type]" :page="page" />
</template>
