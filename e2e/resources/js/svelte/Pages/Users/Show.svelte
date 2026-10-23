<script>
  import { Link, page, useForm } from '@inertiajs/svelte';
  import { route } from '../../route.js';

  let { user } = $props();
  const form = useForm({ name: user.name });
</script>

<main>
  <h1>{user.name}</h1>
  {#if page.flash?.success}
    <p data-testid="flash">{page.flash.success}</p>
  {/if}
  <form
    onsubmit={(event) => {
      event.preventDefault();
      form.put(route('users.update', { id: user.id }));
    }}
  >
    <input bind:value={form.name} aria-label="Rename" />
    <button type="submit">Save</button>
  </form>
  <Link href={route('users.index')}>Back</Link>
</main>
