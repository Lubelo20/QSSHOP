import { getCartCount } from './cart.js';

const base = new URL(import.meta.url).href.replace('js/includes.js', '');

async function inject(file, id) {
  const el = document.getElementById(id);
  if (!el) return;
  try {
    const res = await fetch(base + file);
    el.innerHTML = await res.text();
  } catch (e) {
    console.warn('Could not load component:', file, e);
  }
}

function initAnnouncement() {
  const bar = document.querySelector('.announcement-bar');
  if (!bar) return;
  const closeBtn = bar.querySelector('.announcement-bar__close');
  if (sessionStorage.getItem('ann_dismissed')) bar.style.display = 'none';
  if (closeBtn) closeBtn.addEventListener('click', () => {
    bar.style.display = 'none';
    sessionStorage.setItem('ann_dismissed', '1');
  });

  // Rotate messages
  const msgs = [
    'Free Delivery on Orders Over R1,500 &nbsp;·&nbsp; Use Code GOLF20 &nbsp;·&nbsp; Proudly South African',
    'Use Code <strong>GOLF20</strong> for 20% Off All Orders &nbsp;·&nbsp; Nationwide Delivery',
    '⛳ Proudly South African Since 2010 &nbsp;·&nbsp; 200+ Companies Served'
  ];
  const span = bar.querySelector('.announcement-bar__text');
  if (!span) return;
  let i = 0;
  setInterval(() => {
    i = (i + 1) % msgs.length;
    span.style.opacity = '0';
    setTimeout(() => { span.innerHTML = msgs[i]; span.style.opacity = '1'; }, 300);
  }, 5000);
}

function initNav() {
  const hamburger = document.querySelector('.hamburger');
  const mobileNav = document.querySelector('.mobile-nav');
  const closeBtn = mobileNav?.querySelector('.mobile-nav__close');
  if (hamburger && mobileNav) {
    hamburger.addEventListener('click', () => mobileNav.classList.add('open'));
    closeBtn?.addEventListener('click', () => mobileNav.classList.remove('open'));
  }

  // Mark active nav link
  const currentPage = location.pathname.split('/').pop() || 'index.html';
  document.querySelectorAll('.nav-link[data-page]').forEach(link => {
    if (link.dataset.page === currentPage) link.style.color = 'var(--green)';
  });
}

function updateCartBadge() {
  const count = getCartCount();
  document.querySelectorAll('.cart-badge').forEach(el => el.textContent = count);
  document.querySelectorAll('.nav-cart-text').forEach(el => {
    el.textContent = `Cart (${count})`;
  });
}

document.addEventListener('cartUpdated', e => {
  document.querySelectorAll('.cart-badge').forEach(el => el.textContent = e.detail.count);
  document.querySelectorAll('.nav-cart-text').forEach(el => {
    el.textContent = `Cart (${e.detail.count})`;
  });
});

async function init() {
  await Promise.all([
    inject('components/announcement-bar.html', 'announcement-placeholder'),
    inject('components/nav.html', 'nav-placeholder'),
    inject('components/footer.html', 'footer-placeholder'),
  ]);
  initAnnouncement();
  initNav();
  updateCartBadge();
}

document.addEventListener('DOMContentLoaded', init);
