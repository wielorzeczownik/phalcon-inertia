<script setup>
import { Deferred, Link, router } from '@inertiajs/vue3';

defineProps({
  counter: Number,
  stats: Object,
  audit: String,
  permissions: Array,
  plans: Number,
});
</script>

<template>
  <main>
    <h1>Dashboard</h1>
    <p data-testid="counter">{{ counter }}</p>
    <button type="button" @click="router.reload({ only: ['counter'] })">
      Refresh counter
    </button>
    <Deferred data="stats">
      <template #fallback>
        <p>Loading stats</p>
      </template>
      <p id="stats" data-testid="stats">Visits: {{ stats.visits }}</p>
    </Deferred>
    <Deferred data="permissions">
      <template #fallback>
        <p>Loading permissions</p>
      </template>
      <template #rescue>
        <p data-testid="permissions">Permissions failed</p>
      </template>
      <p data-testid="permissions">{{ permissions }}</p>
    </Deferred>
    <p data-testid="plans">{{ plans }}</p>
    <Link :href="route('dashboard')">Visit again</Link>
    <p data-testid="audit">{{ audit ?? 'no audit' }}</p>
    <button type="button" @click="router.reload({ only: ['audit'] })">
      Load audit
    </button>
    <button type="button" @click="router.post(route('noop'))">
      Do nothing
    </button>
  </main>
</template>
