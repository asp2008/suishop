# 蔵市 KURA-ICHI / suishop

> 日本各地の蔵元・窯元・職人とつながる、Apple Design Language 风格的多商户电商前台。
> ThinkPHP 3.1.2 + jQuery + 苹果风 CSS/JS。

## 部署

1. 上传所有文件到 PHP (5.3 ~ 5.6) 主机
2. 修改 `Conf/config.php` 数据库配置
3. 导入 `data/suishop.sql` 到 MySQL
4. 浏览器访问 `http://你的域名/`

## 项目结构

```
Home/                                # 前台模块
├── BaseAction.class.php             # 公用基类（继承自 ThinkPHP Action）
├── IndexAction.class.php            # 入口（demo）
├── ProductAction.class.php          # 商品列表 / 详情
├── CartAction.class.php             # 购物车 / 结算 / 订单（保留原逻辑 + 新增 update/remove/coupon/pay/donePage/cancel/confirm）
├── AccountAction.class.php          # 登录 / 注册 / 退出 / 改密 / 重置
├── AjaxAction.class.php             # 验证码 / 收藏 / 留言 / 订阅 / 搜索建议 / 点赞（保留原逻辑 + 新增 verify/like/suggest/subscribe/feedback/toggleFav/checkVerify）
├── MyAction.class.php               # 会员中心 / 收藏 / 消息 / 资料 / 品牌 / 文章 / 搜索 / 反馈 / 退出（综合前台）
├── ApiAction.class.php              # 原有 API
├── SearchAction.class.php           # 原有搜索
├── BrandAction.class.php            # 原有品牌
├── UserAction.class.php             # 原有用户
├── EmptyAction.class.php            # 原有空操作
└── Default/                         # 模板
    ├── Home_head.html               # head（已升级 - 引用 apple.css + dialog.css）
    ├── Home_header.html             # 顶部（已升级 - 苹果风毛玻璃）
    ├── Home_footer.html             # 底部（已升级 - 加载 jQuery + dialog.js + main.js）
    ├── Home_bread.html / Home_left.html / Home_right.html / ...  # 公用片段
    ├── Index_index.html             # 首页
    ├── Product_list.html / Product_show.html / Product_brand.html
    ├── Cart_index.html / Cart_checkout.html / Cart_done.html / Cart_donePage.html（新 - Apple 风订单完成）/ Cart_goods.html / Cart_login.html / Cart_unlogin.html / Cart_show.html / Cart_coupon.html
    ├── User_index.html / User_orders.html / User_order_detail.html（新）/ User_login.html / User_register.html / User_reset.html / User_password.html / User_profile.html（新）/ User_logout.html（新）/ User_favorite.html / User_address.html / User_point.html / User_message.html（新）/ User_message_detail.html（新）/ User_qa.html / User_detail.html / User_review.html / User_coupon.html / User_unregister.html / User_updater.html / User_aside.html（已升级）/ User_menu.html / User_newaddress.html / User_editpas.html
    ├── Brand_list.html
    ├── Page_index.html / Page_qas.html / Page_hot.html / Page_new.html / Page_sale.html / Page_special.html / Page_search.html / Page_contact.html / Page_sitemap.html / Page_reply.html / Page_movie.html
    ├── News_list.html / News_show.html
    ├── Sagawas_list.html / Sagawas_show.html
    ├── Talent_list.html / Talent_show.html
    ├── Topics_list.html / Topics_show.html
    ├── Protype_list.html
    ├── Search_index.html
    ├── Slide_1.html / Slide_2.html
    ├── Sitemap.html
    ├── Words_list.html / Words_show.html
    ├── Lottery_list.html
    └── Public/
        ├── css/
        │   ├── apple.css          # 主样式（苹果风 - 来自最新 css 包）
        │   ├── dialog.css         # 对话框 / Toast 组件样式
        │   ├── mypage-sub.css     # 会员中心子页专用样式（按需加载）
        │   ├── style.css          # 原项目旧样式（保留）
        │   ├── jquery.jqzoom.css  # 原项目图片放大样式
        │   ├── suni_logo-04.png / suni_logo-041.png / suni_logo_bessey_style-04.png
        │   ├── hp.jpg / hp2.jpg / nike-just-do-it-nike-com-jp.jpg / nike-just-do-it-nike-com-jp1.jpg  # Hero 轮播图
        │   └── AIR+JORDAN+*.jpg / JORDAN+TRIANGLE+PF.jpg  # 列表占位图
        ├── js/
        │   ├── jquery-3.7.1.min.js  # jQuery（本地，替代 CDN）
        │   ├── main.js              # 主交互（收藏 / 轮播 / 灯箱 / 数量加减 / Toast）
        │   ├── dialog.js            # 对话框 / Toast 组件脚本
        │   ├── jquery.jqzoom-core.js  # 原项目放大脚本
        │   └── yourphp.pics.js      # 原项目脚本
        ├── images/                  # 原项目老图片
        ├── flash/                   # 原项目 flash
        └── xml/                     # 原项目 XML
```

## 主要修改说明

### 1. 样式系统（核心 - 苹果风升级）

- `apple.css` - 主样式（来自最新 css 包）
- `dialog.css` - 对话框组件
- `mypage-sub.css` - 会员中心子页专用
- 全部图片（hero 轮播 / logo / 列表占位）已就位

### 2. Home_footer.html / Home_head.html

- 自动加载 jQuery + main.js + dialog.js
- 引用 apple.css + dialog.css

### 3. 4 个核心 Action 的增强

- **MyAction**：综合前台（dashboard / favorite / point / address / message / profile / brand / article / search / feedback / logout）
- **AccountAction**：新增 logout / doLogout / password / reset / exit_login
- **CartAction**：保留原有 index/checkout/address/done（POST 提交）/clear/getnum，新增 update/remove/coupon/pay/donePage（Apple 风订单完成页）/cancel/confirm
- **AjaxAction**：保留原有 area/address/shipping/sms/dianjis/collect/.../getCounts，新增 verify/like/suggest/subscribe/feedback/toggleFav/checkVerify

### 4. 新增模板

- `User_order_detail.html`（订单详情 - Apple 风）
- `User_message.html` / `User_message_detail.html`（站内信）
- `User_profile.html`（资料修改）
- `User_logout.html`（退出确认）
- `Cart_donePage.html`（Apple 风订单完成页 - 替代老 Cart_done 的视觉）

## URL 入口

| 页面 | URL |
|------|-----|
| 首页 | `?m=Index&a=index` |
| 商品列表 | `?m=Product&a=list` |
| 商品详情 | `?m=Product&a=show&id=` |
| 品牌列表 | `?m=My&a=brand` |
| 会员中心 | `?m=My&a=dashboard` |
| 订单 | `?m=My&a=orders` |
| 收藏 | `?m=My&a=favorite` |
| 登录 | `?m=Account&a=login` |
| 购物车 | `?m=Cart&a=index` |
| 结算 | `?m=Cart&a=checkout` |
| 订单提交 | `?m=Cart&a=done` (POST) |
| 订单完成 | `?m=Cart&a=donePage&sn=` |
| 模拟支付 | `?m=Cart&a=pay&sn=` |
| 验证码 | `?m=Ajax&a=verify` |
| 搜索建议 | `?m=Ajax&a=suggest&q=` |

## 演示账号

注册任意账号即得 500 积分。
优惠码（结算页用）：`KURA10`（95折）、`NEW5000`（减 5000）。
