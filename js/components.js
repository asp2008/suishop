/* JojoKicks — Header / Footer 注入 */
(function () {
  const isSub = () => location.pathname.includes('/pages/');
  const p = () => isSub() ? '../' : '';

  const headerHtml = () => `
<div class="topbar">
  <div class="topbar-inner">
    <div class="tb-left">
      <span>📦 全日本送料無料 ¥10,000 以上</span>
      <span>✨ 新規会員 ¥3,000 OFF クーポン</span>
    </div>
    <div class="tb-right">
      <a href="#">中文</a>
      <span style="opacity:.4">/</span>
      <a href="#">日本語</a>
      <a href="#">${''}</a>
      <a href="#">${''}</a>
    </div>
  </div>
</div>

<header class="header">
  <div class="header-main">
    <div class="header-left">
      <button class="icon-btn menu-btn" aria-label="Menu">≡</button>
    </div>
    <a class="brand" href="${p()}index.html">JOJOKICKS</a>
    <div class="header-right">
      <a class="icon-btn" href="${p()}pages/wishlist.html" aria-label="Wishlist"><span class="ic">♡</span><span class="label" data-wish-count></span></a>
      <a class="icon-btn" href="${p()}pages/login.html" aria-label="Account"><span class="ic">👤</span><span class="label">ログイン</span></a>
      <a class="icon-btn" href="${p()}cart.html" aria-label="Cart"><span class="ic">🛒</span><span class="label">カート</span><span class="badge" data-cart-count data-count="0">0</span></a>
    </div>
  </div>
  <div class="search-wrap" style="position:relative;max-width:560px;margin:0 auto;padding:0 20px 12px">
    <input class="search-input" placeholder="キーワード・ブランド・商品名を検索..." />
    <button class="search-btn">🔍</button>
  </div>
</header>

<nav class="nav-strip">
  <div class="container">
    <div class="nav-strip-inner">
      <a href="${p()}index.html">HOME</a>
      <a href="${p()}list.html?cat=new">NEW ARRIVAL</a>
      <a href="${p()}list.html?cat=supreme">シュプリーム (SUPREME)</a>
      <a href="${p()}list.html?cat=chromehearts">クロムハーツ (CHROME HEARTS)</a>
      <a href="${p()}list.html?cat=rickowens">リック オーウェンス (RICK OWENS)</a>
      <a href="${p()}list.html?cat=sneaker">人気スニーカー</a>
      <a href="${p()}list.html?cat=tshirt">人気 T シャツ</a>
      <a href="${p()}list.html?cat=streetwear">人気ストリートウェア</a>
      <a href="${p()}list.html?cat=belt">人気ベルト</a>
      <a href="${p()}list.html?cat=balenciaga">バレンシアガ (BALENCIAGA)</a>
      <a href="${p()}list.html?cat=acne">アクネ ストゥディオズ (ACNE STUDIOS)</a>
      <a href="${p()}pages/membership.html">会員ランク</a>
      <a href="${p()}pages/blog.html">BLOG</a>
    </div>
  </div>
</nav>`;

  const footerHtml = () => `
<footer class="footer">
  <div class="container">
    <div class="footer-grid">
      <div>
        <h5>お客様サポート</h5>
        <div style="display:flex;gap:6px;align-items:center;margin-bottom:12px">
          <div style="background:var(--green);color:#fff;padding:6px 10px;border-radius:4px;font-weight:700;font-size:12px">LINE</div>
          <strong style="font-size:14px">友だち追加</strong>
        </div>
        <p style="margin:0 0 10px;font-size:11px">ご注文情報、お支払いや<br>出荷状況などをお知らせします。</p>
        <p style="margin:0;font-size:11px;color:#999">E-mail: service@jojokicks.one<br>TEL: 050-5840-2607<br>受付時間: 平日10:00〜23:00</p>
        <div class="social">
          <a href="#">📷</a>
          <a href="#">🎵</a>
          <a href="#">▶</a>
          <a href="#">🐦</a>
        </div>
      </div>
      <div>
        <h5>私たちに参加しよう</h5>
        <div style="display:flex;gap:6px;align-items:center;margin-bottom:10px">
          <div style="background:#5865F2;color:#fff;padding:6px 10px;border-radius:4px;font-weight:700;font-size:12px">Discord</div>
        </div>
        <p style="margin:0;font-size:11px">私たちのdiscordサーバーに参加し、<br>20,000 を超える交流しよう！</p>
      </div>
      <div>
        <h5>ご注文を追跡</h5>
        <div style="display:flex;gap:6px;align-items:center;margin-bottom:10px">
          <div style="background:#fff;color:#000;padding:6px 10px;border-radius:4px;font-size:18px">📦</div>
        </div>
        <p style="margin:0;font-size:11px">クリックすると配送状況を確認できます</p>
        <p style="margin:10px 0 0;font-size:11px;color:#999">ご注文番号を入力してください</p>
      </div>
      <div>
        <h5>私たちに連絡</h5>
        <ul>
          <li><a href="${p()}pages/contact.html">お問い合わせ</a></li>
          <li><a href="${p()}pages/about.html">会社概要</a></li>
          <li><a href="${p()}pages/returns.html">返品ポリシー</a></li>
          <li><a href="${p()}pages/shipping.html">配送と返品</a></li>
          <li><a href="${p()}pages/terms.html">利用規約</a></li>
          <li><a href="${p()}pages/privacy.html">プライバシーポリシー</a></li>
        </ul>
      </div>
      <div>
        <h5>ログイン</h5>
        <div style="display:flex;gap:6px;margin-bottom:10px">
          <span style="color:#b0b0b0">⚲</span>
          <span style="color:#b0b0b0">⚲</span>
          <span style="color:#b0b0b0">⚲</span>
          <span style="color:#b0b0b0">⚲</span>
        </div>
        <ul>
          <li><a href="${p()}pages/login.html">ログイン</a></li>
          <li><a href="${p()}pages/register.html">登録する</a></li>
          <li><a href="${p()}pages/account.html">マイアカウント</a></li>
          <li><a href="${p()}pages/orders.html">ご注文</a></li>
          <li><a href="${p()}pages/wishlist.html">お気に入りリスト</a></li>
          <li><a href="#">トラッキング</a></li>
        </ul>
      </div>
      <div>
        <h5>サービスセンター</h5>
        <ul>
          <li><a href="#">特定商取引法に基づく表記</a></li>
          <li><a href="#">配送ポリシー</a></li>
          <li><a href="#">返金ポリシー</a></li>
          <li><a href="#">利用規約</a></li>
          <li><a href="#">プライバシーポリシー</a></li>
        </ul>
        <h5 style="margin-top:18px">支払いオプション</h5>
        <div class="pay-row">
          <span class="pay">VISA</span>
          <span class="pay">MC</span>
          <span class="pay">JCB</span>
          <span class="pay">AMEX</span>
          <span class="pay">Pay</span>
          <span class="pay">PayPay</span>
          <span class="pay">LINE</span>
        </div>
      </div>
    </div>
    <div class="footer-bottom">
      <div>© 2026 JOJOKICKS Copyright. All Rights Reserved.</div>
    </div>
  </div>
</footer>`;

  document.addEventListener('DOMContentLoaded', () => {
    const h = document.getElementById('jk-header');
    const f = document.getElementById('jk-footer');
    if (h) h.innerHTML = headerHtml();
    if (f) f.innerHTML = footerHtml();
  });
})();
