FROM php:7.4-fpm-alpine

LABEL maintainer="epay"
LABEL description="彩虹易支付系统 - PHP Payment Gateway"

# 安装系统依赖和 PHP 扩展
RUN set -eux; \
    apk add --no-cache \
        freetype-dev \
        libpng-dev \
        libjpeg-turbo-dev \
        gmp-dev \
        curl-dev \
        libsodium-dev \
        oniguruma-dev \
        libxml2-dev \
        tzdata \
        rsync \
    ; \
    # 安装 PHP 扩展
    docker-php-ext-configure gd --with-freetype --with-jpeg; \
    docker-php-ext-install -j$(nproc) \
        pdo \
        pdo_mysql \
        mysqli \
        curl \
        mbstring \
        gd \
        gmp \
        bcmath \
        simplexml \
        opcache \
    ; \
    docker-php-ext-install -j$(nproc) sodium; \
    rm -rf /var/cache/apk/*

# 配置时区
ENV TZ=Asia/Shanghai
RUN ln -snf /usr/share/zoneinfo/$TZ /etc/localtime && echo $TZ > /etc/timezone

# 配置 PHP
RUN mv "$PHP_INI_DIR/php.ini-production" "$PHP_INI_DIR/php.ini"
COPY docker/php-custom.ini /usr/local/etc/php/conf.d/zz-epay.ini

# 设置工作目录
WORKDIR /var/www/html

# 复制项目文件到 staging 目录（运行时由 entrypoint 同步到 volume）
COPY --chown=www-data:www-data . /var/www/html-staging/

# 创建必需的目录并设置权限
RUN set -eux; \
    mkdir -p \
        /var/www/html-staging/assets/uploads \
        /var/www/html-staging/plugins/alipay/cert \
        /var/www/html-staging/plugins/wxpay/cert \
        /var/www/html-staging/plugins/wxpayn/cert \
        /var/www/html-staging/plugins/wxpayng/cert \
        /var/www/html-staging/plugins/wxpaynp/cert \
        /var/www/html-staging/plugins/douyinpay/cert \
    ; \
    chown -R www-data:www-data \
        /var/www/html-staging/assets/uploads \
        /var/www/html-staging/plugins \
        /var/www/html-staging/install \
        /var/www/html-staging/admin \
    ; \
    chmod -R 755 \
        /var/www/html-staging/assets/uploads \
        /var/www/html-staging/plugins \
        /var/www/html-staging/install \
        /var/www/html-staging/admin

# 复制入口脚本
COPY docker/docker-entrypoint.sh /usr/local/bin/
RUN chmod +x /usr/local/bin/docker-entrypoint.sh

ENTRYPOINT ["docker-entrypoint.sh"]
CMD ["php-fpm"]
