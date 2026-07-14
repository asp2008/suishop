/* JojoKicks — Main JS
   Yen prices, white theme, responsive
*/
(function () {
  'use strict';

  const $ = (s, c = document) => c.querySelector(s);
  const $$ = (s, c = document) => Array.from(c.querySelectorAll(s));
  const fmt = n => '¥' + Number(n).toLocaleString('ja-JP');
  const onReady = fn => document.readyState !== 'loading' ? fn() : document.addEventListener('DOMContentLoaded', fn);

  // Cart
  const CART_KEY = 'jk_cart';
  const WISH_KEY = 'jk_wish';
  const Cart = {
    get() { try { return JSON.parse(localStorage.getItem(CART_KEY)) || []; } catch { return []; } },
    set(items) { localStorage.setItem(CART_KEY, JSON.stringify(items)); Cart.updateBadges(); },
    add(item) {
      const items = Cart.get();
      const ex = items.find(i => i.id === item.id && String(i.size) === String(item.size));
      if (ex) ex.qty += item.qty; else items.push({ ...item, size: String(item.size) });
      Cart.set(items);
      toast(`已加入购物车 · ${item.name}`);
    },
    remove(id, size) { Cart.set(Cart.get().filter(i => !(i.id === id && String(i.size) === String(size)))); },
    updateQty(id, size, qty) {
      const items = Cart.get();
      const it = items.find(i => i.id === id && String(i.size) === String(size));
      if (it) it.qty = Math.max(1, qty);
      Cart.set(items);
    },
    clear() { Cart.set([]); },
    count() { return Cart.get().reduce((s, i) => s + i.qty, 0); },
    total() { return Cart.get().reduce((s, i) => s + i.qty * i.price, 0); },
    updateBadges() {
      const n = Cart.count();
      $$('[data-cart-count]').forEach(el => {
        el.textContent = n;
        el.setAttribute('data-count', n);
      });
    },
  };

  // Wish
  const Wish = {
    get() { try { return JSON.parse(localStorage.getItem(WISH_KEY)) || []; } catch { return []; } },
    has(id) { return Wish.get().includes(String(id)); },
    toggle(id) {
      const list = Wish.get();
      const idx = list.indexOf(String(id));
      if (idx >= 0) { list.splice(idx, 1); return false; }
      list.push(String(id));
      return true;
    },
  };

  // Toast
  function toast(msg) {
    let t = $('#jk-toast');
    if (!t) {
      t = document.createElement('div');
      t.id = 'jk-toast';
      t.className = 'toast';
      t.innerHTML = '<span class="ic">✓</span><span class="msg"></span>';
      document.body.appendChild(t);
    }
    $('.msg', t).textContent = msg;
    t.classList.add('show');
    clearTimeout(t._h);
    t._h = setTimeout(() => t.classList.remove('show'), 2000);
  }

  // Product card
  function productCard(p) {
    const tagHtml = p.tag ? `<span class="p-tag p-tag-${p.tag}">${p.tag === 'new' ? 'NEW' : p.tag === 'sale' ? 'SALE' : 'HOT'}</span>` : '';
    const wasHtml = p.was ? `<span class="was">${fmt(p.was)}</span>` : '';
    return `
      <a class="product" href="product.html?id=${p.id}">
        <div class="p-media">
          <button class="p-wish ${Wish.has(p.id) ? 'active' : ''}" data-wish="${p.id}" aria-label="Wishlist">${Wish.has(p.id) ? '♥' : '♡'}</button>
          ${tagHtml}
          <div class="emoji">${p.emoji}</div>
        </div>
        <div class="p-body">
          <h3 class="p-title">
            <span class="brand-name">${p.brand}</span>
            ${p.name}
          </h3>
          <div class="p-price">
            <span class="now">${fmt(p.price)}</span>
            ${wasHtml}
          </div>
        </div>
      </a>
    `;
  }

  function renderProducts(target, list) {
    const el = typeof target === 'string' ? $(target) : target;
    if (!el) return;
    el.innerHTML = list.map(productCard).join('');
  }

  // Bind product list
  function bindListPage() {
    const grid = $('#product-grid');
    if (!grid) return;
    const data = window.JK_DATA.products;
    const state = { brand: new Set(), size: new Set(), maxPrice: 100000, sort: 'featured' };

    function apply() {
      let list = data.filter(p => {
        if (state.brand.size && !state.brand.has(p.brand)) return false;
        if (p.price > state.maxPrice) return false;
        return true;
      });
      switch (state.sort) {
        case 'price-asc': list.sort((a, b) => a.price - b.price); break;
        case 'price-desc': list.sort((a, b) => b.price - a.price); break;
        case 'rating': list.sort((a, b) => b.rating - a.rating); break;
        case 'newest': list.sort((a, b) => (b.tag === 'new' ? 1 : 0) - (a.tag === 'new' ? 1 : 0)); break;
      }
      renderProducts(grid, list);
      $('.list-toolbar .count').textContent = `共 ${list.length} 件商品`;
    }
    $$('#filter-brand input').forEach(cb => cb.addEventListener('change', e => {
      const b = e.target.dataset.brand;
      e.target.checked ? state.brand.add(b) : state.brand.delete(b);
      apply();
    }));
    const priceInput = $('#filter-price');
    if (priceInput) priceInput.addEventListener('input', e => {
      state.maxPrice = Number(e.target.value);
      $('#filter-price-val').textContent = fmt(state.maxPrice);
      apply();
    });
    $('#sort-select')?.addEventListener('change', e => { state.sort = e.target.value; apply(); });
    apply();
  }

  // Product detail
  function bindProductDetail() {
    if (!$('#p-detail')) return;
    const id = Number(new URLSearchParams(location.search).get('id')) || 1;
    const all = window.JK_DATA.products;
    const p = all.find(x => x.id === id) || all[0];

    $('.p-brand-line').textContent = p.brand;
    $('#p-title').textContent = p.name;
    $('#p-price-now').textContent = fmt(p.price);
    $('#p-price-was').textContent = p.was ? fmt(p.was) : '';
    $('#p-rating').textContent = p.rating;
    $('#p-reviews').textContent = p.reviews;
    const off = p.was && p.was > p.price ? Math.round((1 - p.price / p.was) * 100) : 0;
    $('#p-save').textContent = off ? `${off}% OFF` : '';

    // Sizes
    const sizeRow = $('#p-sizes');
    sizeRow.innerHTML = p.sizes.map((s, i) => `<div class="size ${i === 0 ? 'active' : ''}" data-s="${s}">${typeof s === 'number' ? 'US ' + s : s}</div>`).join('');
    sizeRow.addEventListener('click', e => {
      const s = e.target.closest('.size');
      if (!s) return;
      $$('.size', sizeRow).forEach(x => x.classList.remove('active'));
      s.classList.add('active');
    });

    // Qty
    const qtyInput = $('#p-qty');
    $('.qty-dec')?.addEventListener('click', () => qtyInput.value = Math.max(1, Number(qtyInput.value) - 1));
    $('.qty-inc')?.addEventListener('click', () => qtyInput.value = Math.min(10, Number(qtyInput.value) + 1));

    // Tabs
    $$('.tabs-nav button').forEach(b => b.addEventListener('click', () => {
      $$('.tabs-nav button, .tab-content').forEach(x => x.classList.remove('active'));
      b.classList.add('active');
      $('#tab-' + b.dataset.tab)?.classList.add('active');
    }));

    // Add to cart
    $('#add-to-cart')?.addEventListener('click', () => {
      const size = $('.size.active', sizeRow)?.dataset.s;
      if (!size) return toast('请选择尺码');
      Cart.add({ id: p.id, name: p.name, brand: p.brand, price: p.price, size: size, qty: Number(qtyInput.value) || 1, emoji: p.emoji });
    });
    $('#buy-now')?.addEventListener('click', () => { $('#add-to-cart').click(); setTimeout(() => location.href = 'cart.html', 400); });

    // Thumbs
    const main = $('#p-main-emoji');
    $$('.g-thumbs .thumb').forEach(t => t.addEventListener('click', () => {
      $$('.g-thumbs .thumb').forEach(x => x.classList.remove('active'));
      t.classList.add('active');
      main.textContent = t.dataset.emoji || main.textContent;
    }));

    // Related
    const related = all.filter(x => x.id !== p.id && x.brandCat === p.brandCat).slice(0, 4);
    if (related.length === 4) renderProducts('#related-grid', related);
    else renderProducts('#related-grid', all.slice(0, 4));
  }

  // Cart page
  function bindCart() {
    const wrap = $('#cart-body');
    if (!wrap) return;
    function render() {
      const items = Cart.get();
      if (!items.length) {
        wrap.innerHTML = `<div class="empty"><div class="ic">🛒</div><h3>购物车是空的</h3><p>还没添加任何商品</p><a class="btn btn-dark" href="list.html">浏览商品</a></div>`;
        $('#summary').style.display = 'none';
        return;
      }
      $('#summary').style.display = 'block';
      wrap.innerHTML = `
        <div class="cart-table">
          <div class="cart-row head"><div></div><div>商品</div><div>价格</div><div>数量</div><div></div></div>
          ${items.map(it => `
            <div class="cart-row">
              <div class="img">${it.emoji || '👟'}</div>
              <div class="info">
                <h4>${it.name}</h4>
                <div class="meta">${it.brand} · 尺码 ${it.size}</div>
              </div>
              <div class="price">${fmt(it.price)}</div>
              <div class="qty">
                <button data-act="dec" data-id="${it.id}" data-size="${it.size}">−</button>
                <input value="${it.qty}" data-id="${it.id}" data-size="${it.size}" />
                <button data-act="inc" data-id="${it.id}" data-size="${it.size}">+</button>
              </div>
              <button class="rm" data-act="rm" data-id="${it.id}" data-size="${it.size}">×</button>
            </div>
          `).join('')}
        </div>
      `;
      const sub = Cart.total();
      const ship = sub >= 10000 ? 0 : 880;
      const tax = Math.round(sub * 0.1);
      $('#sum-sub').textContent = fmt(sub);
      $('#sum-ship').textContent = ship === 0 ? 'FREE' : fmt(ship);
      $('#sum-tax').textContent = fmt(tax);
      $('#sum-total').textContent = fmt(sub + ship + tax);
    }

    wrap.addEventListener('click', e => {
      const btn = e.target.closest('button');
      if (!btn) return;
      const { act, id, size } = btn.dataset;
      if (act === 'rm') { Cart.remove(Number(id), size); render(); toast('已移除'); }
      if (act === 'inc' || act === 'dec') {
        const it = Cart.get().find(i => i.id === Number(id) && String(i.size) === String(size));
        if (it) {
          Cart.updateQty(Number(id), size, it.qty + (act === 'inc' ? 1 : -1));
          render();
        }
      }
    });
    wrap.addEventListener('change', e => {
      const inp = e.target.closest('input');
      if (!inp) return;
      Cart.updateQty(Number(inp.dataset.id), inp.dataset.size, Number(inp.value));
      render();
    });
    $('#clear-cart')?.addEventListener('click', () => { Cart.clear(); render(); toast('购物车已清空'); });
    render();
  }

  // Wishlist toggle
  function bindWishlist() {
    document.addEventListener('click', e => {
      const b = e.target.closest('[data-wish]');
      if (!b) return;
      e.preventDefault();
      e.stopPropagation();
      const id = b.dataset.wish;
      const added = Wish.toggle(id);
      b.textContent = added ? '♥' : '♡';
      b.classList.toggle('active', added);
      toast(added ? '已加入收藏' : '已取消收藏');
      localStorage.setItem(WISH_KEY, JSON.stringify(Wish.get()));
    });
  }

  // FAQ
  function bindFaq() {
    $$('.faq-item').forEach(item => {
      const ic = $('.ic', item);
      if (ic) ic.textContent = '+';
      $('h4', item)?.addEventListener('click', () => {
        item.classList.toggle('open');
        if (ic) ic.textContent = item.classList.contains('open') ? '−' : '+';
      });
    });
  }

  // Components paths fix
  function fixPaths() {
    const isSub = location.pathname.includes('/pages/');
    if (!isSub) return;
    const p = '../';
    $$('a[href], link[href]').forEach(el => {
      const h = el.getAttribute('href') || '';
      if (h.startsWith('http') || h.startsWith('#') || h.startsWith('../') || h.startsWith('mailto:')) return;
      if (h.startsWith('pages/')) el.setAttribute('href', h);
      else if (/^(index|list|product|cart|checkout)\.html/.test(h)) el.setAttribute('href', p + h);
    });
    $$('script[src], link[href]').forEach(el => {
      const v = el.getAttribute(el.tagName === 'SCRIPT' ? 'src' : 'href') || '';
      if (v.startsWith('http') || v.startsWith('../')) return;
      if (v.startsWith('js/') || v.startsWith('css/')) el.setAttribute(el.tagName === 'SCRIPT' ? 'src' : 'href', p + v);
    });
  }

  window.JK = { Cart, Wish, toast, renderProducts, productCard, fmt, $, $$, fixPaths };
  onReady(() => {
    fixPaths();
    Cart.updateBadges();
    bindListPage();
    bindProductDetail();
    bindCart();
    bindWishlist();
    bindFaq();
  });
})();
