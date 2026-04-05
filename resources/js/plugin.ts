import type { App, Component } from 'vue'

type GlobModule = { default: Component } | Component
type GlobInput = Record<string, GlobModule>

interface CmsOptions {
  blocks: GlobInput
  layouts: GlobInput
}

function normalizeBlocks(input: GlobInput): Record<string, Component> {
  const out: Record<string, Component> = {}
  for (const [key, value] of Object.entries(input)) {
    const component = (value as { default?: Component }).default ?? (value as Component)
    let name: string
    if (key.includes('/') || key.includes('.vue')) {
      // Vite glob key e.g. './cms/blocks/HeroBlock.vue'
      name = key.replace(/^.*\//, '').replace(/\.vue$/, '').replace(/Block$/, '')
    } else {
      name = key
    }
    out[name] = component
  }
  return out
}

function normalizeLayouts(input: GlobInput): Record<string, Component> {
  const out: Record<string, Component> = {}
  for (const [key, value] of Object.entries(input)) {
    const component = (value as { default?: Component }).default ?? (value as Component)
    let name: string
    if (key.includes('/') || key.includes('.vue')) {
      // Vite glob key e.g. './cms/layouts/DefaultLayout.vue'
      const pascal = key.replace(/^.*\//, '').replace(/\.vue$/, '').replace(/Layout$/, '')
      name = pascal.replace(/(?<!^)([A-Z])/g, '-$1').toLowerCase()
    } else {
      name = key
    }
    out[name] = component
  }
  return out
}

export function createCms(options: CmsOptions) {
  const blocks = normalizeBlocks(options.blocks)
  const layouts = normalizeLayouts(options.layouts)
  return {
    install(app: App) {
      app.provide('cmsBlocks', blocks)
      app.provide('cmsLayouts', layouts)
    },
  }
}
