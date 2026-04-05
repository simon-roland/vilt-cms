import type { App, Component } from 'vue'

interface CmsOptions {
  blocks: Record<string, Component>
  layouts: Record<string, Component>
}

export function createCms(options: CmsOptions) {
  return {
    install(app: App) {
      app.provide('cmsBlocks', options.blocks)
      app.provide('cmsLayouts', options.layouts)
    },
  }
}
