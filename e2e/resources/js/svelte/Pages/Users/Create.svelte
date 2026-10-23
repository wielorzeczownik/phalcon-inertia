<script>
  import { useForm } from '@inertiajs/svelte';
  import { route } from '../../route.js';

  const form = useForm('post', route('users.store'), { name: '' });
</script>

<main>
  <h1>New user</h1>
  <form
    onsubmit={(event) => {
      event.preventDefault();
      form.submit();
    }}
  >
    <input
      bind:value={form.name}
      name="name"
      aria-label="Name"
      onchange={() => form.validate('name')}
    />
    {#if form.errors.name}
      <p data-testid="error">{form.errors.name}</p>
    {/if}
    <button type="submit">Create</button>
  </form>
</main>
