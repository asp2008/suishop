/* JojoKicks — Main JS
   公共逻辑：渲染组件、购物车、筛选、交互
*/
(function () {
  'use strict';

  // ===== 工具 =====
  const $ = (sel, ctx = document) => ctx.querySelector(sel);
  const $$ = (sel, ctx = document) => Array.from(ctx.querySelectorAll(sel));
  const fmt = n => '$' + Number(n).toFixed(0);
  const onReady = fn => document.readyState !== 'loading' ? fn() : document.addEventListener('DOMContentLoaded', fn);

  // ===== localStorage 购物车 =====
  const CART_KEY = 'jk_cart';
  const Cart = {
    get() { try { return JSON.parse(localStorage.getItem(CART_KEY)) || []; } catch { return []; } },
    set(items) { localStorage.setItem(CART_KEY, JSON.stringify(items)); Cart.updateBadges(); },
    add(item) {
      const items = Cart.get();
      const ex = items.find(i => i.id === item.id && i.size === item.size);
      if (ex) ex.qty += item.qty; else items.push(item);
      Cart.set(items);
      toast(`已加入购物车 · ${item.name}`);
    },
    remove(id, size) {
      Cart.set(Cart.get().filter(i => !(i.id === id && i.size === size)));
    },
    updateQty(id, size, qty) {
      const items = Cart.get();
      const it = items.find(i => i.id === id && i.size === size);
      if (it) it.qty = Math.max(1, qty);
      Cart.set(items);
    },
    clear() { Cart.set([]); },
    count() { return Cart.get().reduce((s, i) => s + i.qty, 0); },
    total() { return Cart.get().reduce((s, i) => s + i.qty * i.price, 0); },
    updateBadges() {
      const n = Cart.count();
      $$('[data-cart-count]').forEach(el => { el.textContent = n; el.style.display = n > 0 ? 'grid' : 'none'; });
    },
  };

  // ===== Toast =====
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
    t._h = setTimeout(() => t.classList.remove('show'), 2200);
  }

  // ===== 渲染：商品卡 =====
  function productCard(p) {
    const off = p.was && p.was > p.price ? Math.round((1 - p.price / p.was) * 100) : 0;
    const tagHtml = p.tag ? `<span class="badge-tag ${p.tag}">${p.tag}</span>` : '';
    return `
      <a class="product" href="product.html?id=${p.id}">
        <div class="p-media">
          <div class="p-badges">${tagHtml}${off ? `<span class="badge-tag sale">-${off}%</span>` : ''}</div>
          <button class="p-wish" data-wish="${p.id}" aria-label="Wishlist">♡</button>
          <div class="emoji">${p.emoji}</div>
          <div class="p-quick">+ 加入购物车</div>
        </div>
        <div class="p-body">
          <div class="p-brand">${p.brand}</div>
          <h3 class="p-title">${p.name}</h3>
          <div class="p-price">
            <span class="now">${fmt(p.price)}</span>
            ${p.was && p.was > p.price ? `<span class="was">${fmt(p.was)}</span>` : ''}
          </div>
          <div class="p-rating">
            <span class="stars">★★★★★</span>
            <span>${p.rating} (${p.reviews})</span>
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

  // ===== FAQ 折叠 =====
  function bindFaq() {
    $$('.faq-item').forEach(item => {
      $('.ic', item).textContent = '+';
      $('h4', item).addEventListener('click', () => {
        item.classList.toggle('open');
        $('.ic', item).textContent = item.classList.contains('open') ? '−' : '+';
      });
    });
  }

  // ===== 筛选/排序/分页 (list.html) =====
  function bindListPage() {
    const grid = $('#product-grid');
    if (!grid) return;

    const data = window.JK_DATA.listProducts;
    const state = { brand: new Set(), size: new Set(), color: new Set(), maxPrice: 500, sort: 'featured' };

    function apply() {
      let list = data.filter(p => {
        if (state.brand.size && !state.brand.has(p.brand.toLowerCase().replace(/\s/g, ''))) return false;
        if (state.size.size && !p.sizes.some(s => state.size.has(String(s)))) return false;
        if (p.price > state.maxPrice) return false;
        return true;
      });
      switch (state.sort) {
        case 'price-asc': list.sort((a, b) => a.price - b.price); break;
        case 'price-desc': list.sort((a, b) => b.price - a.price); break;
        case 'rating': list.sort((a, b) => b.rating - a.rating); break;
        case 'newest': list.sort((a, b) => b.id - a.id); break;
      }
      renderProducts(grid, list);
      $('.list-toolbar .count').textContent = `显示 1–${list.length} 共 ${list.length} 双`;
    }

    // 品牌
    $$('#filter-brand input').forEach(cb => cb.addEventListener('change', e => {
      const b = e.target.dataset.brand;
      e.target.checked ? state.brand.add(b) : state.brand.delete(b);
      apply();
    }));
    // 尺码
    $$('#filter-size .size').forEach(el => el.addEventListener('click', () => {
      const s = el.dataset.size;
      el.classList.toggle('active');
      el.classList.contains('active') ? state.size.add(s) : state.size.delete(s);
      apply();
    }));
    // 价格
    const priceInput = $('#filter-price');
    if (priceInput) priceInput.addEventListener('input', e => {
      state.maxPrice = Number(e.target.value);
      $('#filter-price-val').textContent = '$' + state.maxPrice;
      apply();
    });
    // 排序
    $('#sort-select')?.addEventListener('change', e => { state.sort = e.target.value; apply(); });
    // 视图切换
    $$('.view-toggle button').forEach(b => b.addEventListener('click', () => {
      $$('.view-toggle button').forEach(x => x.classList.remove('active'));
      b.classList.add('active');
    }));

    apply();
  }

  // ===== 详情页 =====
  function bindProductDetail() {
    if (!$('#p-detail')) return;
    const id = Number(new URLSearchParams(location.search).get('id')) || 1;
    const all = window.JK_DATA.listProducts;
    const p = all.find(x => x.id === id) || all[0];

    $('.p-brand-line').textContent = p.brand;
    $('#p-title').textContent = p.name;
    $('.detail .info .sub').textContent = `Authentic · ${p.brand} · Free shipping`;
    $('#p-price-now').textContent = fmt(p.price);
    $('#p-price-was').textContent = p.was ? fmt(p.was) : '';
    $('#p-rating').textContent = p.rating;
    $('#p-reviews').textContent = p.reviews;
    const off = p.was && p.was > p.price ? Math.round((1 - p.price / p.was) * 100) : 0;
    $('#p-save').textContent = off ? `立减 ${off}%` : '';

    // 颜色
    const colorRow = $('#p-colors');
    colorRow.innerHTML = p.colors.map((c, i) => `<div class="swatch ${i === 0 ? 'active' : ''}" style="background:${c}" data-c="${c}"></div>`).join('');
    colorRow.addEventListener('click', e => {
      const s = e.target.closest('.swatch');
      if (!s) return;
      $$('.swatch', colorRow).forEach(x => x.classList.remove('active'));
      s.classList.add('active');
    });

    // 尺码
    const sizeRow = $('#p-sizes');
    sizeRow.innerHTML = p.sizes.map((s, i) => `<div class="size ${i === 2 ? 'active' : ''}" data-s="${s}">US ${s}</div>`).join('');
    sizeRow.addEventListener('click', e => {
      const s = e.target.closest('.size');
      if (!s) return;
      $$('.size', sizeRow).forEach(x => x.classList.remove('active'));
      s.classList.add('active');
    });

    // 数量
    const qtyInput = $('#p-qty');
    $('.qty-dec')?.addEventListener('click', () => qtyInput.value = Math.max(1, Number(qtyInput.value) - 1));
    $('.qty-inc')?.addEventListener('click', () => qtyInput.value = Math.min(10, Number(qtyInput.value) + 1));

    // 缩略图切换
    const main = $('#p-main-emoji');
    $$('.g-thumbs .thumb').forEach(t => t.addEventListener('click', () => {
      $$('.g-thumbs .thumb').forEach(x => x.classList.remove('active'));
      t.classList.add('active');
      main.textContent = t.dataset.emoji || main.textContent;
    }));

    // Tabs
    $$('.tabs-nav button').forEach(b => b.addEventListener('click', () => {
      $$('.tabs-nav button, .tab-content').forEach(x => x.classList.remove('active'));
      b.classList.add('active');
      $('#tab-' + b.dataset.tab).classList.add('active');
    }));

    // 加入购物车
    $('#add-to-cart')?.addEventListener('click', () => {
      const size = $('.size.active', sizeRow)?.dataset.s;
      if (!size) return toast('请先选择尺码');
      Cart.add({
        id: p.id, name: p.name, brand: p.brand,
        price: p.price, size: Number(size),
        qty: Number(qtyInput.value) || 1,
        emoji: p.emoji,
      });
    });
    $('#buy-now')?.addEventListener('click', () => {
      $('#add-to-cart').click();
      setTimeout(() => location.href = 'cart.html', 400);
    });

    // 相关推荐
    const related = all.filter(x => x.id !== p.id).slice(0, 4);
    renderProducts('#related-grid', related);
  }

  // ===== 购物车页面 =====
  function bindCart() {
    const wrap = $('#cart-body');
    if (!wrap) return;
    function render() {
      const items = Cart.get();
      if (!items.length) {
        wrap.innerHTML = `<div class="empty"><div class="ic">🛒</div><h3>购物车是空的</h3><p>先去逛逛，挑几双心仪的球鞋吧</p><a class="btn btn-gold" href="list.html">浏览商品</a></div>`;
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
                <div class="meta">${it.brand} · US ${it.size}</div>
              </div>
              <div class="price">${fmt(it.price)}</div>
              <div class="qty">
                <button data-act="dec" data-id="${it.id}" data-size="${it.size}">−</button>
                <input value="${it.qty}" data-id="${it.id}" data-size="${it.size}" />
                <button data-act="inc" data-id="${it.id}" data-size="${it.size}">+</button>
              </div>
              <button class="rm" data-act="rm" data-id="${it.id}" data-size="${it.size}" aria-label="Remove">×</button>
            </div>
          `).join('')}
        </div>
      `;
      const sub = Cart.total();
      const ship = sub > 200 ? 0 : 12;
      const tax = Math.round(sub * 0.08);
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
      if (act === 'inc') {
        const it = Cart.get().find(i => i.id === Number(id) && i.size == size);
        Cart.updateQty(Number(id), size, it.qty + 1); render();
      }
      if (act === 'dec') {
        const it = Cart.get().find(i => i.id === Number(id) && i.size == size);
        Cart.updateQty(Number(id), size, it.qty - 1); render();
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

  // ===== 收藏 =====
  function bindWishlist() {
    document.addEventListener('click', e => {
      const b = e.target.closest('[data-wish]');
      if (!b) return;
      e.preventDefault();
      e.stopPropagation();
      const id = b.dataset.wish;
      const list = JSON.parse(localStorage.getItem('jk_wish') || '[]');
      const i = list.indexOf(id);
      if (i >= 0) { list.splice(i, 1); b.textContent = '♡'; toast('已取消收藏'); }
      else { list.push(id); b.textContent = '♥'; b.style.color = '#c8102e'; toast('已加入收藏'); }
      localStorage.setItem('jk_wish', JSON.stringify(list));
    });
  }

  // ===== 暴露全局 =====
  window.JK = { Cart, toast, renderProducts, productCard, fmt, $, $$ };

  onReady(() => {
    Cart.updateBadges();
    bindListPage();
    bindProductDetail();
    bindCart();
    bindWishlist();
    bindFaq();

    // 快速加购 (首页 / 列表 / 相关推荐卡片 hover 后)
    document.addEventListener('click', e => {
      const q = e.target.closest('.p-quick');
      if (!q) return;
      e.preventDefault();
      e.stopPropagation();
      const card = q.closest('.product');
      const id = Number((card.getAttribute('href') || '').split('=')[1]);
      const p = (window.JK_DATA.listProducts || []).find(x => x.id === id);
      if (p) Cart.add({ id: p.id, name: p.name, brand: p.brand, price: p.price, size: p.sizes[2] || 9, qty: 1, emoji: p.emoji });
    });

    // 移动端 nav 切换
    $('.mobile-toggle')?.addEventListener('click', () => {
      const nav = $('.nav');
      if (nav) nav.style.display = nav.style.display === 'flex' ? 'none' : 'flex';
    });
  });
})();
