const pages = import.meta.glob('./Pages/**/*.jsx', { eager: true });

export function resolve(name) {
  return pages[`./Pages/${name}.jsx`];
}
