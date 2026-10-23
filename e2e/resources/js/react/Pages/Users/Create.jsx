import { useForm } from '@inertiajs/react';
import { useRoute } from '../../route.js';

export default function Create() {
  const route = useRoute();
  const form = useForm('post', route('users.store'), { name: '' });

  return (
    <main>
      <h1>New user</h1>
      <form
        onSubmit={(event) => {
          event.preventDefault();
          form.submit();
        }}
      >
        <input
          value={form.data.name}
          name="name"
          aria-label="Name"
          onChange={(event) => form.setData('name', event.target.value)}
          onBlur={() => form.validate('name')}
        />
        {form.errors.name && <p data-testid="error">{form.errors.name}</p>}
        <button type="submit">Create</button>
      </form>
    </main>
  );
}
