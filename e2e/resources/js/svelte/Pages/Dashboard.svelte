<script>
  import { Deferred, Link, router } from '@inertiajs/svelte';
  import { route } from '../route.js';

  let { counter, stats, audit, permissions, plans } = $props();
</script>

<main>
  <h1>Dashboard</h1>
  <p data-testid="counter">{counter}</p>
  <button type="button" onclick={() => router.reload({ only: ['counter'] })}>
    Refresh counter
  </button>
  <p data-testid="stats-title">{stats.title}</p>
  <Deferred data="stats.visits">
    {#snippet fallback()}
      <p>Loading stats</p>
    {/snippet}
    <p id="stats" data-testid="stats">Visits: {stats.visits}</p>
  </Deferred>
  <Deferred data="permissions">
    {#snippet fallback()}
      <p>Loading permissions</p>
    {/snippet}
    {#snippet rescue()}
      <p data-testid="permissions">Permissions failed</p>
    {/snippet}
    <p data-testid="permissions">{JSON.stringify(permissions)}</p>
  </Deferred>
  <p data-testid="plans">{plans}</p>
  <Link href={route('dashboard')}>Visit again</Link>
  <p data-testid="audit">{audit ?? 'no audit'}</p>
  <button type="button" onclick={() => router.reload({ only: ['audit'] })}>
    Load audit
  </button>
  <button type="button" onclick={() => router.post(route('noop'))}>
    Do nothing
  </button>
</main>
