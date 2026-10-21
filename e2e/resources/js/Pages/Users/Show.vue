<script setup>
import { Link, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({ user: Object });
const page = usePage();
const form = useForm({ name: props.user.name });
</script>

<template>
  <main>
    <h1>{{ user.name }}</h1>
    <p v-if="page.flash?.success" data-testid="flash">
      {{ page.flash.success }}
    </p>
    <form @submit.prevent="form.put(route('users.update', { id: user.id }))">
      <input v-model="form.name" aria-label="Rename" />
      <button type="submit">Save</button>
    </form>
    <Link :href="route('users.index')">Back</Link>
  </main>
</template>
