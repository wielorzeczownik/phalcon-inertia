<script setup>
import { Link } from '@inertiajs/vue3';

defineProps({ users: Array, auth: Object });
</script>

<template>
  <main>
    <h1>Users</h1>
    <p data-testid="auth">Signed in as {{ auth.name }}</p>
    <ul>
      <li v-for="user in users" :key="user.id">
        <Link :href="route('users.show', { id: user.id })">{{
          user.name
        }}</Link>
      </li>
    </ul>
    <Link :href="route('users.create')">New user</Link>
    <Link :href="route('dashboard')">Dashboard</Link>
    <Link :href="route('away')">Leave</Link>
    <Link :href="route('jump')">Jump</Link>
  </main>
</template>
