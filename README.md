# 蔵市 KURA-ICHI / suishop

> 日本各地の蔵元・窯元・職人とつながる、苹果风 Apple Design Language 风格的多商户电商前台。
> 简体中文（电商流程）+ 日文视觉标签（保留 KURA-ICHI 风味）。

基于 **ThinkPHP 3.1.2**（含完整 Lib 核心） + jQuery + 原生 CSS/JS。
风格沿用 KURA-ICHI 主题（毛玻璃顶部、圆角胶囊按钮、SF 字体栈、Apple 灰白主色 + 蓝主色）。

---

## 目录结构

```
suishop/
├── index.php                       # TP3 入口
├── App/                            # 应用目录
│   ├── Conf/
│   │   ├── config.php              # 数据库 / URL / 模板变量
│   │   └── debug.php               # 调试模式配置
│   ├── Common/common.php           # 全局函数 (format_price / build_order_sn / require_login …)
│   ├── Modules/
│   │   └── default/                # 前台模块
│   │       ├── Lib/Action/
│   │       │   ├── CommonAction.class.php   # 公用基类（站点、导航、购物车数量、会员）
│   │       │   ├── MyAction.class.php       # 首页 / 列表 / 详情 / 品牌 / 文章 / 搜索 / 收藏 / 反馈
│   │       │   ├── AccountAction.class.php  # 登录 / 注册 / 会员中心 / 订单 / 地址 / 积分 / 站内信
│   │       │   ├── AjaxAction.class.php     # 验证码 / 省市区 / 收藏 / 订阅 / 优惠券
│   │       │   └── CartAction.class.php     # 购物车 / 结算 / 提交订单 / 订单完成 / 支付
│   │       └── Tpl/                # Default 模板
│   │           ├── Public/         # header / footer / 404 / 商品卡 partial
│   │           ├── My/             # 首页 / 列表 / 详情 / 品牌 / 文章 / 搜索 / FAQ / links / feedback
│   │           ├── Cart/           # 购物车 / 结算 / 订单完成
│   │           └── Account/        # 登录 / 注册 / dashboard / orders / order_detail / address / favorite / point / message / profile / password
│   ├── Runtime/                    # 运行时缓存（已 .gitkeep）
│   └── ThinkPHP/                   # TP3.1.2 核心（完整嵌入）
├── Public/
│   └── theme/                      # 前台静态资源
│       ├── css/theme.css           # Apple 风格主样式（53KB，含 25+ 页面所有组件）
│       ├── js/main.js              # 交互脚本（轮播 / 灯箱 / 购物车 / 聊天 / Toast）
│       └── images/                 # logo / 占位 / 支付图标
└── data/
    └── suishop.sql                 # 数据库导出（div_ 前缀，与 SQL 文件保持一致）
```

---

## 快速开始

### 1. 部署

1. 上传所有文件到 PHP (5.3 ~ 5.6) 主机，推荐 Apache/Nginx + PHP 5.6+。
2. 修改 `App/Conf/config.php`：
   ```php
   'DB_HOST'    => 'localhost',
   'DB_NAME'    => 'suishop',
   'DB_USER'    => 'root',
   'DB_PWD'     => 'yourpass',
   'DB_PREFIX'  => 'div_',
   ```
3. 导入 `data/suishop.sql` 到 MySQL（5.5+，推荐 utf8mb4）。
4. 浏览器访问 `http://你的域名/` 即可。

### 2. URL 模式

默认 `URL_MODEL = 0`（标准 `?m=My&a=index`）。如需 pathinfo（`/My/index.html`），改为：
```php
'URL_MODEL' => 1,
'URL_HTML_SUFFIX' => '.html',
```
然后配置 Nginx/Apache rewrite（参考 TP3 官方文档）。

### 3. 默认模块

- 前台：`default`（首页、列表、详情、会员中心 …）
- 后台：暂未搭建（用户原本只要求前台）

### 4. 演示账号

注册任意账号即得 500 积分。
测试优惠券码（结算页用）：`KURA10`（95折）、`NEW5000`（减 5000）。

---

## 页面清单

| 页面 | URL | 说明 |
|------|------|------|
| 首页 | `?m=My&a=index` | Hero 轮播 + 6 格分类 + 推荐 + 品牌 + 季节限定 + 热销 |
| 商品列表 | `?m=My&a=lists` | 支持 `catid` / `brand` / `q` / `min` / `max` / `sort` / `p` |
| 商品详情 | `?m=My&a=detail&id=` | 多图灯箱、规格、数量、加入购物车 / 立即购买、收藏、tab 切换 |
| 品牌列表 | `?m=My&a=brand` | 全部品牌 |
| 品牌详情 | `?m=My&a=brand_show&id=` | 品牌介绍 + 旗下商品 |
| 文章列表 | `?m=My&a=article_lists` | 支持栏目 |
| 文章详情 | `?m=My&a=article_show&id=` | 内容 + 上下篇 |
| 单页 | `?m=My&a=page&id=` | 关于我们 / 配送说明 / 隐私政策 / 退换货 |
| FAQ | `?m=My&a=faq` | 手风琴展开 |
| 友情链接 | `?m=My&a=links` | |
| 全站搜索 | `?m=My&a=search&q=` | |
| 收藏（需登录） | `?m=My&a=favorite_add`（Ajax） | |
| 反馈 | `?m=My&a=feedback` | 留言提交 |
| 登录 | `?m=Account&a=login` | |
| 注册 | `?m=Account&a=register` | 注册送 500 积分 |
| 登出 | `?m=Account&a=logout` | |
| 会员中心 | `?m=Account&a=dashboard` | 概览 + 订单 + 收藏 + 地址 + FAQ + 客服聊天 |
| 订单列表 | `?m=Account&a=orders` | tab 切换（全部/待付/待发/配送/完成） |
| 订单详情 | `?m=Account&a=order_detail&id=` | 4 步进度流 |
| 取消订单 | `?m=Account&a=order_cancel`（Ajax） | |
| 确认收货 | `?m=Account&a=order_confirm`（Ajax） | |
| 收货地址 | `?m=Account&a=address` | 支持默认设置、增删改 + 省市区联动 |
| 收藏列表 | `?m=Account&a=favorite` | |
| 积分 | `?m=Account&a=point` | 每日签到 +10 |
| 站内信 | `?m=Account&a=message` / `message_detail&id=` | |
| 资料修改 | `?m=Account&a=profile` | |
| 修改密码 | `?m=Account&a=password` | |
| 购物车 | `?m=Cart&a=index` | |
| 结算 | `?m=Cart&a=checkout` | 收货地址 / 配送 / 支付 / 备注 / 商品清单 / 优惠码 |
| 提交订单 | `?m=Cart&a=submit`（POST） | |
| 订单完成 | `?m=Cart&a=done&sn=` | 4 步进度 + 立即支付 |
| 支付（演示） | `?m=Cart&a=pay&sn=` | 自动置已付款、加积分 |
| 验证码 | `?m=Ajax&a=verify` | PNG |
| 省市区 | `?m=Ajax&a=area&parent=` | JSON |
| 搜索建议 | `?m=Ajax&a=suggest&q=` | JSON |
| 收藏切换 | `?m=Ajax&a=favorite`（POST） | JSON |
| 优惠券领取 | `?m=Ajax&a=coupon_take`（POST） | JSON |
| 上传 | `?m=Ajax&a=upload` | JSON |
| 邮件订阅 | `?m=Ajax&a=subscribe`（POST） | JSON |

---

## 数据表（div_ 前缀）

| 表 | 说明 |
|---|---|
| `div_user` | 会员（已含字段兼容历史数据） |
| `div_user_address` | 收货地址（多对一） |
| `div_user_collect` | 收藏 |
| `div_user_coupon` | 用户优惠券 |
| `div_user_level` | 会员等级（按累计消费划分） |
| `div_user_sign` | 签到记录 |
| `div_category` | 商品/文章栏目（顶级为导航） |
| `div_product` | 商品（标题、价格、规格、缩略图、详情、品牌、外链 …） |
| `div_brand` | 品牌 |
| `div_article` | 文章/资讯 |
| `div_page` | 单页（关于我们 / 配送说明 …） |
| `div_cart` | 购物车（未登录按 sessionid、登录按 userid） |
| `div_order` | 订单主表（状态、收货信息、支付、优惠） |
| `div_order_data` | 订单商品 |
| `div_payment` | 支付方式 |
| `div_shipping` | 配送方式 |
| `div_coupon` | 优惠券模板 |
| `div_slide` / `div_slide_data` | 轮播图（首页大图 `fid=1`） |
| `div_area` | 省市区 |
| `div_faq` | 常见问题 |
| `div_link` | 友情链接 |
| `div_feedback` | 商品评价 + 留言 |
| `div_message` / `div_message_detail` | 站内信 |

---

## 设计风格一览

- **毛玻璃 Header**：`backdrop-filter: saturate(180%) blur(20px)`，半透明白
- **SF 字体栈**：`-apple-system, BlinkMacSystemFont, "PingFang SC", "Microsoft YaHei", "Noto Sans JP" …`
- **圆角胶囊按钮**：`border-radius: 980px`（Apple pill）
- **主色**：Apple Blue `#0071E3`，强调红 `#FF3B30`，Apple Dark `#1D1D1F`
- **5 段步骤指示器**：购物车 → 收货 → 支付 → 完成
- **Toast**：原生 CSS 注入，毛玻璃黑底
- **响应式**：760px / 860px / 1024px 三档断点

---

## 推送 / 部署 Tips

- 部署到线上务必把 `App/Conf/config.php` 中 `APP_DEBUG` 改为 `false`，并删除 `App/Runtime` 下的缓存。
- 主题图存到 `Public/theme/upload/`，已配 Rewrite 跳过（直接访问即可）。
- 邮件发送用 PHPMailer（自行在 `Common/common.php` 扩展）。

---

## License

仅供学习与商业内部使用。
