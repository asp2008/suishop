/* JojoKicks — 通用 header / footer 注入
   所有页面只需放置 <div id="jk-header"></div> <div id="jk-footer"></div>
   会自动渲染顶栏 + 导航 + 页脚
*/
(function () {
  const headerHtml = `
<div class="topbar">
  <div class="container topbar-inner">
    <div class="tb-links">
      <a href="#">📦 全美免邮 · 订单满 $200</a>
      <a href="#">✨ 新人首单 9 折 — 优惠码 NEW10</a>
    </div>
    <div class="tb-social">
      <a href="#">中文</a>
      <span style="opacity:.4">/</span>
      <a href="#">EN</a>
      <span style="opacity:.4">·</span>
      <a href="#">Instagram</a>
      <a href="#">TikTok</a>
    </div>
  </div>
</div>

<header class="header">
  <div class="container header-main">
    <a class="brand" href="index.html">
      <span class="brand-mark">JK</span>
      <span>JOJO<span class="accent">KICKS</span></span>
    </a>
    <nav class="nav">
      <a href="index.html">首页</a>
      <a href="list.html" class="has-drop">
        男士球鞋
        <div class="drop">
          <a href="list.html?cat=jordan">Jordan</a>
          <a href="list.html?cat=nike">Nike</a>
          <a href="list.html?cat=yeezy">Yeezy</a>
          <a href="list.html?cat=newbalance">New Balance</a>
          <a href="list.html?cat=asics">Asics</a>
        </div>
      </a>
      <a href="list.html?cat=running">跑步</a>
      <a href="list.html?cat=basketball">篮球</a>
      <a href="list.html?cat=lifestyle">潮流生活</a>
      <a href="pages/membership.html">JojoClub 会员</a>
      <a href="pages/blog.html">资讯</a>
      <a href="pages/about.html">关于</a>
    </nav>
    <div class="header-actions">
      <div class="search-wrap">
        <input class="search-input" placeholder="搜索品牌、款号…" />
      </div>
      <button class="icon-btn" aria-label="Wishlist">♡</button>
      <a class="icon-btn" href="pages/login.html" aria-label="Account">👤</a>
      <a class="icon-btn" href="cart.html" aria-label="Cart">🛒<span class="badge" data-cart-count style="display:none">0</span></a>
      <button class="icon-btn mobile-toggle" aria-label="Menu">☰</button>
    </div>
  </div>
</header>`;

  const footerHtml = `
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div class="brand-block">
        <div class="brand"><span class="brand-mark">JK</span><span>JOJO<span class="accent">KICKS</span></span></div>
        <p>成立于 2019 年的 authentic 球鞋电商。专业鉴定师团队 · 全球直采 · 服务超过 12 万 sneakerhead。</p>
        <div class="socials">
          <a href="#">📷</a>
          <a href="#">🎵</a>
          <a href="#">▶</a>
          <a href="#">🐦</a>
        </div>
      </div>
      <div>
        <h5>购买</h5>
        <ul>
          <li><a href="list.html">所有商品</a></li>
          <li><a href="list.html?cat=jordan">Jordan</a></li>
          <li><a href="list.html?cat=nike">Nike</a></li>
          <li><a href="list.html?cat=yeezy">Yeezy</a></li>
          <li><a href="list.html?cat=newbalance">New Balance</a></li>
          <li><a href="pages/sell.html">寄售球鞋</a></li>
        </ul>
      </div>
      <div>
        <h5>支持</h5>
        <ul>
          <li><a href="pages/contact.html">联系我们</a></li>
          <li><a href="pages/faq.html">常见问题</a></li>
          <li><a href="pages/size-guide.html">尺码指南</a></li>
          <li><a href="pages/shipping.html">配送说明</a></li>
          <li><a href="pages/returns.html">退换货政策</a></li>
          <li><a href="pages/orders.html">订单追踪</a></li>
        </ul>
      </div>
      <div>
        <h5>关于 JojoKicks</h5>
        <ul>
          <li><a href="pages/about.html">品牌故事</a></li>
          <li><a href="pages/membership.html">JojoClub 会员</a></li>
          <li><a href="pages/blog.html">资讯博客</a></li>
          <li><a href="pages/careers.html">职业机会</a></li>
          <li><a href="pages/press.html">媒体合作</a></li>
          <li><a href="pages/sustainability.html">可持续承诺</a></li>
        </ul>
      </div>
      <div>
        <h5>联系方式</h5>
        <ul>
          <li>📍 118 Sneaker Lane, Brooklyn, NY 11201</li>
          <li>✉️ hello@jojokicks.one</li>
          <li>📞 +1 (212) 555-0100</li>
          <li style="margin-top:14px"><a class="btn btn-outline btn-sm" href="pages/contact.html">联系客服</a></li>
        </ul>
      </div>
    </div>
    <div class="footer-bottom">
      <div>© 2026 JojoKicks Inc. · All rights reserved.</div>
      <div class="pay">
        <span>VISA</span><span>MASTERCARD</span><span>AMEX</span><span>PAYPAL</span><span>APPLE PAY</span><span>SHOP PAY</span><span>KLARNA</span>
      </div>
    </div>
  </div>
</footer>`;

  function getPrefix() {
    // 兼容 pages/ 子目录
    return location.pathname.includes('/pages/') ? '../' : '';
  }

  function fixPaths(html) {
    const p = getPrefix();
    if (!p) return html;
    return html
      .replace(/href="(index\.html|list\.html|product\.html|cart\.html|checkout\.html)/g, `href="${p}$1`)
      .replace(/href="pages\//g, `href="${p}pages/`)
      .replace(/src="css\//g, `src="${p}css/`)
      .replace(/src="js\//g, `src="${p}js/`);
  }

  document.addEventListener('DOMContentLoaded', () => {
    const h = document.getElementById('jk-header');
    const f = document.getElementById('jk-footer');
    if (h) h.innerHTML = fixPaths(headerHtml);
    if (f) f.innerHTML = fixPaths(footerHtml);
  });
})();
