import { usePage } from '@inertiajs/react';
import { useRoute as bindRoute } from 'phalcon-routes';

export function useRoute() {
  const { props, url } = usePage();

  return bindRoute({
    ...props.routes,
    location: new URL(url, props.routes.url),
  });
}
