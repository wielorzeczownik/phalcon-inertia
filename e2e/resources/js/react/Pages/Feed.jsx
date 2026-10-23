import { InfiniteScroll } from '@inertiajs/react';

export default function Feed({ items }) {
  return (
    <main>
      <h1>Feed</h1>
      <InfiniteScroll data="items" as="ul">
        {items.data.map((item) => (
          <li key={item} data-testid="item">
            {item}
          </li>
        ))}
      </InfiniteScroll>
    </main>
  );
}
