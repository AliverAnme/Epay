# 彩虹易支付部署全指南

## 目录

1. [部署方式速查](#部署方式速查)
2. [环境要求](#环境要求)
3. [场景一：Docker Compose（本地构建）](#场景一docker-compose本地构建)
4. [场景二：Docker Compose（预构建镜像）](#场景二docker-compose预构建镜像)
5. [场景三：传统 LAMP/LEMP 直接部署](#场景三传统-lamplemp-直接部署)
6. [场景四：1Panel 反向代理 + Docker](#场景四1panel-反向代理--docker)
7. [场景五：1Panel 直接 PHP 托管](#场景五1panel-直接-php-托管)
8. [首次配置指南](#首次配置指南)
9. [运维管理](#运维管理)
10. [故障排查](#故障排查)
11. [升级指南](#升级指南)

---

## 部署方式速查

| 场景 | 适用人群 | 依赖 | 复杂度 |
|------|---------|------|--------|
| Docker Compose（本地构建） | 有 Docker 的服务器 | Docker + Compose | ⭐ |
| Docker Compose（预构建镜像） | 不想本地编译 | Docker + Compose | ⭐ |
| 传统 LAMP/LEMP | 虚拟主机/云服务器 | PHP 7.4+ MySQL 5.6+ | ⭐⭐ |
| 1Panel 反向代理 + Docker | 已装 1Panel 的服务器 | 1Panel + Docker | ⭐ |
| 1Panel 直接 PHP 托管 | 已装 1Panel，不想用 Docker | 1Panel（OpenResty+PHP+MySQL） | ⭐⭐ |

---

## 环境要求

| 软件 | 版本 | 说明 |
|------|------|------|
| PHP | >= 7.4 | 需扩展：pdo_mysql, curl, mbstring, gd, gmp, bcmath, simplexml, sodium |
| MySQL / MariaDB | >= 5.6 | 必须使用 `utf8mb4`，`sql_mode` 必须为空 |
| Nginx / Apache | 最新稳定版 | 需配置 URL 重写（见对应场景） |
| Docker | >= 20.10 | 仅 Docker 方案需要 |
| Docker Compose | >= 2.0 | 仅 Docker 方案需要 |

---

## 场景一：Docker Compose（本地构建）

项目自带 `Dockerfile` 从源码构建 PHP-FPM 镜像，适合想完全掌控镜像内容的场景。

### 1.1 快速启动

```bash
# 克隆项目
git clone https://github.com/AliverAnme/Epay.git epay
cd epay

# 创建环境配置
cp .env.example .env

# 编辑 .env，至少修改：
#   MYSQL_ROOT_PASSWORD   — MySQL root 密码
#   DB_PASSWORD           — 数据库密码
#   ADMIN_PASSWORD        — 初始管理员密码
#   SITE_URL              — 站点完整 URL（生产环境改为实际域名）
vim .env

# 构建并启动（首次约 2-3 分钟）
docker compose up -d

# 查看启动日志，获取 CRON_KEY
docker compose logs php | grep CRON_KEY

# 将 CRON_KEY 填回 .env，然后重启 cron 容器
vim .env  # 填入 CRON_KEY=xxxx
docker compose up -d cron
```

### 1.2 架构

```
┌──────────────────────────────────────────────────┐
│  docker compose 四容器架构                         │
│                                                   │
│  ┌─────────┐   ┌──────────┐   ┌──────────┐       │
│  │  nginx  │──→│ php-fpm  │──→│  MySQL   │       │
│  │ :80     │   │ (构建)   │   │ :3306    │       │
│  └─────────┘   └──────────┘   └──────────┘       │
│       ↑              ↑                             │
│       │              │                             │
│  ┌────┴──────┐       │                             │
│  │   cron    │───────┘                             │
│  │ (每5分钟) │   curl nginx/cron.php               │
│  └───────────┘                                     │
└──────────────────────────────────────────────────┘
```

### 1.3 环境变量参考

```ini
# Nginx 端口
NGINX_PORT=80
# NGINX_SSL_PORT=443

# 数据库
DB_HOST=mysql
DB_PORT=3306
DB_USER=epay
DB_PASSWORD=epay123
DB_NAME=epay
DB_PREFIX=pay
MYSQL_ROOT_PASSWORD=root123

# 站点
SITE_URL=http://localhost
# SITE_URL=https://pay.your-domain.com

# 管理员初始密码（安装后请立即修改）
ADMIN_PASSWORD=admin123

# Cron 密钥（首次启动后从日志获取并填回）
CRON_KEY=

# 数据库端口暴露到宿主机（调试用，生产建议注释掉）
# DB_EXPOSE_PORT=33060
```

### 1.4 常用命令

```bash
# 启动/停止
docker compose up -d
docker compose down

# 查看状态
docker compose ps

# 查看日志
docker compose logs -f --tail=100

# 重启某服务
docker compose restart nginx

# 重建镜像（修改 Dockerfile 后）
docker compose up -d --build
```

### 1.5 反向代理（用 Nginx/Caddy/1Panel 在宿主机代理）

如果宿主机已有 Web 服务器，将 Docker nginx 只监听本地：

```ini
# .env
NGINX_PORT=127.0.0.1:8888
```

修改 `docker/nginx.conf`，在 `server` 块添加反向代理信任：

```nginx
set_real_ip_from 172.16.0.0/12;    # Docker 内网
set_real_ip_from 127.0.0.1;
real_ip_header X-Forwarded-For;
real_ip_recursive on;
```

然后重启 nginx：`docker compose restart nginx`（**不需重新构建镜像**，配置通过 volume 挂载）。

---

## 场景二：Docker Compose（预构建镜像）

使用 GitHub Container Registry 的预构建镜像 `ghcr.io/aliveranme/epay:latest`，无需本地编译 PHP 扩展，启动更快。

### 2.1 准备

```bash
git clone https://github.com/AliverAnme/Epay.git epay
cd epay
cp .env.example .env
vim .env  # 修改密码和 SITE_URL
```

### 2.2 启动

```bash
# 使用 prod 配置（跳过构建，直接拉取镜像）
docker compose -f docker-compose.yml -f docker-compose.prod.yml up -d
```

或直接用 prod 文件：

```bash
docker compose -f docker-compose.prod.yml up -d
```

`docker-compose.prod.yml` 与 `docker-compose.yml` 的区别：

| 项目 | 本地构建 | 预构建镜像 |
|------|---------|-----------|
| PHP 镜像来源 | `build: .` 本地编译 | `image: ghcr.io/aliveranme/epay:latest` 拉取 |
| 数据库端口暴露 | 有 `DB_EXPOSE_PORT` | 无（更安全） |
| nginx SSL 端口 | 有 443 映射 | 无 |

### 2.3 后续步骤

```bash
# 获取 CRON_KEY
docker compose -f docker-compose.prod.yml logs php | grep CRON_KEY

# 填入 .env，重启 cron
vim .env
docker compose -f docker-compose.prod.yml up -d cron
```

---

## 场景三：传统 LAMP/LEMP 直接部署

适用于云服务器、虚拟主机等已安装 PHP + MySQL 的环境。

### 3.1 上传代码

将所有文件上传到网站根目录（如 `/www/wwwroot/pay.your-domain.com/`）。

### 3.2 配置 `config.php`

```php
<?php
$dbconfig = [
    'host'      => 'localhost',     // 数据库地址
    'port'      => 3306,
    'user'      => 'epay',          // 数据库用户名
    'pwd'       => 'your_password', // 数据库密码
    'dbname'    => 'epay',          // 数据库名
    'dbqz'      => 'pay_'           // 表前缀 pre_pay_xxx
];
```

### 3.3 创建数据库

```sql
CREATE DATABASE epay DEFAULT CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
CREATE USER 'epay'@'localhost' IDENTIFIED BY 'your_password';
GRANT ALL PRIVILEGES ON epay.* TO 'epay'@'localhost';
FLUSH PRIVILEGES;
```

**重要**：MySQL `sql_mode` 必须设为空。在 `my.cnf` 添加：

```ini
[mysqld]
sql_mode=
```

或在已有的 `sql_mode` 配置中去掉 `ONLY_FULL_GROUP_BY`。

### 3.4 安装 PHP 扩展

```bash
# Ubuntu/Debian
apt install php-gd php-mbstring php-curl php-gmp php-bcmath php-xml php-mysql

# CentOS/Rocky
yum install php-gd php-mbstring php-curl php-gmp php-bcmath php-xml php-mysqlnd

# 或使用宝塔/1Panel 面板：PHP 设置 → 安装扩展
# 勾选：gd, mbstring, curl, gmp, bcmath, simplexml, pdo_mysql
```

### 3.5 配置 URL 重写

项目有 **4 条必须的重写规则**（来自 `nginx.txt`）：

#### Nginx

在网站配置的 `server` 块中添加：

```nginx
location / {
    if (!-e $request_filename) {
        rewrite ^/(.[a-zA-Z0-9\-\_]+).html$ /index.php?mod=$1 last;
    }
    rewrite ^/pay/(.*)$ /pay.php?s=$1 last;
    rewrite ^/api/(.*)$ /api.php?s=$1 last;
    rewrite ^/doc/(.[a-zA-Z0-9\-\_]+).html$ /index.php?doc=$1 last;
}
location ^~ /includes { deny all; return 403; }
location ^~ /plugins { deny all; return 403; }
```

#### Apache

在网站根目录创建 `.htaccess`：

```apache
RewriteEngine On

# *.html → index.php?mod=xxx
RewriteCond %{REQUEST_FILENAME} !-f
RewriteCond %{REQUEST_FILENAME} !-d
RewriteRule ^([a-zA-Z0-9\-_]+)\.html$ index.php?mod=$1 [L,QSA]

# /pay/xxx → pay.php?s=xxx
RewriteRule ^pay/(.*)$ pay.php?s=$1 [L,QSA]

# /api/xxx → api.php?s=xxx
RewriteRule ^api/(.*)$ api.php?s=$1 [L,QSA]

# /doc/xxx.html → index.php?doc=xxx
RewriteRule ^doc/([a-zA-Z0-9\-_]+)\.html$ index.php?doc=$1 [L,QSA]

# 安全规则
RedirectMatch 403 ^/(includes|plugins)/
```

### 3.6 安装与完成

访问 `https://your-domain.com/install/` 完成 Web 安装向导，完成后 **删除 `/install/` 目录**。

### 3.7 配置 Cron 定时任务

```bash
# 编辑 crontab
crontab -e

# 添加（每 5 分钟执行一次结算、对账、回调重试等）
*/5 * * * * curl -s -o /dev/null "https://your-domain.com/cron.php?key=YOUR_CRON_KEY"
```

> Cron Key 在后台 **系统设置 → 安全设置 → 监控密钥** 中获取。

### 3.8 生产环境加固

```bash
# 设置文件权限
chmod 640 config.php
chmod -R 755 assets/uploads plugins install admin
chown -R www-data:www-data assets/uploads
```

---

## 场景四：1Panel 反向代理 + Docker

**前提**：服务器已安装 1Panel 面板，1Panel 负责 SSL/域名管理，Epay 以 Docker Compose 运行在后台。

### 架构

```
用户 (HTTPS) → 1Panel OpenResty (SSL 终止)
                 │  X-Forwarded-Proto: https
                 │  X-Forwarded-For: 真实IP
                 ↓
               127.0.0.1:8888 → Docker nginx → PHP-FPM → MySQL
```

### 4.1 Docker 侧配置

编辑 `.env`：

```ini
# nginx 只监听本地，端口避免冲突
NGINX_PORT=8888

# 站点 URL 设为最终公网地址（必须 HTTPS）
SITE_URL=https://pay.your-domain.com

# 数据库密码务必修改
MYSQL_ROOT_PASSWORD=<32位随机密码>
DB_PASSWORD=<32位随机密码>
ADMIN_PASSWORD=<16位随机密码>

# 不暴露数据库到公网
# DB_EXPOSE_PORT=33060
```

编辑 `docker/nginx.conf`，在 `server` 块开头添加反向代理信任：

```nginx
server {
    listen 80;
    server_name _;

    # 信任 1Panel 反向代理头（关键！）
    set_real_ip_from 127.0.0.1;
    real_ip_header X-Forwarded-For;
    real_ip_recursive on;

    # ... 其余配置保持不变
}
```

启动：

```bash
docker compose up -d
docker compose logs php | grep CRON_KEY   # 获取并填回 .env
vim .env
docker compose up -d cron
```

### 4.2 1Panel 侧配置

1. 进入 **网站 → 创建网站 → 反向代理**
2. 填写域名（如 `pay.your-domain.com`），开启 **HTTPS**（1Panel 自动申请证书）
3. 源地址填：`http://127.0.0.1:8888`
4. 在「**代理设置**」确认以下头已透传（1Panel 默认会传）：

   ```
   Host: $host
   X-Forwarded-Proto: $scheme
   X-Forwarded-For: $remote_addr
   X-Real-IP: $remote_addr
   Referer: $http_referer
   ```

5. 如有 CDN，开启「从 CDN 获取真实 IP」

> **关键**：必须透传 `X-Forwarded-Proto` 头，否则 Epay 的 `is_https()` 检测会失败，生成的支付链接会变成 `http://`。

### 4.3 为什么不需重新构建镜像

nginx 使用官方镜像，配置文件通过 `docker-compose.yml` 的 `volumes` 挂载：

```yaml
nginx:
    image: nginx:alpine          # 官方镜像，不是自定义构建
    volumes:
      - ./docker/nginx.conf:/etc/nginx/conf.d/default.conf:ro
```

修改配置后只需 `docker compose restart nginx`，**从不需重新构建**。

---

## 场景五：1Panel 直接 PHP 托管

不使用 Docker，完全由 1Panel 管理 PHP 运行时和 MySQL。

### 5.1 创建网站

1Panel → **网站 → 创建网站 → 运行环境**。

关键设置：

| 配置项 | 值 |
|--------|-----|
| 域名 | `pay.your-domain.com` |
| PHP 版本 | 7.4（或 8.0/8.1） |
| 数据库 | 勾选「创建数据库」，编码 `utf8mb4` |
| HTTPS | 开启（1Panel 自动申请 Let's Encrypt） |

### 5.2 安装 PHP 扩展

进 **应用商店 → PHP → 设置 → 扩展**，安装：

```
pdo_mysql, gd, mbstring, curl, gmp, bcmath, simplexml, sodium
```

### 5.3 上传代码与配置

将项目文件上传到 `/www/wwwroot/pay.your-domain.com/`（排除 `.env`、`docker/`、`docker-compose.yml`、`Dockerfile`）。

编辑 `config.php` 填入数据库信息（数据库名/用户/密码见 1Panel 数据库管理）：

```php
<?php
$dbconfig = [
    'host'      => 'localhost',
    'port'      => 3306,
    'user'      => 'epay',             // 1Panel 创建的数据库用户名
    'pwd'       => 'your_password',    // 1Panel 创建的数据库密码
    'dbname'    => 'epay',             // 1Panel 创建的数据库名
    'dbqz'      => 'pay_'
];
```

### 5.4 配置伪静态（重要）

1Panel → **网站 → 选择域名 → 伪静态**，粘贴：

```nginx
location / {
    if (!-e $request_filename) {
        rewrite ^/(.[a-zA-Z0-9\-\_]+).html$ /index.php?mod=$1 last;
    }
    rewrite ^/pay/(.*)$ /pay.php?s=$1 last;
    rewrite ^/api/(.*)$ /api.php?s=$1 last;
    rewrite ^/doc/(.[a-zA-Z0-9\-\_]+).html$ /index.php?doc=$1 last;
}
location ^~ /includes { deny all; return 403; }
location ^~ /plugins { deny all; return 403; }
location ^~ /install { deny all; return 403; }
```

### 5.5 修改 MySQL sql_mode

1Panel 默认 MySQL sql_mode 含 `ONLY_FULL_GROUP_BY`，与项目不兼容。

**修复方法**：1Panel → **数据库 → MySQL → 配置**，找到 `sql_mode` 行，将值清空或注释掉，重启 MySQL。

或通过 SQL 临时设置（重启后失效，建议改配置文件）：

```sql
SET GLOBAL sql_mode='';
```

### 5.6 完成安装

1. 暂时移除 `/install` 的 deny 规则（或直接访问 `https://your-domain.com/install/` 走 Web 安装）
2. 安装完成后重新加上 `location ^~ /install { deny all; return 403; }`
3. 后台 **系统设置 → 站点配置 → 站点域名** 填 `https://pay.your-domain.com`

### 5.7 配置 Cron

1Panel → **计划任务 → 创建**：

| 配置项 | 值 |
|--------|-----|
| 类型 | shell |
| 名称 | Epay 定时任务 |
| 周期 | 每 5 分钟 |
| 命令 | `curl -s -o /dev/null "https://pay.your-domain.com/cron.php?key=YOUR_CRON_KEY"` |

> Cron Key 在后台 **系统设置 → 安全设置 → 监控密钥** 获取。

---

## 首次配置指南

所有场景部署完成后，登录后台进行初始配置：

### 修改管理员密码

访问 `https://your-domain.com/admin/`，用账号 `admin` 和初始密码（`.env` 中 `ADMIN_PASSWORD` 或安装时设置的密码）登录，进入 **系统设置 → 修改密码**。

### 配置站点信息

**系统设置 → 站点配置**：

| 配置项 | 说明 | 示例 |
|--------|------|------|
| 站点名称 | 前台显示的网站名 | 某某支付平台 |
| 站点域名 | 填写实际 HTTPS 域名 | `https://pay.your-domain.com` |
| CDN 加速 | 无外网选本地 | 本地源 |

### 配置支付渠道

**支付管理 → 支付插件** → 选择需要的通道 → 填写商户号/密钥 → 开启。

推荐 USDT 收款使用 [Bepusdt 插件](https://github.com/v03413/bepusdt)，资金直入商户钱包。

### 添加商户

**商户管理 → 商户列表** → 添加商户 → 自动生成商户 ID 和 API 密钥 → 设置费率、结算方式。

### 设置 Cron 密钥

**系统设置 → 安全设置 → 监控密钥**，填入 Cron 配置中使用的 Key。

---

## 运维管理

### Docker 场景

```bash
# 备份数据库
docker compose exec mysql mysqldump -u epay -p epay > backup_$(date +%Y%m%d).sql

# 备份上传文件
docker compose cp php:/var/www/html/assets/uploads ./backup_uploads/

# 查看实时日志
docker compose logs -f --tail=100

# 进入容器调试
docker compose exec php sh
```

### 直接部署场景

```bash
# 备份数据库
mysqldump -u epay -p epay > backup_$(date +%Y%m%d).sql

# 备份上传文件
cp -r /www/wwwroot/pay.your-domain.com/assets/uploads ./backup_uploads/
```

---

## 故障排查

| 症状 | 可能原因 | 解决方案 |
|------|---------|----------|
| 首页 404 / `.html` 页面打不开 | URL 重写未生效 | 检查伪静态配置是否加载 |
| `/pay/` 路由 404 | 重写规则缺失 | 检查 `/pay/(.*)` rewrite |
| 支付回调 403 / Referer 错误 | 反向代理未透传 Referer | 确保代理透传 `Referer` 头 |
| 支付链接生成 `http://` | `is_https()` 检测失败 | 确保代理透传 `X-Forwarded-Proto` |
| 后台页面空白 | PHP 扩展缺失或 `sql_mode` 问题 | 安装扩展 + 清空 `sql_mode` |
| 502 Bad Gateway（Docker） | PHP 容器未启动 | `docker compose ps` + `docker compose logs php` |
| 数据库连接失败 | 密码错误或容器间网络不通 | 检查 `.env` 密码；Docker 内主机名应为 `mysql` |
| 安装后页面空白 | syskey 丢失 | Docker：`docker compose down -v && docker compose up -d` |
| Cron 不执行 | CRON_KEY 未配置 | 后台获取 Key 并填入配置 |
| 上传文件过大 | `client_max_body_size` 默认太小 | 设为 `50m` |

### 通用诊断命令

```bash
# Docker 场景
docker compose ps                  # 容器状态
docker compose logs php --tail=50  # PHP 日志
docker compose exec php php -m     # PHP 已装扩展

# 直接部署
php -m | grep -E "pdo|curl|gd|gmp|mbstring|sodium"  # 扩展检查
```

---

## 升级指南

### Docker 场景

```bash
git pull origin main
docker compose up -d --build       # 本地构建模式
# 或
docker compose -f docker-compose.prod.yml pull   # 预构建模式
docker compose -f docker-compose.prod.yml up -d
```

### 直接部署场景

1. 上传新文件覆盖旧文件（**不要覆盖 `config.php`**）
2. 访问 `https://your-domain.com/install/update.php` 执行数据库升级
3. 完成后删除 `/install/` 目录

---

## 附录 A：关键配置模板

### Docker nginx 反向代理版 (`docker/nginx.conf`)

在标准配置的 `server` 块开头插入：

```nginx
server {
    listen 80;
    server_name _;

    # 信任反向代理（1Panel / Nginx / Caddy）
    set_real_ip_from 127.0.0.1;
    set_real_ip_from 172.16.0.0/12;   # Docker 内网
    real_ip_header X-Forwarded-For;
    real_ip_recursive on;

    # ... 后续配置保持不变
}
```

### 1Panel 伪静态完整配置

```nginx
# URL 重写
location / {
    if (!-e $request_filename) {
        rewrite ^/(.[a-zA-Z0-9\-\_]+).html$ /index.php?mod=$1 last;
    }
    rewrite ^/pay/(.*)$ /pay.php?s=$1 last;
    rewrite ^/api/(.*)$ /api.php?s=$1 last;
    rewrite ^/doc/(.[a-zA-Z0-9\-\_]+).html$ /index.php?doc=$1 last;
}

# 安全规则
location ^~ /includes { deny all; return 403; }
location ^~ /plugins { deny all; return 403; }
location ^~ /install { deny all; return 403; }

# 保护敏感文件
location ~* /(config\.php|\.htaccess|\.git) {
    deny all;
    return 403;
}

# 静态资源缓存
location ~* \.(jpg|jpeg|png|gif|ico|css|js|svg|woff|woff2|ttf|eot)$ {
    expires 30d;
    add_header Cache-Control "public, immutable";
}

# 上传目录禁止执行 PHP
location /assets/uploads/ {
    location ~* \.php$ {
        deny all;
        return 403;
    }
}
```

## 附录 B：部署方式与功能矩阵

| 功能 | Docker 本地构建 | Docker 预构建 | 直接部署 | 1Panel+反向代理 | 1Panel 直接托管 |
|------|:---:|:---:|:---:|:---:|:---:|
| 一键启动 | ✅ | ✅ | ❌ | ✅ | ❌ |
| 环境隔离 | ✅ | ✅ | ❌ | ✅ | ❌ |
| SSL 自动管理 | ❌ | ❌ | ❌ | ✅ | ✅ |
| PHP 扩展自动安装 | ✅ | ✅ | ❌ | ✅ | ❌ |
| 面板可视化 | ❌ | ❌ | ❌ | ✅ | ✅ |
| 适合虚拟主机 | ❌ | ❌ | ✅ | ❌ | ❌ |
| 多站点共存 | ✅ | ✅ | ✅ | ✅ | ✅ |

> **推荐**：有 Docker 经验选场景一/二；已装 1Panel 的服务器选场景四；虚拟主机/轻量云服务器选场景三或五。
