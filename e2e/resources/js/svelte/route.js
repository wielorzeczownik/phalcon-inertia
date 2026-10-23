import { page } from '@inertiajs/svelte';
import { useRoute } from 'phalcon-routes';

export function route(...args) {
  const routes = page.props.routes;

  return useRoute({ ...routes, location: new URL(page.url, routes.url) })(
    ...args
  );
}
