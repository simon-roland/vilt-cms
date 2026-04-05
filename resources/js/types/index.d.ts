import type { Config } from 'ziggy-js'

export interface Page {
  props: PageProps
}

export interface PageProps {
  [key: string]: unknown
  ziggy: Config
  title: string
  header: NavigationItem[]
  footer: NavigationItem[]
  page: CmsPage
}

export interface MinimalPage {
  id: number
  title: string
  slug: string
  frontpage: boolean
}

export interface CmsPage {
  id: number
  title: string
  slug: string
  is_frontpage: boolean | null
  layout: Layout
  blocks: Block[]
  meta?: Meta
}

export interface Meta {
  title?: string
  description?: string
  robots?: string
}

// Generic block/layout shapes — sites extend these with their own concrete types
export interface Block {
  type: string
  data: { id: string; [key: string]: unknown }
}

export interface Layout {
  type: string
  data: { id: string; [key: string]: unknown }
}

// Navigation
export type NavigationItem = LinkBlock | DropdownBlock

export interface LinkBlock {
  type: 'link'
  data: Link
}

export interface DropdownBlock {
  type: 'dropdown'
  data: Dropdown
}

export interface Link {
  id: string
  type: 'link'
  label: string
  link_type: string
  page?: PageLink
  url?: string
  target?: string
}

export interface Dropdown {
  id: string
  label: string
  items: LinkBlock[]
}

export interface PageLink {
  slug: string
  frontpage: boolean
}

// Media
export interface Media {
  id: string
  placeholder?: string
  sizes: string
  src: string
  srcset: string
}
