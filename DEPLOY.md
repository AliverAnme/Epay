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

> **代码持久化**：应用代码在镜像中存放于 `/var/www/html-staging/`，entrypoint 启动时通过 rsync 同步到 `app_data` 命名卷。`config.php` 自动生成并保留在卷中，插件通过 `./plugins` bind mount 直接映射。拉取新镜像即可完成代码更新，详见 [升级指南](#升级指南)。

### 1.2 架构

```
┌──────────────────────────────────────────────────┐
│  docker compose 四容器架构                         │
│                                                   │
│  ┌─────────┐   ┌──────────┐   ┌──────────┐       │
│  │  nginx  │──→│ php-fpm  │──→│  MySQL   │       │
│  │ :80     │   │          │   │ :3306    │       │
│  └─────────┘   └──────────┘   └──────────┘       │
│       ↑              ↑                             │
│       │              │                             │
│  ┌────┴──────┐       │                             │
│  │   cron    │───────┘                             │
│  │ (每5分钟) │   curl nginx/cron.php               │
│  └───────────┘                                     │
│                                                   │
│  代码更新机制:                                      │
│  镜像 /var/www/html-staging/ ─rsync→ app_data 卷    │
│  (排除 config.php, plugins, install.lock)           │
│                                                   │
│  Volumes:                                         │
│  app_data  → /var/www/html（运行时 + config.php）   │
│  ./plugins → /var/www/html/plugins（插件 bind mt）  │
│  uploads   → assets/uploads（支付凭证）             │
│  sessions  → PHP Session 文件                      │
│  mysql_data→ MySQL 数据文件                        │
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

# 代码更新（重建镜像后重启，entrypoint 自动同步代码）
git pull origin main
docker compose up -d --build

# 重建镜像（仅修改 Dockerfile 或 docker/ 内配置文件时需要）
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
vim .env  # 修改密码和 SITE_URL（所有变量必须填写，prod 文件无 fallback 默认值）
```

> **代码持久化**：与场景一相同，应用代码在 `app_data` 命名卷中，`config.php` 自动生成并持久化。插件通过 `./plugins` bind mount 管理。

### 2.2 启动

`docker-compose.prod.yml` 是**完整独立的** compose 文件，**不要**和 `docker-compose.yml` 合并使用（两者都定义了全部 service，合并会导致端口重复等冲突）。

```bash
docker compose -f docker-compose.prod.yml up -d
```

`docker-compose.prod.yml` 与 `docker-compose.yml` 的区别：

| 项目 | `docker-compose.yml`（本地构建） | `docker-compose.prod.yml`（预构建镜像） |
|------|------|------|
| PHP 镜像来源 | `build: .` 本地编译 | `image: ghcr.io/aliveranme/epay:latest` 拉取 |
| 环境变量默认值 | 有 fallback（如 `DB_PASSWORD:-epay123`） | 无 fallback，**`.env` 里必须全部填写** |
| CRON_KEY | 允许为空 `${CRON_KEY:-}` | 无默认值 → 首次启动前必须从日志获取并填入 |
| 数据库端口暴露 | 有 `DB_EXPOSE_PORT`（调试用） | 无（更安全） |
| nginx SSL 端口 | 有 443 映射 | 无

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

**安全响应头配置**：在 nginx `server` 块中添加以下头部，防止点击劫持、MIME嗅探等攻击：

```nginx
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;
add_header Permissions-Policy "camera=(), microphone=(), geolocation=()" always;
```

**敏感路径拦截**：确保以下路径不可通过 Web 访问：

```nginx
location ^~ /install { deny all; }
location ^~ /includes { deny all; }
location ^~ /plugins { deny all; }
location ~* /(\.git|\.env|config\.php) { deny all; }
```

**PHP 安全配置**（Docker 已默认配置，直接部署需手动设置 `php.ini`）：

```ini
display_errors = Off
expose_php = Off
allow_url_fopen = Off
allow_url_include = Off
disable_functions = exec,passthru,shell_exec,system,proc_open,popen
```

**强制 HTTPS**：建议在反向代理层（Nginx/Caddy）配置 HTTP→HTTPS 重定向，确保所有通信加密。

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

> **使用的 compose 文件**：`docker-compose.yml`（本地构建）。如需使用预构建镜像，改为 `docker-compose.prod.yml`，所有 `docker compose` 命令前加上 `-f docker-compose.prod.yml`。

编辑 `.env`：

```ini
# nginx 只监听本地，避免端口暴露到公网（关键！）
NGINX_PORT=127.0.0.1:8888

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

> ⚠️ **首次安装后必须立即修改管理员密码**。默认安装密码为 `admin123456`（手动安装）或 `.env` 中设置的值（Docker 安装）。

### 配置站点信息

**系统设置 → 站点配置**：

| 配置项 | 说明 | 示例 |
|--------|------|------|
| 站点名称 | 前台显示的网站名 | 某某支付平台 |
| 站点域名 | 填写 **HTTPS** 实际域名 | `https://pay.your-domain.com` |
| CDN 加速 | 无外网选本地 | 本地源 |

> ⚠️ **站点域名必须使用 HTTPS**。系统会自动检测协议，HTTP 访问会导致支付回调、API 签名等使用明文传输。

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

### Docker 插件管理

插件目录通过 bind mount 单独映射到宿主机 `./plugins/`（`./plugins:/var/www/html/plugins`），应用代码和配置文件在 `app_data` 命名卷中。**添加/删除插件不需要任何 Docker 命令**：

```bash
# 添加新插件（直接复制到宿主机 plugins 目录）
cp -r /path/to/new-plugin ./plugins/

# 删除插件
rm -rf ./plugins/unwanted-plugin/

# 插件立即生效，无需重启容器
```

### Docker 数据持久化

entrypoint 每次启动时从镜像 staging 目录同步代码到 `app_data` 卷（排除配置文件）：

- `app_data` 命名卷 — 应用代码 + `config.php` + `install/install.lock`，entrypoint 自动同步代码、生成配置
- `/var/www/html-staging/` (镜像内) — 代码源，每次拉取新镜像后自动同步到卷
- `./plugins/` bind mount — 插件文件，直接映射宿主机目录
- `assets/uploads/` — 上传文件（额外使用命名卷 `uploads`，双重保护）

> **Linux 用户注意**：容器内 PHP-FPM 以 `www-data`（UID 82）运行。如果遇到插件 cert 目录写入权限问题，在宿主机执行：
> ```bash
> chown -R 82:82 plugins/
> ```
> macOS / Windows Docker Desktop 无此问题。

### Docker 数据安全（重要）

理解各类数据的存储位置和持久化方式，才能在运维时避免数据丢失。

```
┌─────────────────────────────────────────────────────┐
│  数据类型               存储位置           持久化方式  │
├─────────────────────────────────────────────────────┤
│  商户/订单/配置          MySQL → mysql_data 命名卷 永久 │
│  数据库连接信息          app_data 卷 → config.php 永久  │
│  安装锁定标记            app_data 卷 → install.lock  永久 │
│  支付凭证上传            uploads 命名卷     永久       │
│  PHP Session             sessions 命名卷    可丢失     │
│  插件文件                ./plugins/         bind mount│
│  程序代码                app_data 命名卷    (自动同步) │
└─────────────────────────────────────────────────────┘
```

**核心原则**：所有业务数据（商户账号、支付订单、结算记录、系统配置）全部在 MySQL 数据库中，由 `mysql_data` 命名卷保护。`config.php` 在 `app_data` 卷中持久化。entrypoint 每次启动自动从镜像同步代码，拉取新镜像即可更新。

```bash
# ✅ 安全 — 数据全部保留
docker compose down
docker compose up -d

# ✅ 安全 — 数据全部保留
docker compose restart

# ✅ 安全 — 代码更新（重建镜像/拉取新镜像，自动同步）
git pull origin main && docker compose up -d --build

# ❌ 危险 — 删除所有数据（包括整个数据库！）
docker compose down -v
```

> **备份**：定期执行数据库备份，见下方运维管理。

### 直接部署场景（运维）

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
| 502 Bad Gateway（Docker） | PHP 容器未启动，或 config.php 未生成 | `docker compose ps` + `docker compose logs php`；检查 config.php 中 host 是否为 `mysql`（非 `localhost`） |
| 502 Bad Gateway（首次部署后） | entrypoint 未自动生成 config.php | 删除 `app_data` 卷重建：`docker compose down && docker volume rm epay_app_data && docker compose up -d` |
| 数据库连接失败 | 密码错误或容器间网络不通 | 检查 `.env` 密码；Docker 内主机名应为 `mysql` |
| 安装后页面空白 | syskey 丢失 | Docker：`docker compose down && docker compose up -d` 重试安装 |
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

应用代码在 `app_data` 命名卷中。entrypoint 会在每次容器启动时自动从镜像同步代码到该卷（排除 `config.php`、`plugins/`、`install.lock`），因此拉取新镜像后重启即可完成代码更新，无需手动管理卷。

```bash
# 本地构建模式 — 代码更新
git pull origin main
docker compose up -d --build                      # 重建镜像 + 重启，entrypoint 自动同步代码

# 预构建模式 — 代码更新（最简洁）
docker compose -f docker-compose.prod.yml pull    # 拉取新镜像
docker compose -f docker-compose.prod.yml up -d   # 重启，entrypoint 自动同步代码
```

> ⚙️ **工作原理**：镜像内代码存放在 `/var/www/html-staging/`（staging 目录），运行时工作目录为 `/var/www/html/`（`app_data` 卷）。entrypoint 启动时执行 `rsync --delete` 将 staging 同步到工作目录，但排除 `config.php`、`plugins/`、`install.lock`、`assets/uploads/`、`cache/`。因此：
> - `config.php` 和 `install.lock` 在卷中持久化，不受代码同步影响
> - `plugins/` 是 bind mount 覆盖，不受影响
> - `assets/uploads/` 是独立命名卷覆盖，不受影响
> - 删除旧文件、添加新文件均自动处理

> entrypoint.sh 会在每次启动时自动检测并执行数据库升级 SQL。

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
# 安全响应头
add_header X-Frame-Options "SAMEORIGIN" always;
add_header X-Content-Type-Options "nosniff" always;
add_header Referrer-Policy "strict-origin-when-cross-origin" always;

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
location ~* /(config\.php|\.htaccess|\.git|\.env) {
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
