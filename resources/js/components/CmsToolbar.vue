<script setup lang="ts">
import { router } from '@inertiajs/vue3'
import type { CmsToolbarData } from '../types'

const props = defineProps<{
  toolbar: CmsToolbarData
  pageTitle: string
}>()

function setPreviewMode(mode: 'draft' | 'published') {
  router.post(
    '/cms/preview-mode',
    { mode },
    { preserveScroll: true, preserveState: false },
  )
}

function formatRelativeTime(isoString: string): string {
  const diff = Math.round((Date.now() - new Date(isoString).getTime()) / 1000)
  const rtf = new Intl.RelativeTimeFormat(document.documentElement.lang || 'en', { numeric: 'auto' })

  if (diff < 60) return rtf.format(-diff, 'second')
  if (diff < 3600) return rtf.format(-Math.round(diff / 60), 'minute')
  if (diff < 86400) return rtf.format(-Math.round(diff / 3600), 'hour')
  return rtf.format(-Math.round(diff / 86400), 'day')
}
</script>

<template>
  <div class="cms-toolbar">
    <div class="cms-toolbar__inner">
      <!-- Left: CMS label + status -->
      <div class="cms-toolbar__left">
        <span class="cms-toolbar__brand">CMS</span>
        <span
          class="cms-toolbar__badge"
          :class="toolbar.status === 0 ? 'cms-toolbar__badge--draft' : 'cms-toolbar__badge--published'"
        >
          {{ toolbar.status === 0 ? 'Draft' : 'Published' }}
        </span>
      </div>

      <!-- Center: page title + last edited -->
      <div class="cms-toolbar__center">
        <span class="cms-toolbar__title">{{ pageTitle }}</span>
        <span class="cms-toolbar__meta">Edited {{ formatRelativeTime(toolbar.updatedAt) }}</span>
      </div>

      <!-- Right: preview toggle + edit link -->
      <div class="cms-toolbar__right">
        <div
          v-if="toolbar.hasDraft && toolbar.hasPublished"
          class="cms-toolbar__toggle"
        >
          <button
            class="cms-toolbar__toggle-btn"
            :class="{ 'cms-toolbar__toggle-btn--active': toolbar.previewMode === 'draft' }"
            @click="setPreviewMode('draft')"
          >
            Draft
          </button>
          <button
            class="cms-toolbar__toggle-btn"
            :class="{ 'cms-toolbar__toggle-btn--active': toolbar.previewMode === 'published' }"
            @click="setPreviewMode('published')"
          >
            Published
          </button>
        </div>

        <a
          :href="toolbar.editUrl"
          class="cms-toolbar__edit-btn"
          target="_blank"
          rel="noopener"
        >
          Edit
        </a>
      </div>
    </div>
  </div>
</template>

<style scoped>
.cms-toolbar {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  z-index: 9999;
  height: 36px;
  background: #1e293b;
  color: #f8fafc;
  font-family: system-ui, -apple-system, sans-serif;
  font-size: 13px;
  box-shadow: 0 1px 3px rgba(0, 0, 0, 0.3);
}

.cms-toolbar__inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 100%;
  padding: 0 16px;
  gap: 16px;
}

.cms-toolbar__left,
.cms-toolbar__right {
  display: flex;
  align-items: center;
  gap: 10px;
  flex-shrink: 0;
}

.cms-toolbar__center {
  display: flex;
  align-items: center;
  gap: 10px;
  min-width: 0;
}

.cms-toolbar__brand {
  font-weight: 700;
  font-size: 12px;
  letter-spacing: 0.08em;
  text-transform: uppercase;
  color: #94a3b8;
}

.cms-toolbar__badge {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 600;
  text-transform: uppercase;
  letter-spacing: 0.05em;
}

.cms-toolbar__badge--draft {
  background: #92400e;
  color: #fef3c7;
}

.cms-toolbar__badge--published {
  background: #14532d;
  color: #dcfce7;
}

.cms-toolbar__title {
  font-weight: 500;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 300px;
}

.cms-toolbar__meta {
  color: #94a3b8;
  font-size: 12px;
  white-space: nowrap;
}

.cms-toolbar__toggle {
  display: flex;
  border: 1px solid #334155;
  border-radius: 6px;
  overflow: hidden;
}

.cms-toolbar__toggle-btn {
  background: transparent;
  border: none;
  color: #94a3b8;
  cursor: pointer;
  padding: 3px 10px;
  font-size: 12px;
  font-weight: 500;
  transition: background 0.15s, color 0.15s;
}

.cms-toolbar__toggle-btn:hover {
  background: #334155;
  color: #f8fafc;
}

.cms-toolbar__toggle-btn--active {
  background: #334155;
  color: #f8fafc;
}

.cms-toolbar__edit-btn {
  display: inline-flex;
  align-items: center;
  padding: 4px 12px;
  background: #3b82f6;
  color: #fff;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
  transition: background 0.15s;
}

.cms-toolbar__edit-btn:hover {
  background: #2563eb;
}
</style>
