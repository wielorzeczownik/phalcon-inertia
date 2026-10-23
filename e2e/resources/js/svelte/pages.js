const pages = import.meta.glob('./Pages/**/*.svelte', { eager: true });

export function resolve(name) {
  return pages[`./Pages/${name}.svelte`];
}
