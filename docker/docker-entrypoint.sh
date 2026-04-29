#!/bin/sh
set -e

# ============================================
# 彩虹易支付 Docker 入口脚本
# 功能：自动安装、升级、配置检查
# ============================================

CONFIG_FILE="/var/www/html/config.php"
INSTALL_LOCK="/var/www/html/install/install.lock"
ADMIN_PASSWORD="${ADMIN_PASSWORD:-admin123456}"

echo "============================================"
echo "  彩虹易支付系统 - Docker 启动中..."
echo "============================================"

# 确保 Session 目录权限正确
mkdir -p /var/lib/php/sessions
chown www-data:www-data /var/lib/php/sessions

# ============================================
# 0. 从镜像同步代码到 volume（首次部署/镜像更新）
# ============================================
if [ -d "/var/www/html-staging" ]; then
    echo "[entrypoint] 同步代码至工作目录 ..."
    rsync -a --delete \
        --exclude='config.php' \
        --exclude='plugins/' \
        --exclude='install/install.lock' \
        --exclude='assets/uploads/' \
        --exclude='cache/' \
        --exclude='ip2region.xdb' \
        /var/www/html-staging/ /var/www/html/
    echo "[entrypoint] 代码同步完成"
fi

# 确保插件 cert 目录存在（bind mount 后 Dockerfile 的 mkdir 不生效）
mkdir -p \
    /var/www/html/plugins/alipay/cert \
    /var/www/html/plugins/wxpay/cert \
    /var/www/html/plugins/wxpayn/cert \
    /var/www/html/plugins/wxpayng/cert \
    /var/www/html/plugins/wxpaynp/cert \
    /var/www/html/plugins/douyinpay/cert

# ============================================
# 1. 生成 config.php（如果不存在）
# ============================================
if [ ! -f "$CONFIG_FILE" ] || grep -q "'host' *=> *'localhost'" "$CONFIG_FILE" 2>/dev/null; then
    echo "[entrypoint] 生成 config.php ..."
    cat > "$CONFIG_FILE" << PHPEOF
<?php
/*数据库配置*/
\$dbconfig=array(
    'host' => '${DB_HOST:-mysql}',
    'port' => ${DB_PORT:-3306},
    'user' => '${DB_USER:-epay}',
    'pwd' => '${DB_PASSWORD:-epay123}',
    'dbname' => '${DB_NAME:-epay}',
    'dbqz' => '${DB_PREFIX:-pay}'
);
PHPEOF
    chown www-data:www-data "$CONFIG_FILE"
    chmod 640 "$CONFIG_FILE"
    echo "[entrypoint] config.php 已生成"
else
    echo "[entrypoint] config.php 已存在，跳过"
fi

# ============================================
# 2. 等待 MySQL 就绪
# ============================================
echo "[entrypoint] 等待 MySQL 连接 ${DB_HOST:-mysql}:${DB_PORT:-3306} ..."
MAX_RETRIES=30
RETRY_COUNT=0
until php -r "
include '$CONFIG_FILE';
try {
    new PDO('mysql:host='.\$dbconfig['host'].';port='.\$dbconfig['port'].';charset=utf8mb4', \$dbconfig['user'], \$dbconfig['pwd'], [PDO::ATTR_TIMEOUT => 3]);
    echo 'connected';
} catch (Exception \$e) {
    exit(1);
}
" 2>/dev/null; do
    RETRY_COUNT=$((RETRY_COUNT + 1))
    if [ $RETRY_COUNT -ge $MAX_RETRIES ]; then
        echo "[entrypoint] 错误: MySQL 连接超时"
        exit 1
    fi
    echo "[entrypoint]   重试 $RETRY_COUNT/$MAX_RETRIES ..."
    sleep 2
done
echo "[entrypoint] MySQL 连接成功"

# ============================================
# 3. 检查是否需要安装/升级
# ============================================
DB_CONFIG=$(php -r "
include '$CONFIG_FILE';
echo json_encode(\$dbconfig);
")

DB_HOST=$(echo "$DB_CONFIG" | php -r 'echo json_decode(file_get_contents("php://stdin"))->host;')
DB_PORT=$(echo "$DB_CONFIG" | php -r 'echo json_decode(file_get_contents("php://stdin"))->port;')
DB_USER=$(echo "$DB_CONFIG" | php -r 'echo json_decode(file_get_contents("php://stdin"))->user;')
DB_PWD=$(echo "$DB_CONFIG" | php -r 'echo json_decode(file_get_contents("php://stdin"))->pwd;')
DB_NAME=$(echo "$DB_CONFIG" | php -r 'echo json_decode(file_get_contents("php://stdin"))->dbname;')
DB_PREFIX=$(echo "$DB_CONFIG" | php -r 'echo json_decode(file_get_contents("php://stdin"))->dbqz;')

DSN="mysql:host=${DB_HOST};port=${DB_PORT};dbname=${DB_NAME};charset=utf8mb4"

TABLE_EXISTS=$(php -r "
try {
    \$pdo = new PDO('${DSN}', '${DB_USER}', '${DB_PWD}', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    \$stmt = \$pdo->query(\"SELECT COUNT(*) FROM information_schema.tables WHERE table_schema='${DB_NAME}' AND table_name='${DB_PREFIX}_config'\");
    echo \$stmt->fetchColumn();
} catch (Exception \$e) {
    echo '0';
}
")

if [ "$TABLE_EXISTS" = "0" ]; then
    # ============================================
    # 首次安装：执行 install.sql
    # ============================================
    echo "[entrypoint] 首次安装，执行数据库初始化 ..."

    # 生成随机密钥
    SYSKEY=$(php -r "echo substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 32);")
    CRONKEY=$(php -r "echo bin2hex(random_bytes(16));")
    BUILD_DATE=$(date +%Y-%m-%d)

    php -r "
    \$pdo = new PDO('${DSN}', '${DB_USER}', '${DB_PWD}', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    \$pdo->exec(\"set sql_mode = ''\");
    \$pdo->exec('set names utf8mb4');

    // 执行 install.sql
    \$sql = file_get_contents('/var/www/html/install/install.sql');
    \$sqls = explode(';', \$sql);
    \$success = 0;
    \$error = 0;

    foreach (\$sqls as \$value) {
        \$value = trim(\$value);
        if (empty(\$value)) continue;
        \$value = str_replace('pre_', '${DB_PREFIX}_', \$value);
        try {
            \$pdo->exec(\$value);
            \$success++;
        } catch (Exception \$e) {
            \$error++;
            echo '[entrypoint] SQL错误: ' . \$e->getMessage() . PHP_EOL;
        }
    }

    // 插入动态配置
    \$pdo->exec(\"INSERT INTO \`${DB_PREFIX}_config\` VALUES ('syskey', '${SYSKEY}')\");
    \$pdo->exec(\"INSERT INTO \`${DB_PREFIX}_config\` VALUES ('build', '${BUILD_DATE}')\");
    \$pdo->exec(\"INSERT INTO \`${DB_PREFIX}_config\` VALUES ('cronkey', '${CRONKEY}')\");

    // 修改默认管理员密码
    \$pdo->exec(\"UPDATE \`${DB_PREFIX}_config\` SET v='${ADMIN_PASSWORD}' WHERE k='admin_pwd'\");
    \$pdo->exec(\"UPDATE \`${DB_PREFIX}_config\` SET v='${ADMIN_PASSWORD}' WHERE k='admin_paypwd'\");

    echo '[entrypoint] 安装完成! 成功: '.\$success.' 条, 失败: '.\$error.' 条' . PHP_EOL;
    echo '[entrypoint] 系统密钥/定时任务密钥已生成，可通过以下方式获取定时任务密钥：' . PHP_EOL;
    echo '[entrypoint]   1. 登录后台 → 系统设置 → 安全设置 → 监控密钥' . PHP_EOL;
    echo '[entrypoint]   2. 或执行: docker compose exec php cat /cron_key.txt' . PHP_EOL;
    echo '[entrypoint] 获取密钥后请填入 .env 的 CRON_KEY 并重启: docker compose up -d cron' . PHP_EOL;
    echo '[entrypoint] 请尽快登录后台修改管理员密码！' . PHP_EOL;
    // 将定时任务密钥写入文件（不在web目录下，仅容器内可读）
    file_put_contents('/cron_key.txt', '${CRONKEY}');
    "

    touch "$INSTALL_LOCK"
    chown www-data:www-data "$INSTALL_LOCK"
    echo "[entrypoint] install.lock 已创建"

else
    echo "[entrypoint] 数据库已安装，检查版本 ..."

    DB_VERSION=$(php -r "
    try {
        \$pdo = new PDO('${DSN}', '${DB_USER}', '${DB_PWD}', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        \$stmt = \$pdo->query(\"SELECT v FROM ${DB_PREFIX}_config WHERE k='version'\");
        echo \$stmt->fetchColumn();
    } catch (Exception \$e) {
        echo '0';
    }
    ")

    CURRENT_VERSION="2054"

    if [ -n "$DB_VERSION" ] && [ "$DB_VERSION" != "0" ] && [ "$DB_VERSION" != "$CURRENT_VERSION" ]; then
        echo "[entrypoint] 数据库版本: $DB_VERSION → $CURRENT_VERSION，执行升级 ..."
        php -r "
        \$pdo = new PDO('${DSN}', '${DB_USER}', '${DB_PWD}', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
        \$pdo->exec(\"set sql_mode = ''\");
        \$pdo->exec('set names utf8mb4');

        // 执行更新 SQL
        if (file_exists('/var/www/html/install/update2.sql')) {
            \$sql = file_get_contents('/var/www/html/install/update2.sql');
            \$sqls = explode(';', \$sql);
            foreach (\$sqls as \$value) {
                \$value = trim(str_replace('pre_', '${DB_PREFIX}_', \$value));
                if (!empty(\$value)) \$pdo->exec(\$value);
            }
        }
        if (file_exists('/var/www/html/install/update3.sql')) {
            \$sql = file_get_contents('/var/www/html/install/update3.sql');
            \$sqls = explode(';', \$sql);
            foreach (\$sqls as \$value) {
                \$value = trim(str_replace('pre_', '${DB_PREFIX}_', \$value));
                if (!empty(\$value)) \$pdo->exec(\$value);
            }
        }

        \$pdo->exec(\"UPDATE \`${DB_PREFIX}_config\` SET v='${CURRENT_VERSION}' WHERE k='version'\");
        \$pdo->exec(\"UPDATE \`${DB_PREFIX}_cache\` SET v='' WHERE k='config'\");
        echo '[entrypoint] 数据库升级完成' . PHP_EOL;
        "
    else
        echo "[entrypoint] 数据库已是最新版本 ($CURRENT_VERSION)"
    fi

    # 检查并补充缺失的关键配置（syskey/cronkey）
    php -r "
    \$pdo = new PDO('${DSN}', '${DB_USER}', '${DB_PWD}', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    \$pdo->exec(\"set sql_mode = ''\");

    \$syskey = \$pdo->query(\"SELECT v FROM ${DB_PREFIX}_config WHERE k='syskey'\")->fetchColumn();
    if (!\$syskey) {
        \$new_syskey = substr(str_shuffle('abcdefghijklmnopqrstuvwxyz0123456789'), 0, 32);
        \$pdo->exec(\"INSERT INTO ${DB_PREFIX}_config VALUES ('syskey', '\$new_syskey')\");
        echo '[entrypoint] 已补充 SYS_KEY' . PHP_EOL;
    }

    \$cronkey = \$pdo->query(\"SELECT v FROM ${DB_PREFIX}_config WHERE k='cronkey'\")->fetchColumn();
    if (!\$cronkey) {
        \$new_cronkey = bin2hex(random_bytes(16));
        \$pdo->exec(\"INSERT INTO ${DB_PREFIX}_config VALUES ('cronkey', '\$new_cronkey')\");
    }

    \$adminpwd = \$pdo->query(\"SELECT v FROM ${DB_PREFIX}_config WHERE k='admin_pwd'\")->fetchColumn();
    if (\$adminpwd == '123456') {
        \$pdo->exec(\"UPDATE ${DB_PREFIX}_config SET v='${ADMIN_PASSWORD}' WHERE k='admin_pwd'\");
        \$pdo->exec(\"UPDATE ${DB_PREFIX}_config SET v='${ADMIN_PASSWORD}' WHERE k='admin_paypwd'\");
        \$pdo->exec(\"UPDATE ${DB_PREFIX}_cache SET v='' WHERE k='config'\");
        echo '[entrypoint] 已更新管理员密码' . PHP_EOL;
    }

    \$build = \$pdo->query(\"SELECT v FROM ${DB_PREFIX}_config WHERE k='build'\")->fetchColumn();
    if (!\$build) {
        \$pdo->exec(\"INSERT INTO ${DB_PREFIX}_config VALUES ('build', '\".date('Y-m-d').\"')\");
    }

    \$cache = \$pdo->query(\"SELECT v FROM ${DB_PREFIX}_cache WHERE k='config'\")->fetchColumn();
    if (empty(\$cache)) {
        \$pdo->exec(\"INSERT INTO ${DB_PREFIX}_cache VALUES ('config', '', 0)\");
    }
    "

    # 确保 install.lock 存在
    if [ ! -f "$INSTALL_LOCK" ]; then
        touch "$INSTALL_LOCK"
        chown www-data:www-data "$INSTALL_LOCK"
        echo "[entrypoint] install.lock 已重建"
    fi
    
fi

# ============================================
# 4. 检查默认管理员密码
# ============================================
ADMIN_PWD_CURRENT=$(php -r "
try {
    \$pdo = new PDO('${DSN}', '${DB_USER}', '${DB_PWD}', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    \$stmt = \$pdo->query(\"SELECT v FROM ${DB_PREFIX}_config WHERE k='admin_pwd'\");
    echo \$stmt->fetchColumn();
} catch (Exception \$e) {
    echo '';
}
")

if [ "$ADMIN_PWD_CURRENT" = "123456" ]; then
    echo "============================================"
    echo "  ⚠️  安全警告：管理员密码仍为默认值 123456"
    echo "  请立即登录后台修改密码！"
    echo "  后台地址: /admin/"
    echo "============================================"
fi

# ============================================
# 5. 检查 ip2region.xdb
# ============================================
if [ ! -f "/var/www/html/includes/ip2region.xdb" ]; then
    echo "[entrypoint] ip2region.xdb 不存在，正在自动下载 ..."
    if curl -sL -o /var/www/html/includes/ip2region.xdb \
        "https://raw.githubusercontent.com/lionsoul2014/ip2region/master/data/ip2region.xdb"; then
        chown www-data:www-data /var/www/html/includes/ip2region.xdb
        echo "[entrypoint] ip2region.xdb 下载完成，IP 归属地功能可用"
    else
        echo "[entrypoint] 提示: ip2region.xdb 下载失败，IP 归属地功能将不可用"
        echo "[entrypoint] 手动下载: https://github.com/lionsoul2014/ip2region"
    fi
fi

echo "============================================"
echo "  启动 PHP-FPM ..."
echo "============================================"

# 切换到 www-data 用户启动 PHP-FPM
exec "$@"
