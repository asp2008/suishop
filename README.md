# 👟 JojoKicks — Authentic Sneaker E-commerce

> 一套完整的 authentic 球鞋电商前端模板 — 黑金 + Joker 主题设计，32 个页面，纯 HTML/CSS/JS，零依赖、开箱即用。

![Status](https://img.shields.io/badge/status-ready-success) ![Pages](https://img.shields.io/badge/pages-32-blue) ![Stack](https://img.shields.io/badge/stack-HTML%2FCSS%2FJS%20%7C%20zero%20deps-orange)

---

## ✨ 特性

- 🎨 **Joker 黑金主题** — 暗色系 + 金色高亮，球鞋潮流站经典调性
- 📱 **完整响应式** — 桌面 / 平板 / 移动端三档断点
- 🛒 **完整电商流程** — 浏览 → 详情 → 加购 → 结账 → 订单 → 会员中心
- 💳 **多支付方式** — Visa、PayPal、Apple Pay、Klarna 分期
- 👤 **会员中心** — 订单、收藏、地址、积分、设置
- 📰 **内容板块** — 博客列表 + 详情页、FAQ 折叠、关于、联系
- ⚡ **零依赖** — 纯 HTML/CSS/JS，3 个 JS 文件，可直接部署到任何静态托管
- 🧩 **可复用组件** — Header/Footer 用 `components.js` 动态注入，自动适配根/子目录

---

## 📂 项目结构

```
suishop/
├── index.html              # 首页
├── list.html               # 商品列表 + 筛选
├── product.html            # 商品详情
├── cart.html               # 购物车
├── checkout.html           # 结账
├── pages/                  # 二级页面
│   ├── login.html          # 登录
│   ├── register.html       # 注册
│   ├── account.html        # 会员中心概览
│   ├── orders.html         # 我的订单
│   ├── order-detail.html   # 订单详情 + 物流追踪
│   ├── order-success.html  # 下单成功页
│   ├── wishlist.html       # 我的收藏
│   ├── addresses.html      # 收货地址
│   ├── payment.html        # 支付方式
│   ├── points.html         # JojoClub 积分
│   ├── settings.html       # 账号设置
│   ├── support.html        # 客服中心
│   ├── membership.html     # JojoClub 会员
│   ├── sell.html           # 寄售球鞋
│   ├── blog.html           # 博客列表
│   ├── blog-detail.html    # 博客详情
│   ├── faq.html            # 常见问题
│   ├── about.html          # 关于我们
│   ├── contact.html        # 联系我们
│   ├── size-guide.html     # 尺码指南
│   ├── shipping.html       # 配送说明
│   ├── returns.html        # 退换货政策
│   ├── careers.html        # 加入我们
│   ├── press.html          # 媒体合作
│   ├── sustainability.html # 可持续承诺
│   ├── terms.html          # 服务条款
│   └── privacy.html        # 隐私政策
├── css/
│   └── style.css           # 全部样式 (39KB)
├── js/
│   ├── data.js             # 商品/分类/博客 mock 数据
│   ├── components.js       # Header/Footer 注入
│   └── main.js             # 购物车/筛选/交互
└── assets/
    ├── images/             # 商品图（占位）
    └── icons/              # 图标（占位）
```

---

## 🚀 快速开始

### 本地预览

```bash
# 方式 1: 直接打开
open index.html

# 方式 2: 启动本地服务器（推荐）
python3 -m http.server 8000
# 访问 http://localhost:8000

# 方式 3: Node.js
npx serve .
```

### 部署

支持任何静态托管：

```bash
# Vercel
vercel deploy

# Netlify
netlify deploy --prod --dir .

# GitHub Pages
# 推送到 main 分支，在 Settings → Pages 选择 root

# 阿里云 / 腾讯云 OSS / 华为云 OBS
# 上传整个目录到 bucket，开启静态网站托管
```

---

## 🎨 设计系统

### 颜色

| 名称 | 变量 | 用途 |
|---|---|---|
| 主背景 | `--bg: #0a0a0a` | 整体背景 |
| 卡片背景 | `--bg-card: #1a1a1a` | 商品卡、面板 |
| 主金色 | `--gold: #d4af37` | CTA、强调、会员色 |
| 警示红 | `--red: #c8102e` | 促销、热卖 |
| 成功绿 | `--green: #2ecc71` | 库存、已送达 |
| 主文字 | `--text: #f5f5f5` | 标题、正文 |
| 弱文字 | `--text-soft: #a3a3a3` | 描述、辅助 |

### 字体

- 标题: `Inter` 800/900
- 正文: `Inter` 400/500
- 中文: PingFang SC / Microsoft YaHei（系统兜底）

### 组件

- 按钮: `.btn` + `.btn-gold` / `.btn-outline` / `.btn-dark` / `.btn-ghost` + `.btn-sm` / `.btn-lg` / `.btn-block`
- 商品卡: `.product` 包含 `.p-media` `.p-body`
- 表单: `.field` + `.field input/select/textarea`
- 提示: 调用 `JK.toast('消息')`

---

## 🛠️ 自定义

### 1. 替换商品图片

把 emoji 换成真实图片：

```html
<!-- 之前 -->
<div class="emoji">👟</div>

<!-- 之后 -->
<img src="assets/images/jordan-1-bred.jpg" alt="Air Jordan 1 Bred" />
```

### 2. 修改商品数据

编辑 `js/data.js`：

```js
window.JK_DATA = {
  products: [
    {
      id: 1,
      brand: 'Jordan',
      name: 'Air Jordan 1 Retro High OG "Bred"',
      price: 320,
      was: 380,        // 划线价，可选
      tag: 'hot',      // 'new' | 'hot' | 'sale' | null
      rating: 4.9,
      reviews: 234,
      emoji: '👟',     // 或换成 image URL
      colors: ['#c8102e','#000','#fff'],
      sizes: [7,7.5,8,8.5,9,9.5,10,10.5,11,12]
    },
    // ...更多
  ]
};
```

### 3. 修改品牌信息

`js/components.js` 中搜索 `JOJO` `KICKS` `Brooklyn` `jojokicks.one` 替换。

### 4. 接入真实后端

替换 `js/main.js` 中的 `Cart` 对象为 API 调用：

```js
const Cart = {
  get: () => fetch('/api/cart').then(r => r.json()),
  add: item => fetch('/api/cart', { method: 'POST', body: JSON.stringify(item) }),
  // ...
};
```

---

## 📦 主要功能演示

| 页面 | 功能 |
|---|---|
| 首页 | Hero 轮播、分类入口、热销商品、订阅 |
| 列表 | 品牌/尺码/价格筛选、排序、网格/列表视图、分页 |
| 详情 | 颜色/尺码选择、数量、加入购物车、立即购买、Tab 切换、相关推荐 |
| 购物车 | 增删改数量、优惠码、自动算税、运费 |
| 结账 | 多步骤（信息/地址/物流/支付）、5 种支付方式 |
| 会员中心 | 数据卡片、最近订单、推荐 |
| 订单详情 | 物流进度时间线、商品明细、申请退换 |
| 博客 | 列表 + 详情 + 分类筛选 |

---

## 🌐 浏览器兼容

- Chrome / Edge 90+
- Safari 14+
- Firefox 88+
- 移动端 Safari iOS 14+ / Chrome Android 90+

---

## 📄 License

MIT — 自由使用、修改、商用。

---

## 🙌 关于

JojoKicks Frontend Template — 设计师/前端开发者的 sneaker 电商模板起点。

> "每一双球鞋都有自己的故事 · 我们只是把它们送到对的人手里。"
