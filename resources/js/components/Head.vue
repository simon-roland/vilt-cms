<script setup lang="ts">
import { Head, usePage } from '@inertiajs/vue3'
import { computed } from 'vue'

import type { Meta, PageProps } from '../types'

const page = usePage<PageProps>()

const meta = computed<Meta>(() => {
  return page.props.page?.meta ?? { robots: 'index,follow' }
})

const siteName = computed(() => page.props.settings?.site_name ?? '')
const titleFormat = computed(() => page.props.settings?.title_format ?? '')

const title = computed(() => {
  const cmsPage = page.props.page
  const pageTitle = meta.value.title ?? cmsPage?.name ?? ''

  // Frontpage: never use the format — just the page title, fallback to site name
  if (cmsPage?.is_frontpage) {
    return pageTitle || siteName.value
  }

  // Non-frontpage with a format and a page title: apply format tokens
  if (titleFormat.value && pageTitle) {
    return titleFormat.value
      .replace('{title}', pageTitle)
      .replace('{site}', siteName.value)
  }

  // Fallback: page title or site name
  return pageTitle || siteName.value
})

const canonicalUrl = computed(() => page.props.ziggy?.location ?? '')

const ogType = computed(() => page.props.page?.is_frontpage ? 'website' : 'article')

const ogImage = computed(() => {
  const pageMeta = meta.value
  return pageMeta.og_image_media?.[0]?.src
    ?? (page.props.settings?.og_image_media as any)?.[0]?.src
    ?? null
})

const faviconUrl = computed(() => (page.props.settings?.favicon_media as any)?.[0]?.src ?? null)
</script>

<template>
  <Head>
    <title>{{ title }}</title>
    <link rel="canonical" :href="canonicalUrl" />
    <link v-if="faviconUrl" rel="icon" :href="faviconUrl" />
    <meta name="robots" :content="meta.robots ?? 'index,follow'" />
    <meta v-if="meta.description" name="description" :content="meta.description" />

    <!-- Open Graph -->
    <meta property="og:title" :content="title" />
    <meta property="og:type" :content="ogType" />
    <meta property="og:url" :content="canonicalUrl" />
    <meta v-if="siteName" property="og:site_name" :content="siteName" />
    <meta v-if="meta.description" property="og:description" :content="meta.description" />
    <meta v-if="ogImage" property="og:image" :content="ogImage" />

    <!-- Twitter Card -->
    <meta name="twitter:card" :content="ogImage ? 'summary_large_image' : 'summary'" />
    <meta name="twitter:title" :content="title" />
    <meta v-if="meta.description" name="twitter:description" :content="meta.description" />
    <meta v-if="ogImage" name="twitter:image" :content="ogImage" />
    <meta v-if="$page.props.settings?.twitter_handle" name="twitter:site" :content="($page.props.settings.twitter_handle as string)" />
  </Head>
</template>
