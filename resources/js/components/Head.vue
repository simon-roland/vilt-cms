<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

import type { Meta, PageProps } from '../types'

const page = usePage<PageProps>()

const meta = computed<Meta>(() => {
  return page.props.page?.meta ?? { robots: 'index,follow' }
})

const title = computed(() => {
  if (page.props.page?.is_frontpage) {
    return page.props.title
  }

  const metaTitle = meta.value.title ?? page.props.page?.title

  if (metaTitle) {
    return `${metaTitle} - ${page.props.title}`
  }

  return page.props.title
})
</script>

<template>
  <Head>
    <title>{{ title }}</title>
    <meta name="title" :content="title" />
    <meta name="robots" :content="meta.robots ?? 'index,follow'" />
    <meta v-if="meta.description" name="description" :content="meta.description" />
  </Head>
</template>
