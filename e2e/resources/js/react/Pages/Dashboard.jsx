import { Deferred, Link, router } from '@inertiajs/react';
import { useRoute } from '../route.js';

export default function Dashboard({
  counter,
  stats,
  audit,
  permissions,
  plans,
}) {
  const route = useRoute();

  return (
    <main>
      <h1>Dashboard</h1>
      <p data-testid="counter">{counter}</p>
      <button
        type="button"
        onClick={() => router.reload({ only: ['counter'] })}
      >
        Refresh counter
      </button>
      <Deferred data="stats" fallback={<p>Loading stats</p>}>
        <p id="stats" data-testid="stats">
          Visits: {stats?.visits}
        </p>
      </Deferred>
      <Deferred
        data="permissions"
        fallback={<p>Loading permissions</p>}
        rescue={<p data-testid="permissions">Permissions failed</p>}
      >
        <p data-testid="permissions">{JSON.stringify(permissions)}</p>
      </Deferred>
      <p data-testid="plans">{plans}</p>
      <Link href={route('dashboard')}>Visit again</Link>
      <p data-testid="audit">{audit ?? 'no audit'}</p>
      <button type="button" onClick={() => router.reload({ only: ['audit'] })}>
        Load audit
      </button>
      <button type="button" onClick={() => router.post(route('noop'))}>
        Do nothing
      </button>
    </main>
  );
}
