import { usePage } from '@inertiajs/vue3'

import type { Link, PageProps } from '../types'
import { useRoute } from './route'

export function useHref(component: Link): string {
  if (component.link_type === 'url') {
    return component.url!
  }

  const { locale, defaultLocale } = usePage<PageProps>().props
  const prefixed = !!locale && !!defaultLocale && locale !== defaultLocale

  if (component.page && !component.page.frontpage) {
    return prefixed
      ? useRoute('pages.show.localized', { locale, slug: component.page.slug }, false)
      : useRoute('pages.show', { slug: component.page.slug }, false)
  }

  return prefixed
    ? useRoute('pages.frontpage.localized', { locale }, false)
    : useRoute('pages.frontpage')
}
