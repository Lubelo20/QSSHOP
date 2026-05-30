export function formatPrice(n) {
  return 'R' + Number(n).toLocaleString('en-ZA');
}

export function getBadgeHTML(badge) {
  if (!badge) return '';
  const map = {
    'Sale':        'badge--sale',
    'New':         'badge--new',
    'Popular':     'badge--popular',
    'Best Seller': 'badge--popular',
    'Top Pick':    'badge--featured',
    'Corporate':   'badge--corporate',
  };
  const cls = map[badge] || 'badge--new';
  return `<span class="badge ${cls}">${badge}</span>`;
}

export function getShippingRate(province, total) {
  if (total >= 1500) return 0;
  const rates = { 'Gauteng': 85, 'Western Cape': 95, 'KwaZulu-Natal': 85 };
  return rates[province] || 120;
}

export function getDeliveryDate() {
  const d = new Date();
  let count = 0;
  while (count < 5) {
    d.setDate(d.getDate() + 1);
    const day = d.getDay();
    if (day !== 0 && day !== 6) count++;
  }
  return d.toLocaleDateString('en-ZA', { weekday: 'long', day: 'numeric', month: 'long' });
}

export function applyPromo(code, subtotal) {
  if (code.toUpperCase() === 'GOLF20') {
    return { discount: Math.round(subtotal * 0.2), label: '20% off (GOLF20)' };
  }
  return null;
}

export function renderProductCard(product) {
  const badge = getBadgeHTML(product.badge);
  const low = product.stock <= 5 ? `<span style="font-size:11px;color:#e63946;display:block;margin-bottom:8px">Only ${product.stock} left!</span>` : '';
  return `
    <div class="product-card" data-id="${product.id}">
      <div class="product-card__image">
        ${badge ? `<div class="product-card__badge">${badge}</div>` : ''}
        ${product.image
          ? `<img src="${product.image}" alt="${product.name}" loading="lazy">`
          : `<span>${product.emoji}</span>`}
      </div>
      <div class="product-card__body">
        <span class="product-card__cat">${product.category}</span>
        <div class="product-card__name">${product.name}</div>
        ${low}
        <div class="product-card__price">
          <span class="price-sale">${formatPrice(product.price)}</span>
          <span class="price-orig">${formatPrice(product.origPrice)}</span>
        </div>
        <a href="product.html?id=${product.id}" class="btn btn--outline-green btn--sm" style="margin-bottom:10px;display:flex;justify-content:center">View Product</a>
        <button class="btn btn--green btn--full btn--sm add-to-cart-btn" data-id="${product.id}">Add to Cart</button>
      </div>
    </div>`;
}

export function showToast(msg) {
  let t = document.getElementById('site-toast');
  if (!t) {
    t = document.createElement('div');
    t.id = 'site-toast';
    t.className = 'toast';
    document.body.appendChild(t);
  }
  t.textContent = msg;
  t.classList.add('show');
  clearTimeout(t._timer);
  t._timer = setTimeout(() => t.classList.remove('show'), 2400);
}
