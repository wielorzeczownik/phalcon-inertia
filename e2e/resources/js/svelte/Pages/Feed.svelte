<script>
  import { InfiniteScroll } from '@inertiajs/svelte';

  let { items } = $props();
</script>

<main>
  <h1>Feed</h1>
  <InfiniteScroll data="items" as="ul">
    {#each items.data as item (item)}
      <li data-testid="item">{item}</li>
    {/each}
  </InfiniteScroll>
</main>
