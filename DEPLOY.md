# 彩虹易支付 Docker 部署教程

## 目录

1. [环境要求](#1-环境要求)
2. [快速开始](#2-快速开始)
3. [配置详解](#3-配置详解)
4. [启动与验证](#4-启动与验证)
5. [首次配置指南](#5-首次配置指南)
6. [运维管理](#6-运维管理)
7. [生产环境加固](#7-生产环境加固)
8. [故障排查](#8-故障排查)
9. [升级指南](#9-升级指南)

---

## 1. 环境要求

| 软件 | 版本 | 说明 |
|------|------|------|
| Docker | >= 20.10 | [安装文档](https://docs.docker.com/engine/install/) |
| Docker Compose | >= 2.0 | 随 Docker Desktop 一同安装，Linux 需[单独安装](https://docs.docker.com/compose/install/) |
| 内存 | >= 2GB | MySQL 8.0 最低需求 |
| 磁盘 | >= 10GB | 含 MySQL 数据卷 |

**验证安装：**

```bash
docker --version
# Docker version 24.0.x

docker compose version
# Docker Compose version v2.x
```

---

## 2. 快速开始

### 2.1 获取项目

```bash
git clone https://github.com/AliverAnme/Epay.git epay
cd epay
```

### 2.2 配置环境变量

```bash
cp .env.example .env
```

编辑 `.env`，**至少修改以下三项**：

```ini
MYSQL_ROOT_PASSWORD=your-strong-root-password
DB_PASSWORD=your-strong-db-password
ADMIN_PASSWORD=your-strong-admin-password
```

### 2.3 构建并启动

```bash
docker compose up -d
```

首次启动会自动：
- 构建 PHP 镜像（约 2-3 分钟）
- 拉取 nginx、MySQL、Alpine 镜像
- 等待 MySQL 就绪
- 自动创建数据表
- 生成加密密钥
- 设置管理员密码

### 2.4 查看启动日志

```bash
docker compose logs php | tail -30
```

成功输出示例：

```
============================================
  彩虹易支付系统 - Docker 启动中...
============================================
[entrypoint] 生成 config.php ...
[entrypoint] config.php 已生成
[entrypoint] 等待 MySQL 连接 mysql:3306 ...
[entrypoint] MySQL 连接成功
[entrypoint] 首次安装，执行数据库初始化 ...
[entrypoint] 安装完成! 成功: 65 条, 失败: 0 条
[entrypoint] SYS_KEY: a8xk3m...（32位随机字符串）
[entrypoint] CRON_KEY: 483729
[entrypoint] 管理员密码: your-strong-password
[entrypoint] 请保存以上信息，并尽快修改管理员密码！
[entrypoint] install.lock 已创建
============================================
  启动 PHP-FPM ...
============================================
```

### 2.5 获取 Cron 密钥

```bash
docker compose logs php | grep CRON_KEY
```

将输出的 **CRON_KEY** 值填入 `.env` 文件：

```ini
CRON_KEY=483729
```

然后重启 cron 容器：

```bash
docker compose up -d cron
```

### 2.6 访问系统

| 入口 | 地址 | 默认账号 |
|------|------|----------|
| 前台首页 | `http://localhost` | - |
| 商户后台 | `http://localhost/user/` | 注册后获取 |
| 平台管理 | `http://localhost/admin/` | `admin` / `.env`中设置的密码 |

---

## 3. 配置详解

### 3.1 .env 全部变量

```ini
# ========== Nginx 端口 ==========
NGINX_PORT=80                  # HTTP 端口
# NGINX_SSL_PORT=443           # HTTPS 端口（配置 SSL 后启用）

# ========== 数据库 ==========
DB_HOST=mysql                  # 数据库主机名（Docker 内部使用，勿改）
DB_PORT=3306                   # 数据库端口
DB_USER=epay                   # 数据库用户名
DB_PASSWORD=epay123            # 数据库密码 ⚠️ 务必修改
DB_NAME=epay                   # 数据库名
DB_PREFIX=pay                  # 表前缀
MYSQL_ROOT_PASSWORD=root123    # MySQL Root 密码 ⚠️ 务必修改
DB_EXPOSE_PORT=33060           # 暴露到宿主机的数据库端口（调试用）

# ========== 站点 ==========
SITE_URL=http://localhost      # 站点完整 URL（生产环境改为实际域名）
# SITE_URL=https://pay.your-domain.com

# ========== 管理员 ==========
ADMIN_PASSWORD=admin123        # 初始管理员密码 ⚠️ 务必修改

# ========== Cron ==========
CRON_KEY=                      # 首次启动后从日志获取并填回此处
```

### 3.2 docker-compose 架构

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
│                                                   │
│  Volumes:                                         │
│  app_data  → /var/www/html (代码+配置)             │
│  uploads   → assets/uploads (支付凭证)             │
│  sessions  → PHP Session 文件                      │
│  mysql_data→ MySQL 数据文件                        │
└──────────────────────────────────────────────────┘
```

### 3.3 端口映射

| 容器内端口 | 宿主机端口 | 用途 | 修改方式 |
|-----------|-----------|------|----------|
| nginx:80 | `${NGINX_PORT:-80}` | Web 服务 | `.env` 中 `NGINX_PORT` |
| mysql:3306 | `${DB_EXPOSE_PORT:-33060}` | 数据库（调试） | `.env` 中 `DB_EXPOSE_PORT`，生产建议注释掉 |

---

## 4. 启动与验证

### 4.1 基础命令

```bash
# 启动所有服务
docker compose up -d

# 查看运行状态
docker compose ps

# 查看所有日志
docker compose logs -f

# 查看特定服务日志
docker compose logs -f php
docker compose logs -f mysql

# 重启单个服务
docker compose restart php

# 停止所有服务
docker compose down

# 停止并删除数据卷（⚠️ 危险，会清空数据库）
docker compose down -v
```

### 4.2 健康检查

```bash
# 检查 nginx 是否正常
curl -I http://localhost

# 检查 PHP 是否正常
curl http://localhost/index.php

# 检查 MySQL 是否可连接（需要暴露端口）
mysql -h 127.0.0.1 -P 33060 -u epay -p
```

### 4.3 各容器状态预期

| 容器 | 预期状态 | 关键日志 |
|------|---------|----------|
| epay-nginx | `Up` | `"GET / HTTP/1.1" 200` |
| epay-php | `Up` | `[entrypoint] MySQL 连接成功` |
| epay-mysql | `Up` (healthy) | `ready for connections` |
| epay-cron | `Up` | `Cron started with key:` |

---

## 5. 首次配置指南

登录后台后进行以下配置：

### 5.1 修改管理员密码

1. 访问 `http://localhost/admin/`
2. 使用账号 `admin` 和 `.env` 中 `ADMIN_PASSWORD` 登录
3. 进入 **系统设置 → 修改密码**，设置新密码

### 5.2 配置站点信息

进入 **系统设置 → 站点配置**：

| 配置项 | 说明 | 示例 |
|--------|------|------|
| 站点名称 | 前台显示的网站名 | 某某支付平台 |
| 站点域名 | 填写实际域名 | pay.your-domain.com |
| 客服QQ | 用户联系用 | 123456789 |
| CDN 加速 | 无外网选本地 | 本地源 |

### 5.3 配置支付渠道

进入 **支付管理 → 支付插件**：
- 选择需要的支付通道，点击"配置"
- 填写商户号、密钥、应用ID等信息
- 开启通道状态

### 5.4 添加商户

进入 **商户管理 → 商户列表**：
- 添加商户，自动生成商户ID和密钥
- 设置结算方式、费率、分组

### 5.5 设置 Cron 密钥（如未自动）

进入 **系统设置 → 安全设置**，找到"监控密钥"，填入 `.env` 中 `CRON_KEY` 的值。

---

## 6. 运维管理

### 6.1 查看实时日志

```bash
# 所有服务
docker compose logs -f --tail=100

# 仅 PHP 错误
docker compose logs php | grep -i error

# 仅 nginx 访问日志
docker compose logs nginx | grep "GET\|POST"
```

### 6.2 进入容器调试

```bash
# 进入 PHP 容器
docker compose exec php sh

# 检查 PHP 扩展
php -m | grep -E "pdo|curl|gd|openssl|gmp"

# 检查 config.php
cat config.php

# 手动执行 cron 任务
php -r "file_get_contents('http://nginx/cron.php?key=YOUR_CRON_KEY');"
```

### 6.3 备份与恢复

```bash
# 备份数据库
docker compose exec mysql mysqldump -u epay -p epay > backup_$(date +%Y%m%d).sql

# 备份上传文件
docker compose cp php:/var/www/html/assets/uploads ./backup_uploads/

# 恢复数据库
docker compose exec -T mysql mysql -u epay -p epay < backup_20260428.sql

# 完整备份（数据库 + 上传文件）
mkdir -p backups/$(date +%Y%m%d)
docker compose exec mysql mysqldump -u epay -p epay > backups/$(date +%Y%m%d)/database.sql
docker compose cp php:/var/www/html/assets/uploads backups/$(date +%Y%m%d)/
```

### 6.4 日志清理

```bash
# 清理 Docker 日志（容器内）
docker compose exec nginx sh -c "> /var/log/nginx/epay-access.log"
docker compose exec nginx sh -c "> /var/log/nginx/epay-error.log"

# 清理 Docker 构建缓存
docker builder prune -f
```

---

## 7. 生产环境加固

### 7.1 强制修改的配置

`.env` 中必须修改：

```ini
MYSQL_ROOT_PASSWORD=<32位以上随机密码>
DB_PASSWORD=<32位以上随机密码>
ADMIN_PASSWORD=<16位以上随机密码>
CRON_KEY=<16位以上随机密码>
```

生成随机密码：

```bash
openssl rand -base64 32
```

### 7.2 HTTPS 配置

1. 获取 SSL 证书（Let's Encrypt 免费）：

```bash
# 使用 certbot
certbot certonly --standalone -d pay.your-domain.com

# 证书复制到项目
mkdir -p docker/ssl
cp /etc/letsencrypt/live/pay.your-domain.com/fullchain.pem docker/ssl/
cp /etc/letsencrypt/live/pay.your-domain.com/privkey.pem docker/ssl/
```

2. 在 `docker/nginx.conf` 中添加 HTTPS server 块，或在宿主机使用反向代理（推荐）。

3. 更新 `.env`：

```ini
SITE_URL=https://pay.your-domain.com
NGINX_SSL_PORT=443
```

### 7.3 关闭不必要的端口暴露

生产环境建议在 `.env` 中注释掉：

```ini
# 不暴露数据库端口到宿主机
# DB_EXPOSE_PORT=33060
```

### 7.4 防火墙配置

```bash
# 仅开放 80/443
ufw allow 80/tcp
ufw allow 443/tcp
ufw enable
```

### 7.5 安全 checklist

- [ ] 修改了所有默认密码
- [ ] 配置了 HTTPS
- [ ] 关闭了 `DB_EXPOSE_PORT`
- [ ] `config.php` 权限为 640
- [ ] MySQL `sql_mode=`（项目需要空 sql_mode）
- [ ] 定期备份数据库
- [ ] 监控 cron 容器是否正常运行
- [ ] 删除或限制 `/install/` 目录访问（nginx 已配置 deny）

### 7.6 资源限制（可选）

在 `docker-compose.yml` 中添加：

```yaml
services:
  php:
    deploy:
      resources:
        limits:
          memory: 512M
    # ... 其他配置

  mysql:
    deploy:
      resources:
        limits:
          memory: 1G
```

---

## 8. 故障排查

### 8.1 容器无法启动

```bash
# 查看详细错误
docker compose logs <容器名>

# 常见问题：
# "port is already allocated" → 端口冲突，修改 .env 中端口
# "MySQL 连接超时" → MySQL 未完全启动，等待重试或检查密码
```

### 8.2 502 Bad Gateway

```bash
# 检查 PHP 容器是否运行
docker compose ps php

# 检查 PHP 日志
docker compose logs php --tail=50

# 进入 nginx 容器测试连接
docker compose exec nginx sh
wget -O- http://php:9000/status
```

### 8.3 数据库连接失败

```bash
# 检查 MySQL 状态
docker compose logs mysql --tail=20

# 检查 .env 中密码一致
grep PASSWORD .env

# 进入 PHP 容器测试
docker compose exec php php -r "
\$pdo = new PDO('mysql:host=mysql;port=3306', 'epay', 'your-password');
echo 'OK';
"
```

### 8.4 首次安装后页面空白

原因：缺少 `syskey`，这是 Bug 1 的症状（已修复）。如果遇到：

```bash
# 手动检查
docker compose exec php php -r "
\$pdo = new PDO('mysql:host=mysql', 'epay', getenv('DB_PASSWORD'));
\$r = \$pdo->query(\"SELECT v FROM pay_config WHERE k='syskey'\")->fetchColumn();
echo \$r ? 'OK: '.\$r : 'MISSING';
"
```

修复：删除数据库卷重新安装：

```bash
docker compose down -v
docker compose up -d
```

### 8.5 Cron 任务不执行

```bash
# 检查 cron 容器日志
docker compose logs cron

# 确认 CRON_KEY 已配置
docker compose exec cron cat /etc/crontabs/root

# 手动测试
docker compose exec cron curl -v "http://nginx/cron.php?key=YOUR_CRON_KEY"
```

### 8.6 支付回调 403

原因：`checkRefererHost()` 检查 Referer 头。在 Docker 环境中，如果使用了反向代理或 CDN，需要确保 Referer 头正确传递。在 nginx 配置中添加：

```nginx
proxy_set_header Referer $http_referer;
```

---

## 9. 升级指南

### 9.1 代码升级

```bash
# 拉取最新代码
git pull origin main

# 重新构建并重启
docker compose up -d --build
```

entrypoint.sh 会自动检测数据库版本并执行升级 SQL。

### 9.2 仅升级数据库

如果只更新了 `install/update3.sql` 等升级脚本：

```bash
# 修改 Dockerfile 中 DB_VERSION 常量（或重启即可）
docker compose restart php
```

### 9.3 升级 PHP 版本

编辑 `Dockerfile` 第一行：

```dockerfile
FROM php:8.1-fpm-alpine  # 从 7.4 升级到 8.1
```

然后重新构建：

```bash
docker compose up -d --build
```

### 9.4 回滚

```bash
# 使用 Git 回滚代码
git checkout <previous-commit>

# 恢复数据库备份
docker compose exec -T mysql mysql -u epay -p epay < backup.sql

# 重新构建
docker compose up -d --build
```

---

## 附录：快速部署脚本

```bash
#!/bin/bash
# deploy.sh - 一键部署脚本
set -e

echo "=== 彩虹易支付 Docker 部署 ==="

# 生成随机密码
ROOT_PWD=$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9' | head -c 32)
DB_PWD=$(openssl rand -base64 24 | tr -dc 'a-zA-Z0-9' | head -c 32)
ADMIN_PWD=$(openssl rand -base64 12 | tr -dc 'a-zA-Z0-9' | head -c 16)

# 创建 .env
cat > .env << EOF
NGINX_PORT=80
DB_HOST=mysql
DB_PORT=3306
DB_USER=epay
DB_PASSWORD=${DB_PWD}
DB_NAME=epay
DB_PREFIX=pay
MYSQL_ROOT_PASSWORD=${ROOT_PWD}
ADMIN_PASSWORD=${ADMIN_PWD}
SITE_URL=http://localhost
CRON_KEY=
EOF

echo "✓ .env 已生成"
echo "  MySQL Root: ${ROOT_PWD}"
echo "  DB Password: ${DB_PWD}"
echo "  Admin: ${ADMIN_PWD}"

# 构建启动
docker compose up -d

# 等待启动
echo "等待服务启动..."
sleep 30

# 获取 CRON_KEY
CRON_KEY=$(docker compose logs php | grep 'CRON_KEY:' | tail -1 | awk '{print $NF}')
if [ -n "$CRON_KEY" ]; then
    sed -i "s/CRON_KEY=$/CRON_KEY=${CRON_KEY}/" .env
    docker compose up -d cron
    echo "✓ CRON_KEY: ${CRON_KEY}"
fi

echo ""
echo "=== 部署完成 ==="
echo "  前台: http://localhost"
echo "  后台: http://localhost/admin/"
echo "  账号: admin"
echo "  密码: ${ADMIN_PWD}"
echo ""
echo "  ⚠️  请保存以上密码，登录后立即修改！"
```
