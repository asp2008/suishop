# 安装 / 部署指南

## 1. 环境要求

- PHP 5.3 ~ 5.6（TP3.1.2 兼容性最佳；PHP 7.x 大部分功能可用）
- MySQL 5.5+ / MariaDB
- Apache + mod_rewrite 或 Nginx
- PHP 扩展：mbstring、gd（验证码用）、mysql、curl

## 2. 步骤

```bash
# 1) 上传项目到 web 根目录
unzip suishop.zip -d /var/www/html/

# 2) 修改数据库配置
vi /var/www/html/App/Conf/config.php
# 修改 DB_HOST / DB_NAME / DB_USER / DB_PWD

# 3) 导入数据
mysql -uroot -p your_db < /var/www/html/data/suishop.sql

# 4) 设置目录权限
chmod -R 755 /var/www/html/
chmod -R 777 /var/www/html/App/Runtime
chmod -R 777 /var/www/html/Public/theme/upload

# 5) 访问 http://你的域名/ 验证
```

## 3. Apache 配置（.htaccess 已内置）

```apache
RewriteEngine On
RewriteCond %{REQUEST_FILENAME} !-d
RewriteCond %{REQUEST_FILENAME} !-f
RewriteRule ^(.*)$ index.php/$1 [QSA,PT,L]
```

## 4. Nginx 配置示例

```nginx
server {
    listen 80;
    server_name yourdomain.com;
    root /var/www/html;
    index index.php;

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php-fpm.sock;
        fastcgi_index index.php;
        include fastcgi_params;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
    }
}
```

## 5. 演示数据

注册任意账号即送 500 积分，结算页可用 `KURA10`（95折）、`NEW5000`（减 5000） 体验优惠码逻辑。

## 6. 上线 checklist

- [ ] `App/Conf/config.php` 中 `APP_DEBUG = false`
- [ ] 修改默认管理员 / 后台入口（暂无后台，已加 TODO）
- [ ] 设置 `App/Runtime` 不可外访问
- [ ] 调整时区 `DEFAULT_TIMEZONE`
- [ ] 邮件 / 短信接口替换为真实通道
- [ ] 接入真实支付（支付宝 / 微信 / Stripe）
- [ ] 接入真实物流查询（顺丰 / ヤマト / 佐川）
