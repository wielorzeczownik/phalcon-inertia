import { Link } from '@inertiajs/react';
import { useRoute } from '../../route.js';

export default function Index({ users, auth }) {
  const route = useRoute();

  return (
    <main>
      <h1>Users</h1>
      <p data-testid="auth">Signed in as {auth.name}</p>
      <ul>
        {users.map((user) => (
          <li key={user.id}>
            <Link href={route('users.show', { id: user.id })}>{user.name}</Link>
          </li>
        ))}
      </ul>
      <Link href={route('users.create')}>New user</Link>
      <Link href={route('dashboard')}>Dashboard</Link>
      <Link href={route('away')}>Leave</Link>
      <Link href={route('jump')}>Jump</Link>
    </main>
  );
}
