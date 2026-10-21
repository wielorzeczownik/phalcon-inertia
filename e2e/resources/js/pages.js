const pages = import.meta.glob('./Pages/**/*.vue', { eager: true });

export function resolve(name) {
  return pages[`./Pages/${name}.vue`];
}
