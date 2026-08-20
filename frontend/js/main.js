/* =========================================================
   FRESH SCENTS — Main Site Script (navbar, footer, cart)
========================================================= */

// Ongeza Bootstrap Icons kwenye kila ukurasa wa frontend kiotomatiki
(function loadIconFont() {
  if (document.getElementById('fs-icon-font')) return;
  const link = document.createElement('link');
  link.id = 'fs-icon-font';
  link.rel = 'stylesheet';
  link.href = 'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css';
  document.head.appendChild(link);
})();

// ---------------- PWA: Sakinisha kama App kwenye Simu ----------------
(function setupPWA() {
  // Manifest
  if (!document.querySelector('link[rel="manifest"]')) {
    const manifestLink = document.createElement('link');
    manifestLink.rel = 'manifest';
    manifestLink.href = 'manifest.json';
    document.head.appendChild(manifestLink);
  }
  // Rangi ya juu ya kivinjari (theme color) na icon ya Apple/Android
  if (!document.querySelector('meta[name="theme-color"]')) {
    const theme = document.createElement('meta');
    theme.name = 'theme-color';
    theme.content = '#1a1408';
    document.head.appendChild(theme);
  }
  if (!document.querySelector('link[rel="apple-touch-icon"]')) {
    const appleIcon = document.createElement('link');
    appleIcon.rel = 'apple-touch-icon';
    appleIcon.href = 'images/icons/icon-192.png';
    document.head.appendChild(appleIcon);
  }

  // Sajili Service Worker (inahitajika ili simu iruhusu "Sakinisha App")
  if ('serviceWorker' in navigator) {
    window.addEventListener('load', () => {
      navigator.serviceWorker.register('sw.js').catch(() => {});
    });
  }

  // Onyesha kitufe cha "Sakinisha App" pale simu inapokubali usakinishaji
  let deferredInstallPrompt = null;
  window.addEventListener('beforeinstallprompt', (e) => {
    e.preventDefault();
    deferredInstallPrompt = e;
    const btn = document.getElementById('installAppBtn');
    if (btn) btn.classList.remove('hidden');
  });

  window.installFreshScentsApp = async function () {
    if (!deferredInstallPrompt) return;
    deferredInstallPrompt.prompt();
    await deferredInstallPrompt.userChoice;
    deferredInstallPrompt = null;
    const btn = document.getElementById('installAppBtn');
    if (btn) btn.classList.add('hidden');
  };

  // Ficha kitufe ikiwa tayari imesakinishwa
  window.addEventListener('appinstalled', () => {
    const btn = document.getElementById('installAppBtn');
    if (btn) btn.classList.add('hidden');
  });
})();

// ---------------- CART (localStorage) ----------------
const CART_KEY = 'freshscents_cart';

function getCart() {
  try { return JSON.parse(localStorage.getItem(CART_KEY)) || []; }
  catch (e) { return []; }
}
function saveCart(cart) {
  localStorage.setItem(CART_KEY, JSON.stringify(cart));
  updateCartBadge();
}
function addToCart(item, qty = 1) {
  const cart = getCart();
  const existing = cart.find(i => i.variant_id === item.variant_id);
  if (existing) {
    existing.qty += qty;
  } else {
    cart.push({
      variant_id: item.variant_id, product_id: item.product_id,
      name: item.name, ml: item.ml,
      price: Number(item.price), image_url: item.image_url, qty,
    });
  }
  saveCart(cart);
}
function removeFromCart(variantId) {
  saveCart(getCart().filter(i => i.variant_id !== variantId));
}
function updateCartQty(variantId, qty) {
  const cart = getCart();
  const item = cart.find(i => i.variant_id === variantId);
  if (item) { item.qty = Math.max(1, qty); saveCart(cart); }
}
function cartTotal() {
  return getCart().reduce((sum, i) => sum + i.price * i.qty, 0);
}
function cartCount() {
  return getCart().reduce((sum, i) => sum + i.qty, 0);
}
function updateCartBadge() {
  document.querySelectorAll('.cart-count').forEach(el => el.textContent = cartCount());
}

// ---------------- NAVBAR + FOOTER INJECTION ----------------
function renderNavbar(activePath = '') {
  const el = document.getElementById('navbar-container');
  if (!el) return;
  el.innerHTML = `
  <div class="navbar">
    <div class="container">
      <a class="brand" href="index.html">
        <img src="images/logo.jpeg" alt="Fresh Scents Logo">
        <span>FRESH SCENTS</span>
      </a>
      <button class="nav-toggle" onclick="document.getElementById('mainNav').classList.toggle('open')"><i class="bi bi-list"></i></button>
      <nav id="mainNav">
        <a href="index.html">Mwanzo</a>
        <a href="products.html">Manukato</a>
        <a href="track.html">Fuatilia Oda</a>
        <a href="account.html" id="navAccountLink">Akaunti Yangu</a>
      </nav>
      <div class="nav-actions">
        <button id="installAppBtn" class="btn-sm hidden" onclick="installFreshScentsApp()" style="display:flex;align-items:center;gap:6px;white-space:nowrap;background:transparent;border:2px solid var(--gold-light);color:var(--gold-light);border-radius:8px;cursor:pointer;padding:6px 12px;font-size:13px;font-weight:700">
          <i class="bi bi-download"></i> Sakinisha App
        </button>
        <a href="cart.html" class="cart-badge" title="Kikapu">
          <i class="bi bi-cart3" style="font-size:20px"></i><span class="count cart-count">0</span>
        </a>
      </div>
    </div>
  </div>`;
  updateCartBadge();
  checkLoginState();
}

function renderFooter() {
  const el = document.getElementById('footer-container');
  if (!el) return;
  el.innerHTML = `
  <footer>
    <div class="container foot-grid">
      <div>
        <div class="brand"><img src="images/logo.jpeg" alt="logo"><span>FRESH SCENTS</span></div>
        <p style="max-width:320px;font-size:13.5px;line-height:1.7">Harufu Nzuri, Hadhi Ya Kifalme. Tunakuletea manukato bora yenye ubora wa hali ya juu kwa bei nzuri, tayari kwa kila hisia na kila tukio.</p>
      </div>
      <div>
        <h4>Viungo</h4>
        <a href="index.html">Mwanzo</a>
        <a href="products.html">Manukato Yote</a>
        <a href="track.html">Fuatilia Oda Yako</a>
        <a href="login.html">Ingia / Jisajili</a>
      </div>
      <div>
        <h4>Wasiliana Nasi</h4>
        <a href="#" id="footShopPhone"><i class="bi bi-telephone"></i> Inapakia...</a>
        <a href="#" id="footShopAddress"><i class="bi bi-geo-alt"></i> Inapakia...</a>
        <a href="../backend/admin/login.php">Admin Login</a>
      </div>
    </div>
    <div class="bottom container">&copy; ${new Date().getFullYear()} FRESH SCENTS &mdash; Haki zote zimehifadhiwa.</div>
  </footer>`;

  apiGet('settings.php').then(r => {
    if (r.success) {
      document.getElementById('footShopPhone').innerHTML = '<i class="bi bi-telephone"></i> ' + r.settings.shop_phone;
      document.getElementById('footShopAddress').innerHTML = '<i class="bi bi-geo-alt"></i> ' + r.settings.shop_address;
    }
  }).catch(()=>{});
}

async function checkLoginState() {
  try {
    const r = await apiGet('auth.php', { action: 'me' });
    const link = document.getElementById('navAccountLink');
    if (link && r.logged_in) link.innerHTML = '<i class="bi bi-person-check"></i> ' + r.customer.full_name.split(' ')[0];
  } catch (e) {}
}

document.addEventListener('DOMContentLoaded', () => {
  renderNavbar();
  renderFooter();
});
