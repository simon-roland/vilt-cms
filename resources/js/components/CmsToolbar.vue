<script setup lang="ts">
import { computed } from 'vue'
import type { CmsToolbarData } from '../types'

const props = defineProps<{
  toolbar: CmsToolbarData
  pageTitle: string
}>()

function setPreviewMode(mode: 'draft' | 'published') {
  const csrfToken = (document.querySelector('meta[name="csrf-token"]') as HTMLMetaElement | null)?.content ?? ''
  const data = new FormData()
  data.append('mode', mode)
  fetch('/cms/preview-mode', {
    method: 'POST',
    headers: { 'X-CSRF-TOKEN': csrfToken },
    body: data,
  }).then(() => {
    window.location.reload()
  })
}

function formatRelativeTime(isoString: string): string {
  const diff = Math.round((Date.now() - new Date(isoString).getTime()) / 1000)
  const rtf = new Intl.RelativeTimeFormat(document.documentElement.lang || 'en', { numeric: 'auto' })

  if (diff < 60) return rtf.format(-diff, 'second')
  if (diff < 3600) return rtf.format(-Math.round(diff / 60), 'minute')
  if (diff < 86400) return rtf.format(-Math.round(diff / 3600), 'hour')
  return rtf.format(-Math.round(diff / 86400), 'day')
}

const showToggle = computed(() => props.toolbar.hasDraft && props.toolbar.hasPublished)
</script>

<template>
  <div class="cms-toolbar">
    <div class="cms-toolbar__inner">
      <!-- Left: nav links -->
      <div class="cms-toolbar__left">
        <a :href="toolbar.settingsUrl" class="cms-toolbar__nav-link" :title="toolbar.labels.settings">
          <!-- settings / gear icon -->
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M12.22 2h-.44a2 2 0 0 0-2 2v.18a2 2 0 0 1-1 1.73l-.43.25a2 2 0 0 1-2 0l-.15-.08a2 2 0 0 0-2.73.73l-.22.38a2 2 0 0 0 .73 2.73l.15.1a2 2 0 0 1 1 1.72v.51a2 2 0 0 1-1 1.74l-.15.09a2 2 0 0 0-.73 2.73l.22.38a2 2 0 0 0 2.73.73l.15-.08a2 2 0 0 1 2 0l.43.25a2 2 0 0 1 1 1.73V20a2 2 0 0 0 2 2h.44a2 2 0 0 0 2-2v-.18a2 2 0 0 1 1-1.73l.43-.25a2 2 0 0 1 2 0l.15.08a2 2 0 0 0 2.73-.73l.22-.39a2 2 0 0 0-.73-2.73l-.15-.08a2 2 0 0 1-1-1.74v-.5a2 2 0 0 1 1-1.74l.15-.09a2 2 0 0 0 .73-2.73l-.22-.38a2 2 0 0 0-2.73-.73l-.15.08a2 2 0 0 1-2 0l-.43-.25a2 2 0 0 1-1-1.73V4a2 2 0 0 0-2-2z" />
            <circle cx="12" cy="12" r="3" />
          </svg>
          <span class="cms-toolbar__nav-label">{{ toolbar.labels.settings }}</span>
        </a>
        <a :href="toolbar.pagesUrl" class="cms-toolbar__nav-link" :title="toolbar.labels.pages">
          <!-- layout-list icon -->
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <rect width="7" height="7" x="3" y="3" rx="1" />
            <rect width="7" height="7" x="3" y="14" rx="1" />
            <path d="M14 4h7" />
            <path d="M14 9h7" />
            <path d="M14 15h7" />
            <path d="M14 20h7" />
          </svg>
          <span class="cms-toolbar__nav-label">{{ toolbar.labels.pages }}</span>
        </a>
        <a :href="toolbar.newPageUrl" class="cms-toolbar__nav-link" :title="toolbar.labels.newPage">
          <!-- plus icon -->
          <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M5 12h14" />
            <path d="M12 5v14" />
          </svg>
          <span class="cms-toolbar__nav-label">{{ toolbar.labels.newPage }}</span>
        </a>
      </div>

      <!-- Center: page title + last edited -->
      <div class="cms-toolbar__center">
        <span class="cms-toolbar__title">{{ pageTitle }}</span>
        <span class="cms-toolbar__meta">{{ toolbar.labels.edited }} {{ formatRelativeTime(toolbar.updatedAt) }}</span>
      </div>

      <!-- Right: version toggle OR status label + edit button -->
      <div class="cms-toolbar__right">
        <!-- Toggle pill: only when both versions exist -->
        <div v-if="showToggle" class="cms-toolbar__toggle">
          <button
            class="cms-toolbar__toggle-btn"
            :class="{ 'cms-toolbar__toggle-btn--active': toolbar.previewMode === 'draft' }"
            @click="setPreviewMode('draft')"
          >
            {{ toolbar.labels.draft }}
          </button>
          <button
            class="cms-toolbar__toggle-btn"
            :class="{ 'cms-toolbar__toggle-btn--active': toolbar.previewMode === 'published' }"
            @click="setPreviewMode('published')"
          >
            {{ toolbar.labels.published }}
          </button>
        </div>

        <!-- Status label when toggle not shown -->
        <span v-else class="cms-toolbar__status-label" :class="toolbar.hasPublished ? 'cms-toolbar__status-label--published' : 'cms-toolbar__status-label--draft'">
          {{ toolbar.hasPublished ? toolbar.labels.published : toolbar.labels.draft }}
        </span>

        <!-- Edit button -->
        <a :href="toolbar.editUrl" class="cms-toolbar__edit-btn">
          <!-- pencil icon -->
          <svg xmlns="http://www.w3.org/2000/svg" width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
            <path d="M21.174 6.812a1 1 0 0 0-3.986-3.987L3.842 16.174a2 2 0 0 0-.5.83l-1.321 4.352a.5.5 0 0 0 .623.622l4.353-1.32a2 2 0 0 0 .83-.497z" />
            <path d="m15 5 4 4" />
          </svg>
          {{ toolbar.labels.edit }}
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
  height: var(--cms-toolbar-height, 44px);
  background: #0f172a;
  color: #cbd5e1;
  font-family: system-ui, -apple-system, sans-serif;
  font-size: 13px;
  box-shadow: 0 1px 0 rgba(255, 255, 255, 0.06);
}

.cms-toolbar__inner {
  display: flex;
  align-items: center;
  justify-content: space-between;
  height: 100%;
  padding: 0 20px 0 12px;
  gap: 12px;
}

/* Left */

.cms-toolbar__left {
  display: flex;
  align-items: center;
  gap: 2px;
  flex-shrink: 0;
}

.cms-toolbar__nav-link {
  display: inline-flex;
  align-items: center;
  gap: 6px;
  padding: 5px 8px;
  border-radius: 6px;
  color: #94a3b8;
  text-decoration: none;
  transition: background 0.15s, color 0.15s;
  font-size: 12px;
  font-weight: 500;
  white-space: nowrap;
}

.cms-toolbar__nav-link:hover {
  background: #1e293b;
  color: #f1f5f9;
}

.cms-toolbar__nav-label {
  display: none;
}

@media (min-width: 768px) {
  .cms-toolbar__nav-label {
    display: inline;
  }
}

/* Center */

.cms-toolbar__center {
  display: flex;
  align-items: center;
  gap: 8px;
  min-width: 0;
  flex: 1;
  justify-content: center;
}

.cms-toolbar__title {
  font-weight: 500;
  color: #f1f5f9;
  white-space: nowrap;
  overflow: hidden;
  text-overflow: ellipsis;
  max-width: 260px;
}

.cms-toolbar__meta {
  color: #475569;
  font-size: 11px;
  white-space: nowrap;
  flex-shrink: 0;
}

/* Right */

.cms-toolbar__right {
  display: flex;
  align-items: center;
  gap: 8px;
  flex-shrink: 0;
}

.cms-toolbar__status-label {
  display: inline-flex;
  align-items: center;
  padding: 2px 8px;
  border-radius: 9999px;
  font-size: 11px;
  font-weight: 600;
  letter-spacing: 0.04em;
}

.cms-toolbar__status-label--draft {
  background: #78350f;
  color: #fef3c7;
}

.cms-toolbar__status-label--published {
  background: #14532d;
  color: #bbf7d0;
}

.cms-toolbar__toggle {
  display: flex;
  border: 1px solid #1e293b;
  border-radius: 6px;
  overflow: hidden;
}

.cms-toolbar__toggle-btn {
  background: transparent;
  border: none;
  color: #64748b;
  cursor: pointer;
  padding: 3px 10px;
  font-size: 12px;
  font-weight: 500;
  transition: background 0.15s, color 0.15s;
}

.cms-toolbar__toggle-btn:hover {
  background: #1e293b;
  color: #f1f5f9;
}

.cms-toolbar__toggle-btn--active {
  background: #1e293b;
  color: #f1f5f9;
}

.cms-toolbar__edit-btn {
  display: inline-flex;
  align-items: center;
  gap: 5px;
  padding: 4px 11px;
  background: #3b82f6;
  color: #fff;
  border-radius: 6px;
  font-size: 12px;
  font-weight: 600;
  text-decoration: none;
  transition: background 0.15s;
  white-space: nowrap;
}

.cms-toolbar__edit-btn:hover {
  background: #2563eb;
}
</style>
