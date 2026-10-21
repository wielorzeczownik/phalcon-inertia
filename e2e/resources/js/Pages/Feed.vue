<script setup>
import { InfiniteScroll } from '@inertiajs/vue3';

defineProps({ items: Object });
</script>

<template>
  <main>
    <h1>Feed</h1>
    <InfiniteScroll data="items" as="ul">
      <li v-for="item in items.data" :key="item" data-testid="item">
        {{ item }}
      </li>
    </InfiniteScroll>
  </main>
</template>
