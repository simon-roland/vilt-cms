import { useRoute } from './route'
import type { Link } from '../types'

export function useHref(component: Link): string {
  if (component.link_type === 'url') {
    return component.url!
  }

  if (component.page && !component.page.frontpage) {
    return useRoute('pages.show', { page: component.page.slug }, false)
  }

  return useRoute('pages.frontpage')
}
