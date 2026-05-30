// Catalog is loaded once from the API and cached in these arrays.
// loadProducts() fills them IN PLACE so existing `import { PRODUCTS }`
// references stay valid after the first await.
export const PRODUCTS = [];
export const CATEGORIES = [];

let loadPromise = null;

export function loadProducts() {
  if (loadPromise) return loadPromise;
  loadPromise = (async () => {
    const [pRes, cRes] = await Promise.all([
      fetch('/api/products'),
      fetch('/api/categories'),
    ]);
    if (!pRes.ok || !cRes.ok) throw new Error('Failed to load catalog');
    const products = await pRes.json();
    const categories = await cRes.json();
    PRODUCTS.splice(0, PRODUCTS.length, ...products);
    CATEGORIES.splice(0, CATEGORIES.length, ...categories);
  })();
  return loadPromise;
}

export function getProductById(id) {
  return PRODUCTS.find(p => p.id === id) || null;
}

export function getProductsByCategory(slug) {
  return PRODUCTS.filter(p => p.slug === slug);
}

export function getFeatured(n = 6) {
  return PRODUCTS.filter(p => p.badge).slice(0, n);
}
