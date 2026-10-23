import { Link, useForm, usePage } from '@inertiajs/react';
import { useRoute } from '../../route.js';

export default function Show({ user }) {
  const route = useRoute();
  const page = usePage();
  const form = useForm({ name: user.name });

  return (
    <main>
      <h1>{user.name}</h1>
      {page.flash?.success && <p data-testid="flash">{page.flash.success}</p>}
      <form
        onSubmit={(event) => {
          event.preventDefault();
          form.put(route('users.update', { id: user.id }));
        }}
      >
        <input
          value={form.data.name}
          aria-label="Rename"
          onChange={(event) => form.setData('name', event.target.value)}
        />
        <button type="submit">Save</button>
      </form>
      <Link href={route('users.index')}>Back</Link>
    </main>
  );
}
