<script>
  import { Link } from '@inertiajs/svelte';
  import { route } from '../../route.js';

  let { users, auth } = $props();
</script>

<main>
  <h1>Users</h1>
  <p data-testid="auth">Signed in as {auth.name}</p>
  <ul>
    {#each users as user (user.id)}
      <li>
        <Link href={route('users.show', { id: user.id })}>{user.name}</Link>
      </li>
    {/each}
  </ul>
  <Link href={route('users.create')}>New user</Link>
  <Link href={route('dashboard')}>Dashboard</Link>
  <Link href={route('away')}>Leave</Link>
  <Link href={route('jump')}>Jump</Link>
</main>
