# 👟 JOJOKICKS — Authentic Streetwear & Luxury E-commerce

> 仿 jojokicks.one 真实设计 — 日式潮流买手店风格，30 个页面，纯 HTML/CSS/JS，**完整响应式**（1440 / 1024 / 768 / 480）。

![Pages](https://img.shields.io/badge/pages-30-blue) ![Responsive](https://img.shields.io/badge/responsive-yes-success) ![Stack](https://img.shields.io/badge/stack-HTML%2FCSS%2FJS%20%7C%20zero%20deps-orange)

---

## ✨ 设计还原要点

基于真实 jojokicks.one 截图（20986px 长图）1:1 复刻：

- 🎨 **白底 + 红色价格** 配色（`#d11c2c`）
- 🇯🇵 **日文界面 + ¥ 日元价格**
- 🏷️ **品牌专区** 9 个：SUPREME / CHROME HEARTS / RICK OWENS / 人気スニーカー / Tシャツ / ストリートウェア / ベルト / BALENCIAGA / ACNE STUDIOS
- 📱 **响应式**：4 列 → 3 列 → 2 列 → 1 列，移动端 hamburger menu
- 💬 **用户评价区**（Spread / Quotation 风格）— 4.8 星 + 4 列卡片
- 🛡️ **服务保障区** — 配送 / 支付 / LINE / 購入保障
- ⬛ **黑色页脚** — 6 列 + 支付方式

---

## 📂 项目结构

```
suishop/
├── index.html              # 首页 (9 个商品专区)
├── list.html               # 列表 + 筛选
├── product.html            # 商品详情
├── cart.html               # 购物车
├── checkout.html           # 结账
├── pages/                  # 二级页面 (25 个)
│   ├── login.html · register.html
│   ├── account.html · orders.html · order-detail.html · order-success.html
│   ├── wishlist.html · addresses.html · payment.html · points.html
│   ├── settings.html · support.html
│   ├── membership.html · sell.html
│   ├── blog.html · blog-detail.html · reviews.html
│   ├── about.html · contact.html · faq.html
│   └── shipping.html · returns.html · size-guide.html
│   └── terms.html · privacy.html
├── css/
│   └── style.css           # 全部样式 (40KB · 完整响应式)
├── js/
│   ├── data.js             # 100+ 商品 / 品牌 / 评价
│   ├── components.js       # Header / Footer 注入
│   └── main.js             # 购物车 / 收藏 / FAQ
└── assets/
```

---

## 🚀 快速开始

```bash
# 本地预览
python3 -m http.server 8000
# 打开 http://localhost:8000

# 或
npx serve .
```

---

## 📱 响应式断点

| 设备 | 断点 | 主要调整 |
|---|---|---|
| 桌面 | ≥ 1024 | 4 列网格、完整 nav、5 列页脚 |
| 平板 | 768 - 1024 | 3 列网格、隐藏侧栏、3 列页脚 |
| 手机 | 480 - 768 | 2 列网格、hamburger menu、2 列页脚 |
| 小手机 | < 480 | 1 列布局、单列页脚、紧凑间距 |

---

## 🛠️ 自定义

### 替换商品图（emoji → 真实图）

```html
<!-- 之前 -->
<div class="emoji">👕</div>
<!-- 之后 -->
<img src="assets/images/supreme-tee.jpg" alt="SUPREME Tee" />
```

### 修改商品数据

编辑 `js/data.js`：
```js
{ id: 1, brand: 'SUPREME', name: '...', price: 9290, was: null, tag: 'new', emoji: '👕' }
```

### 接入真实后端

替换 `js/main.js` 中的 `Cart` 对象为 API：
```js
const Cart = {
  get: () => fetch('/api/cart').then(r => r.json()),
  add: item => fetch('/api/cart', { method: 'POST', body: JSON.stringify(item) }),
  // ...
};
```

---

## 🌐 浏览器兼容

Chrome / Edge / Safari / Firefox 最新版 · iOS Safari 14+ · Android Chrome 90+

---

## 📄 License

MIT — 自由使用、修改、商用。
