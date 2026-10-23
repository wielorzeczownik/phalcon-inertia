<script setup>
import { useForm } from '@inertiajs/vue3';
import { inject } from 'vue';

const route = inject('route');
const form = useForm('post', route('users.store'), { name: '' });
</script>

<template>
  <main>
    <h1>New user</h1>
    <form @submit.prevent="form.submit()">
      <input
        v-model="form.name"
        name="name"
        aria-label="Name"
        @change="form.validate('name')"
      />
      <p v-if="form.errors.name" data-testid="error">{{ form.errors.name }}</p>
      <button type="submit">Create</button>
    </form>
  </main>
</template>
