import { usePage } from '@inertiajs/vue3'
import { route as ziggyRoute, RouteParams } from 'ziggy-js'

export function useRoute(
  name: string,
  params: RouteParams<string> = {},
  absolute: boolean = false,
  config = usePage().props.ziggy,
) {
  return ziggyRoute(name, params, absolute, config)
}
