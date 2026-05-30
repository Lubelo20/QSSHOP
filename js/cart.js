import { PRODUCTS } from './products.js';
import { formatPrice } from './utils.js';

const KEY = 'qs_golf_cart';

function read() {
  try { return JSON.parse(localStorage.getItem(KEY)) || {}; }
  catch { return {}; }
}

function write(cart) {
  localStorage.setItem(KEY, JSON.stringify(cart));
  document.dispatchEvent(new CustomEvent('cartUpdated', { detail: { count: getCount(cart) } }));
}

export function addToCart(productId, variant = {}) {
  const cart = read();
  const key = variant.size
    ? `${productId}::size::${variant.size}`
    : variant.colour
      ? `${productId}::colour::${variant.colour}`
      : productId;
  if (cart[key]) {
    cart[key].qty += 1;
  } else {
    cart[key] = { productId, variant, qty: 1 };
  }
  write(cart);
}

export function removeFromCart(key) {
  const cart = read();
  delete cart[key];
  write(cart);
}

export function updateQty(key, delta) {
  const cart = read();
  if (!cart[key]) return;
  cart[key].qty = Math.max(0, cart[key].qty + delta);
  if (cart[key].qty === 0) delete cart[key];
  write(cart);
}

export function getCartItems() {
  const cart = read();
  return Object.entries(cart).map(([key, item]) => {
    const product = PRODUCTS.find(p => p.id === item.productId);
    return { key, ...item, product };
  }).filter(i => i.product);
}

export function getCartCount() {
  return getCount(read());
}

function getCount(cart) {
  return Object.values(cart).reduce((s, i) => s + i.qty, 0);
}

export function getCartTotal() {
  return getCartItems().reduce((s, i) => s + i.product.price * i.qty, 0);
}

export function clearCart() {
  localStorage.removeItem(KEY);
  document.dispatchEvent(new CustomEvent('cartUpdated', { detail: { count: 0 } }));
}

export function getCartSummaryHTML() {
  const items = getCartItems();
  if (!items.length) return '<p style="color:var(--muted);font-size:14px">Your cart is empty.</p>';
  return items.map(i => {
    const variantStr = i.variant.size ? ` · ${i.variant.size}` : i.variant.colour ? ` · ${i.variant.colour}` : '';
    return `
      <div style="display:flex;justify-content:space-between;font-size:13px;margin-bottom:10px">
        <span>${i.product.name}${variantStr} × ${i.qty}</span>
        <span style="font-weight:600">${formatPrice(i.product.price * i.qty)}</span>
      </div>`;
  }).join('');
}
